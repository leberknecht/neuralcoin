import tensorflow as tf
import NetworkManager
import numpy as np
from numpy import array



print 'starting training'


# Create model
def conv_net(x, weights, biases, dropout):
    # Reshape input picture
    x = tf.reshape(x, shape=[-1, 28, 28, 1])

    # Convolution Layer
    conv1 = conv2d(x, weights['wc1'], biases['bc1'])
    # Max Pooling (down-sampling)
    conv1 = maxpool2d(conv1, k=2)

    # Convolution Layer
    conv2 = conv2d(conv1, weights['wc2'], biases['bc2'])
    # Max Pooling (down-sampling)
    conv2 = maxpool2d(conv2, k=2)

    # Fully connected layer
    # Reshape conv2 output to fit fully connected layer input
    fc1 = tf.reshape(conv2, [-1, weights['wd1'].get_shape().as_list()[0]])
    fc1 = tf.add(tf.matmul(fc1, weights['wd1']), biases['bd1'])
    fc1 = tf.nn.relu(fc1)
    # Apply Dropout
    fc1 = tf.nn.dropout(fc1, dropout)

    # Output, class prediction
    out = tf.add(tf.matmul(fc1, weights['out']), biases['out'])
    return out

training_data = NetworkManager.get_training_data('bool-test-data.csv')
input_length = 20
output_length = 2

x = tf.placeholder(tf.float32, [None, input_length])
#y = tf.placeholder(tf.float32, [None, output_length])
W = tf.Variable(tf.zeros([input_length, output_length]))
b = tf.Variable(tf.zeros([output_length]))
y = tf.nn.softmax(tf.matmul(x, W) + b)

y_ = tf.placeholder(tf.float32, [None, output_length])
#pred = conv_net(x, weights, biases, keep_prob)

cross_entropy = tf.reduce_mean(-tf.reduce_sum(y_ * tf.log(y), reduction_indices=[1]))
train_step = tf.train.GradientDescentOptimizer(0.5).minimize(cross_entropy)
#sess = tf.InteractiveSession()


input_data = []
output_data = []
saver = tf.train.Saver()

sess = tf.Session()
print("loading model...")
saver.restore(sess, "model.ckpt")
print("Model restored.")

for line in training_data:
    data = [float(i) for i in line.strip().split(',') if i != '']
    input_data.append(data[:(int(input_length))])
    t = data[(int(input_length)):]
    if t[0] == 1:
        output_data.append([0, 1])
    else:
        output_data.append([1, 0])


def weight_variable(shape):
    initial = tf.truncated_normal(shape, stddev=0.1)
    return tf.Variable(initial)


def bias_variable(shape):
    initial = tf.constant(0.1, shape=shape)
    return tf.Variable(initial)


def conv2d(x, W):
    return tf.nn.conv2d(x, W, strides=[1, 1, 1, 1], padding='SAME')


def max_pool_2x2(x):
    return tf.nn.max_pool(x, ksize=[1, 2, 2, 1],
                        strides=[1, 2, 2, 1], padding='SAME')

W_conv1 = weight_variable([input_length, 1, 1, input_length])
b_conv1 = bias_variable([input_length])

for i in range(20):
    sess.run(train_step, feed_dict={x: input_data, y_: output_data})
    correct_prediction = tf.equal(tf.argmax(y, 1), tf.argmax(y_, 1))
    accuracy = tf.reduce_mean(tf.cast(correct_prediction, tf.float32))
    t = sess.run(accuracy, feed_dict={x: input_data, y_: output_data})
    print("Epoch:", '%04d' % (i + 1), "accuray:", (t))
    save_path = saver.save(sess, "model.ckpt")


print("Model saved in file: %s" % save_path)

writer = tf.summary.FileWriter('tensorboard_log', graph=sess.graph)
print("summary saved")
