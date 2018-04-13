namespace :deploy do
  before "deploy:symlink:release", "deploy:prepare_application"
  after :deploy, "deploy:restart_docker"
end