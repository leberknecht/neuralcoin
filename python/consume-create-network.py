#!/usr/bin/env python
import pika
import json
import os
import yaml

from NetworkManagerTf import NetworkManager

os.umask(002)

config_file = open("app/config/parameters.yml", "r")
parameters = (yaml.load(config_file))['parameters']
credentials = pika.PlainCredentials(parameters['rabbitmq_user'], parameters['rabbitmq_password'])

connection = pika.BlockingConnection(pika.ConnectionParameters(
    credentials=credentials,
    host='nc_rabbitmq'
))

channel = connection.channel()
channel.exchange_declare(exchange='create-network')
result = channel.queue_declare(queue='create-network-queue', exclusive=True)
channel.queue_bind(exchange='create-network',
                   queue=result.method.queue,
                   routing_key='')


def on_request(ch, method, props, body):
    print(" [.] got create-network request: (%r)" % body)
    message = json.loads(body)
    NetworkManager.create(message, False)
    ch.basic_publish(exchange='',
                     routing_key=props.reply_to,
                     properties=pika.BasicProperties(correlation_id=props.correlation_id),
                     body='{"status": "success"}')
    ch.basic_ack(delivery_tag=method.delivery_tag)

channel.basic_qos(prefetch_count=1)
channel.basic_consume(on_request, queue='create-network-queue')

print(" [x] Awaiting RPC requests")
channel.start_consuming()
