import math
import yaml
import boto.s3.connection
import os

from boto.s3.key import Key
from pybrain.structure import FeedForwardNetwork
from pybrain.structure import LinearLayer, SigmoidLayer, TanhLayer, SoftmaxLayer
from pybrain.structure import FullConnection
from pybrain.tools.customxml.networkwriter import NetworkWriter
from pybrain.tools.customxml.networkreader import NetworkReader
from pybrain.datasets.supervised import SupervisedDataSet
from pybrain.datasets.classification import ClassificationDataSet
from pybrain.supervised.trainers.backprop import BackpropTrainer
from tempfile import NamedTemporaryFile

config_file = open("app/config/parameters.yml", "r")
parameters = (yaml.load(config_file))['parameters']
bucket = None
network_storage_path = parameters['local_network_data_directory'] + '/'


if parameters['network_data_adapter'] == 's3_filesystem_adapter':
    conn = boto.s3.connect_to_region(
        'eu-central-1',
        aws_access_key_id=parameters['aws_key'],
        aws_secret_access_key=parameters['aws_secret']
    )
    bucket = conn.get_bucket(parameters['aws_s3_bucket'])
    print "using bucket: " + parameters['aws_s3_bucket']


def read_from_s3(key):
    k = Key(bucket)
    k.key = key
    return k.get_contents_as_string()


def get_training_data(training_data_file):
    if bucket:
        print "reading training data from bucket: " + training_data_file
        file_content = read_from_s3(training_data_file)
        file_content = file_content.split("\n")
        print "got content: "
        print file_content
    else:
        training_file = open(network_storage_path + training_data_file, 'r')
        file_content = training_file.readlines()
    return file_content[:-1]


def get_network(network_file):
    if bucket:
        print "reading network from bucket"
        network_xml = read_from_s3(network_file)
        tmp_file = NamedTemporaryFile(delete=False)
        tmp_file.write(network_xml)
        tmp_file.close()
        print "temp file: " + tmp_file.name
        print "content: " + network_xml
        network = NetworkReader.readFrom(tmp_file.name)
        os.unlink(tmp_file.name)
    else:
        network = NetworkReader.readFrom(network_storage_path + network_file)
    return network


def save_network(network, network_file):
    if bucket:
        k = Key(bucket)
        k.key = network_file
        tmp_file = NamedTemporaryFile(delete=False)
        NetworkWriter.writeToFile(network, tmp_file.name)
        k.set_contents_from_filename(tmp_file.name)
        os.unlink(tmp_file.name)
        print "network saved to s3"
    else:
        NetworkWriter.writeToFile(network, network_storage_path + network_file)
        print "network saved to local filesystem"


class NetworkManager:
    last_training_set_length = 0
    @staticmethod
    def create(number_of_hidden_layers, activation_function, input_length, output_length, network_file, classify):
        n = FeedForwardNetwork(

        )
        in_layer = LinearLayer(input_length)
        n.addInputModule(in_layer)

        layer_to_connect_to = in_layer
        for x in range(0, number_of_hidden_layers):
            if activation_function == 'sigmoid':
                hidden_layer = SigmoidLayer(input_length)
            else:
                hidden_layer = TanhLayer(input_length)

            n.addModule(hidden_layer)
            hidden_layer_connection = FullConnection(layer_to_connect_to, hidden_layer)
            n.addConnection(hidden_layer_connection)
            layer_to_connect_to = hidden_layer

        if classify:
            out_layer = SoftmaxLayer(output_length)
        else:
            out_layer = LinearLayer(output_length)
        n.addOutputModule(out_layer)

        hidden_to_out = FullConnection(layer_to_connect_to, out_layer)
        n.addConnection(hidden_to_out)
        n.sortModules()
        save_network(n, network_file)

    @staticmethod
    def train(
            network_file,
            input_length,
            output_length,
            training_data_file,
            learning_rate,
            momentum,
            stop_on_convergence,
            epochs,
            classify):
        n = get_network(network_file)
        if classify:
            ds = ClassificationDataSet(int(input_length), int(output_length) * 2)
            ds._convertToOneOfMany()
        else:
            ds = SupervisedDataSet(int(input_length), int(output_length))
        training_data = get_training_data(training_data_file)

        NetworkManager.last_training_set_length = 0
        for line in training_data:
            data = [float(x) for x in line.strip().split(',') if x != '']
            input_data = tuple(data[:(int(input_length))])
            output_data = tuple(data[(int(input_length)):])
            ds.addSample(input_data, output_data)
            NetworkManager.last_training_set_length += 1

        t = BackpropTrainer(n, learningrate=learning_rate, momentum=momentum, verbose=True)
        print "training network " + network_storage_path + network_file

        if stop_on_convergence:
            t.trainUntilConvergence(ds, epochs)
        else:
            if classify:
                t.trainOnDataset(ds['class'], epochs)
            else:
                t.trainOnDataset(ds, epochs)

        error = t.testOnData()
        print "training done"
        if not math.isnan(error):
            save_network(n, network_file)
        else:
            print "error occured, network not saved"

        print "network saved"

        return error

    @staticmethod
    def predict(network_file, inputs):
        net = get_network(network_file)
        return net.activate(inputs)
