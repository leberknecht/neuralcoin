#!/usr/bin/env python

import boto.s3.connection
from collections import deque
from boto.s3.key import Key

conn = boto.s3.connect_to_region(
    'eu-central-1',
    aws_access_key_id='',
    aws_secret_access_key=''
)
bucket = conn.get_bucket('neuralcoin-network-data')
versions = bucket.list_versions(prefix='e7a43acb-b390-11e7-aa44-021725b121c1/e7a43acb-b390-11e7-aa44-021725b121c1.tflearn')
for version in versions:
    print version.last_modified + "  - " + version.version_id + " -  " + version.name

version = deque(versions)[0]

print version.version_id

# message ='{"trainingDataFile":"a6d0df23-a098-11e7-aa44-021725b121c1\\/training-data.59c6c523b9d82.csv","trainingRunId":"7b3e52da-a09e-11e7-aa44-021725b121c1","epochs":15,"generateImage":false,"numberOfHiddenLayers":3,"activationFunction":"tanh","networkFilePath":"a6d0df23-a098-11e7-aa44-021725b121c1\\/a6d0df23-a098-11e7-aa44-021725b121c1.tflearn","inputLength":58,"outputLength":2,"classify":true,"learningRate":0.1,"useDropout":false,"dropout":null,"bias":true,"optimizer":"RMSprop","customShape":"1","shape":[30,20,25]}'
# message = json.loads(message)
# manager = NetworkManagerTf.NetworkManager()
# model = manager.create(message, keep_existing=True)
# manager.train(model, training_data_file=message['trainingDataFile'],
#             epochs=20)
# print("done")
#
#
# message = '{"predictionId":"2ba3b501-a2b0-11e7-aa44-021725b121c1","inputData":[0.2601908065915,-0.60344827586208,0.17271157167532,0.52083333333333,-0.51813471502589,0.2886893931114,0.31855777584708,-1.4210854715202e-14,0.26132404181185,1.4134275618375,-0.7017543859649,1.423487544484,-1.4035087719298,0,0,0.64744559431698,-0.29343309859155,0,0,0,0.35335689045937,0.26572187776794,-0.65992080950286,-0.71344791575119,-0.46377391304348,-0.1736111111111,0,-0.77519379844961,-0.42881646655232,-0.51194539249147,0,0.6872852233677,0,-0.68259385665529,0,-1.3468013468013,-0.25188916876576,-0.4180602006689,1.7006802721089,-0.59171597633136,0.3818413237166,0.25521054870268,-0.38135593220338,1.1139674378749,-0.085616438356169,-1.4210854715202e-14,-1.2679628064243,-0.25295109612141,0.28185458184073,0.39614601018674,0.42625745950555,-0.59322033898304,1.1139674378749,0.77720207253888,-0.68610634648373,-0.25662959794695,-1.433389544688,1.3675213675214,-1.2658227848101,0.53733600324298],"numberOfHiddenLayers":4,"activationFunction":"tanh","networkFilePath":"ecf9157e-a149-11e7-aa44-021725b121c1\\/ecf9157e-a149-11e7-aa44-021725b121c1.tflearn","inputLength":60,"outputLength":2,"classify":true,"learningRate":0.001,"useDropout":true,"dropout":0.6,"bias":true,"optimizer":"adam","customShape":"1","shape":[40,20,35,28]}'
# message = json.loads(message)
# manager = NetworkManagerTf.NetworkManager()
# model = manager.create(message, keep_existing=True)
# #manager.predict(model,)
#
#
# message ='{"trainingDataFile":"training-data.59d5415ad1ce1.csv","trainingRunId":"7b3e52da-a09e-11e7-aa44-021725b121c1","epochs":8,"generateImage":false,"numberOfHiddenLayers":3,"activationFunction":"tanh","networkFilePath":"a6d0df23-a098-11e7-aa44-021725b121c1\\/a6d0df23-a098-11e7-aa44-021725b121c1.tflearn","inputLength":10,"outputLength":2,"classify":true,"learningRate":0.1,"useDropout":false,"dropout":null,"bias":true,"optimizer":"RMSprop","customShape":"1","shape":[10,8,10]}'
# message = json.loads(message)
# manager = NetworkManagerTf.NetworkManager()
# model = manager.create(message, keep_existing=False)
# manager.train(model, training_data_file=message['trainingDataFile'],
#             epochs=20)
# print("done")
#
#
#
# input_data = [
#     [[2, 3, 2, 1.5, 2.1, 1, 4, 5], [0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, ]]
# ]
#
# output_data = [
#     [0, 1]
# ]
#
#
# input_layer = tflearn.input_data(shape=[None, 2, 8], name='input')
# dense1 = tflearn.fully_connected(input_layer, 3, name='dense0')
# dense2 = tflearn.fully_connected(dense1, 8, name='dense1')
# dense3 = tflearn.fully_connected(dense2, 7, name='dense2')
# output = tflearn.fully_connected(dense3, 1, activation="relu", name="output")
# regression = tflearn.regression(output, optimizer='adam', loss='mean_square',
#                                 learning_rate=0.001)
#
# model = tflearn.DNN(regression)
# model.fit(input_data, output_data, n_epoch=200,
#           show_metric=True,
#           snapshot_epoch=True,
#           run_id='model_and_weights')
#
# print(model.predict([[2, 3, 2, 1.5, 2.1, 1, 4, 5]]))
# print(model.predict([[3, 2, 2.4, 1.7, 2.3, 1.2, 3, 4.2]]))
#
# imageCreator = NetworkImageCreator.NetworkImageCreator()
# svg_content = imageCreator.generate_svg(model)
#
# output_file = open('output.svg', 'w')
# output_file.write(svg_content)
# output_file.close()
