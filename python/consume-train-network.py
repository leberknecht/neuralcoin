#!/usr/bin/env python
import pika
import json
import sys
import os
import math
from NetworkImageCreator import NetworkImageCreator

from cStringIO import StringIO

os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'
from NetworkManagerTf import *

config_file = open("app/config/parameters.yml", "r")
parameters = (yaml.load(config_file))['parameters']
credentials = pika.PlainCredentials(parameters['rabbitmq_user'], parameters['rabbitmq_password'])

os.umask(002)
connection = pika.BlockingConnection(pika.ConnectionParameters(
    host=parameters['rabbitmq_host'],
    credentials=credentials,
    heartbeat_interval=0
))
channel = connection.channel()
channel.queue_declare(queue='train-network')
channel.queue_declare(queue='training-status')


def consume_callback(ch, method, properties, body):
    print(" [x] got message: %r " % body)

    message = json.loads(body)

    print("constructing network...")
    network = NetworkManager.create(message, True)
    print("construction done.")

    print("restoring weights + bias..")
    model = restore_network(network, message['networkFilePath'])
    print("restore done.")

    channel.basic_publish(
        exchange='',
        routing_key='training-status',
        body='{"trainingRunId": "' + message["trainingRunId"] + '", "status": "in progress"}'
    )

    backup = sys.stdout
    sys.stdout = StringIO()
    training_successful = True
    last_error = 0
    try:
        print("starting training for network " + message['networkFilePath'])
        last_error = NetworkManager.train(
            model,
            training_data_file=message['trainingDataFile'],
            epochs=message['epochs']
        )
        if math.isnan(last_error):
            print("training done without exception, but error is not a number")
            training_successful = False
    except Exception as exception:
        print("exception occured during training: " + exception.message)
        print(exception)
        training_successful = False

    out = sys.stdout.getvalue()
    sys.stdout.close()  # close the stream
    sys.stdout = backup

    print("console output: %s" % out)

    if training_successful:
        print(" [x] training done, error: " + str(last_error) + " sending result, set-length: " + str(
            NetworkManager.last_training_set_length))

        print("saving weights + bias..")
        save_network(network, message['networkFilePath'])
        print("saving done.")

        if message['generateImage']:
            image_file_name = message['networkFilePath'].split('/')[0] + '/' + message['trainingRunId'] + ".svg"
            image_creator = NetworkImageCreator()
            print("creating network SVG...")
            svg_content = image_creator.generate_svg(model)
            #svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg width="40" height="40"><circle cx="20" cy="20" r="20" /></svg>'
            store_file(image_file_name, svg_content)
            print("done, network image: " + image_file_name)
            out += "\nOverall connection-count in model (including dropped out): " + str(image_creator.connections_drawn)
        else:
            image_file_name = ''

        channel.basic_publish(
            exchange='',
            routing_key='training-status',
            body=json.dumps({
                'trainingRunId': message["trainingRunId"],
                'status': "finished",
                'imagePath': image_file_name,
                'error': str(last_error),
                'rawOutput': ("[...]\n" + out[-4096:]) if len(out) > 4096 else out,
                'trainingSetLength': str(NetworkManager.last_training_set_length),
            })
        )
    else:
        channel.basic_publish(
            exchange='',
            routing_key='training-status',
            body=json.dumps({
                'trainingRunId': message["trainingRunId"],
                'status': "error",
                'rawOutput': ("[...]\n" + out[:-4096]) if len(out) > 4096 else out
            })
        )

channel.basic_consume(consume_callback,
                      queue='train-network',
                      no_ack=True)

print(" [x] Awaiting training requests")

while True:
    try:
        channel.start_consuming()
    except IOError, e:
        print "got io-error, number: " + str(e.errno) + ", message: " + e.strerror
        connection.close()
        if e.errno != 4:
            raise
