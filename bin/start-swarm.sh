#!/usr/bin/env bash
set -e
PUBLIC_IP=$(curl http://169.254.169.254/latest/meta-data/public-ipv4)
echo "using public ip: $PUBLIC_IP"

docker swarm init --advertise-addr ${PUBLIC_IP}

echo "MAKE SURE TO ADD '--advertise-addr <YOUR_PUBLIC_IP>' if you are behind a firewall!"


#docker node update --label-add foo