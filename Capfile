set :deploy_config_path, 'app/deploy/deploy.rb'
set :stage_config_path, 'app/deploy/stages'
set :format_options, log_file: "var/logs/capistrano.log"


Dir.glob("app/deploy/tasks/*.rake").each { |r| import r }
Dir.glob('app/deploy/tasks/*.rb').each { |r| import r }

# previous variables MUST be set before 'capistrano/setup'
require 'capistrano/setup'
require "capistrano/deploy"
require "capistrano/scm/git"
install_plugin Capistrano::SCM::Git