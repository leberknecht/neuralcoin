# config valid only for current version of Capistrano

require 'capistrano/setup'
require 'capistrano/deploy'
require "pp"

set :application, "neuralcoin"
set :repo_url, "git@bitbucket.org:leberle/neuralcoin.git"
set :deploy_to, "/srv/neuralcoin_deploy"
set :linked_dirs, %w{var/log var/networks var/shared-images}