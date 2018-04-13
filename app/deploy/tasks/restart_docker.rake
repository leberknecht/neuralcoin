namespace :deploy do
    task :restart_docker do
        on roles(:app) do
            execute "cp #{deploy_to}/shared/parameters.yml #{current_path}/app/config/"
            execute "cd #{release_path}; echo #{fetch(:symfony_env)} > .sfenv"
            execute "cd #{current_path}; sed 's/app_dev\.php/#{fetch(:front_controller)}/g' docker/nginx/nginx.conf -i"
            execute "cd #{current_path}; docker-compose -p neuralcoin build"
            execute "cd #{current_path}; docker-compose -p neuralcoin stop"
            execute "cd #{current_path}; docker ps -a | tail -n +2 |  cut -d' ' -f1 | xargs docker rm -f"
            execute "cd #{current_path}; docker volume prune -f"
            execute "cd #{current_path}; sudo service docker stop"
            execute "cd #{current_path}; sudo service docker start"
            execute "cd #{current_path}; docker-compose -p neuralcoin up -d --force-recreate"
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bash -c 'umask 002 && bin/env-console doctri:data:create || true'"
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bash -c 'umask 002 && bin/env-console doctri:mig:mig'"
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bash -c 'umask 002 && bin/env-console cache:clear'"
            execute "cd #{current_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{current_path}; sudo chmod g+wr . -R"
            execute "cd #{release_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{release_path}; sudo chmod g+wr . -R"
            execute "cd #{release_path}; sudo bin/docker-ip-helper.sh"
        end
    end
end