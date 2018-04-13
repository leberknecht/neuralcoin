FROM python:2.7.11
MAINTAINER Delf <delf.tonder@gmx.de>

RUN pip install --upgrade pip
RUN pip install scipy numpy pika
RUN pip install pyyaml
RUN pip install boto
RUN pip install pathlib
RUN pip install svgwrite
RUN pip install tensorflow==1.4.1
RUN pip install tflearn==0.3.2

COPY wait-for-it.sh /usr/bin/wait-for-it
RUN chmod a+x /usr/bin/wait-for-it

WORKDIR /code