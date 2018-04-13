from __future__ import absolute_import, division, print_function

import yaml
import boto.s3.connection
import os
import errno
from boto.s3.key import Key
import tflearn
import tensorflow as tf
import glob
import tempfile
from collections import deque

config_file = open("app/config/parameters.yml", "r")
parameters = (yaml.load(config_file))['parameters']
print(parameters)
bucket = None
network_storage_path = parameters['local_network_data_directory'] + '/'


def mkdir_p(path):
    try:
        os.makedirs(path)
    except OSError as exc:  # Python >2.5
        if exc.errno == errno.EEXIST and os.path.isdir(path):
            pass
        else:
            raise


if parameters['network_data_adapter'] == 's3_filesystem_adapter':
    conn = boto.s3.connect_to_region(
        'eu-central-1',
        aws_access_key_id=parameters['aws_key'],
        aws_secret_access_key=parameters['aws_secret']
    )
    bucket = conn.get_bucket(parameters['aws_s3_bucket'])
    print("using bucket: " + parameters['aws_s3_bucket'])


def read_from_s3(key):
    k = Key(bucket)
    k.key = key
    return k.get_contents_as_string()


def get_training_data_content(training_data_file):
    if bucket:
        print("reading training data from bucket: " + training_data_file)
        file_content = read_from_s3(training_data_file)
        file_content = file_content.split("\n")
    else:
        training_file = open(network_storage_path + training_data_file, 'r')
        file_content = training_file.readlines()
    return file_content[:-1]


def restore_network(model, network_file):
    if bucket:
        print('reading network from bucket ' + network_file)
        for key in bucket.list(prefix=network_file):
            print("downloading " + key.name)
            key.get_contents_to_filename(network_storage_path + key.name)

        print('network loaded from bucket, s3 path: ' + network_file)

    full_network_path = network_storage_path + network_file
    print("loading from file: %s" % full_network_path)
    try:
        model.load('./' + full_network_path, weights_only=True)
    except (tf.errors.DataLossError, tf.errors.OutOfRangeError) as exception:
        if ("Checksum does not match" in exception.message) or ("Read less bytes than requested" in exception.message):
            print("stored network file is corrupted, dropping latest versions...")
            files = ['data-00000-of-00001', 'index', 'meta']
            for s3_file in files:
                key_name = network_file + "." + s3_file
                version = deque(bucket.list_versions(prefix=key_name))[0]
                print("deleting " + key_name + " version: " + version.version_id)
                bucket.delete_key(key_name, version_id=version.version_id)

            print("corrupted files removed, re-try to load the model...")
            model = restore_network(model, network_file)
        else:
            print("unhandled exception: " + exception.message)
            raise exception

    print('network loaded')
    return model


def save_network(model, network_file):
    full_path = network_storage_path + network_file
    directory = full_path[:full_path.rfind('/')]
    print("ensuring directory exists: " + directory)
    mkdir_p(directory)

    print("writing network to local file system, path: " + full_path)
    model.save(full_path)
    print("network saved to local filesystem")
    if bucket:
        print("fetching files from: " + full_path)
        files = glob.glob(full_path + '*')
        for tmpFile in files:
            target_file = network_file + os.path.splitext(tmpFile)[1]
            print("uploading file: " + tmpFile + " to: " + target_file)
            store_file(target_file, file_get_contents(tmpFile))
        print("network saved to s3 at path: " + network_file)
    return


def file_get_contents(filename):
    with open(filename) as f:
        return f.read()


def store_file(full_path, content):
    if bucket:
        print("writing file to s3: " + full_path + ", data-length: " + str(len(content)))
        file_handle = tempfile.NamedTemporaryFile(delete=False)
        file_handle.write(content)
        file_handle.close()
        k = Key(bucket)
        k.key = full_path
        k.set_contents_from_filename(file_handle.name)
        os.unlink(file_handle.name)
    else:
        full_path = network_storage_path + full_path
        print("writing local file, path: " + full_path + ", data-length: " + str(len(content)))
        file_handle = open(full_path, 'w')
        file_handle.write(content)
        file_handle.close()


class TrainingCallbackHandler(tflearn.callbacks.Callback):
    def __init__(self):
        pass

    def on_epoch_end(self, training_state):
        print("training epoch ended, error: %s" % training_state.global_loss)
        NetworkManager.last_training_error = training_state.global_loss


class NetworkManager:
    def __init__(self):
        pass

    last_training_set_length = 0
    last_training_error = 0.0
    current_session = None

    @staticmethod
    def create(message, keep_existing):
        print('creating network...')
        number_of_hidden_layers = message['numberOfHiddenLayers']
        activation_function = message['activationFunction'].encode('ascii', 'ignore')
        input_length = int(message['inputLength'])
        output_length = int(message['outputLength'])
        network_file = message['networkFilePath']
        classify = message['classify']
        learning_rate = message['learningRate']
        bias = message['bias']
        use_dropout = message['useDropout']
        dropout = message['dropout']
        has_custom_shape = message['customShape']
        shape = message['shape']

        tf.reset_default_graph()
        input_layer = tflearn.input_data(shape=[None, input_length], name='input')
        layer_to_connect_to = input_layer
        layer_activation = None

        if activation_function != 'none':
            layer_activation = activation_function

        for x in range(0, int(number_of_hidden_layers)):
            layer_to_connect_to = tflearn.fully_connected(
                layer_to_connect_to,
                input_length if not has_custom_shape else shape[x],
                weights_init='uniform',
                name='dense' + str(x),
                activation=layer_activation,
                bias=bias,
                regularizer='L2'
            )
            if use_dropout:
                layer_to_connect_to = tflearn.dropout(layer_to_connect_to, dropout)

        finished_network = tflearn.fully_connected(
            layer_to_connect_to,
            output_length,
            regularizer='L2',
            activation='softmax' if classify else 'linear',
            name='output',
            bias=bias
        )

        optimizer = 'adam'
        if 'SGD' == message['optimizer']:
            optimizer = tflearn.SGD(learning_rate=learning_rate)
        elif 'RMSprop' == message['optimizer']:
            optimizer = tflearn.RMSProp(learning_rate=learning_rate)
        elif 'Momentum' == message['optimizer']:
            optimizer = tflearn.Momentum(learning_rate=learning_rate)

        network_graph = tflearn.regression(
            finished_network,
            optimizer=optimizer,
            learning_rate=learning_rate,
            loss='categorical_crossentropy' if classify else 'mean_square',
            metric='default' if classify else 'R2',
        )

        model = tflearn.DNN(network_graph)
        print('network created.')

        if not keep_existing:
            save_network(model, network_file)

        return model

    @staticmethod
    def train(
            model,
            training_data_file,
            epochs
            ):
        training_data = get_training_data_content(training_data_file)
        set_count = 0
        input_data = []
        output_data = []
        print("input length: %s" % model.inputs[0].shape.dims[1].value)
        input_length = model.inputs[0].shape.dims[1].value
        for line in training_data:
            data = [float(x) for x in line.strip().split(',') if x != '']
            input_data.append(tuple(data[:(int(input_length))]))
            output_data.append(tuple(data[(int(input_length)):]))
            set_count += 1
        NetworkManager.last_training_set_length = set_count

        split_offset = int((set_count - (set_count / 4)))
        in_test = input_data[split_offset:]
        out_test = output_data[split_offset:]
        in_training = input_data[:split_offset]
        out_training = output_data[:split_offset]

        callback_handler = TrainingCallbackHandler()
        model.fit(in_training,
                  out_training,
                  n_epoch=epochs,
                  validation_set=(in_test, out_test),
                  show_metric=True,
                  run_id='model_and_weights',
                  callbacks=callback_handler
                  )

        print("training finished, error: %s" % NetworkManager.last_training_error)

        return NetworkManager.last_training_error

    @staticmethod
    def predict(model, inputs):
        return model.predict([inputs])
