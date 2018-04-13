#!/usr/bin/env python
import pika
import json
import os
import yaml

from NetworkManagerTf import NetworkManager
from NetworkManagerTf import restore_network

os.umask(002)

config_file = open("app/config/parameters.yml", "r")
parameters = (yaml.load(config_file))['parameters']
credentials = pika.PlainCredentials(parameters['rabbitmq_user'], parameters['rabbitmq_password'])

connection = pika.BlockingConnection(pika.ConnectionParameters(
    host=parameters['rabbitmq_host'],
    credentials=credentials,
    heartbeat_interval=0
))

channel = connection.channel()
channel.exchange_declare(exchange='get-prediction')
result = channel.queue_declare(queue='get-prediction-queue', exclusive=True)
channel.queue_bind(
    exchange='get-prediction',
    queue=result.method.queue,
    routing_key=''
)


def on_request(ch, method, props, body):
    print(" [.] got get prediction request: (%r)" % body)
    message = json.loads(body)

    print("constructing network...")
    network = NetworkManager.create(message, True)
    print("construction done.")
    model = restore_network(network, message['networkFilePath'])
    outputs = NetworkManager.predict(
        model,
        inputs=message['inputData']
    )
    print("raw ouputs:")
    print(outputs)

    ch.basic_publish(exchange='',
                     routing_key=props.reply_to,
                     properties=pika.BasicProperties(correlation_id=props.correlation_id),
                     body=json.dumps({'outputs': outputs[0].tolist()}))
    ch.basic_ack(delivery_tag=method.delivery_tag)

channel.basic_qos(prefetch_count=1)
channel.basic_consume(on_request, queue='get-prediction-queue')

print(" [x] Awaiting RPC requests")
channel.start_consuming()
