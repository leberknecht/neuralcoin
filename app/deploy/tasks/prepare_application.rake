namespace :deploy do
    task :prepare_application do
        on roles(:app) do
            #pp Capistrano::Configuration.env
            execute "cd #{release_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{release_path}; sudo chmod g+w . -R"
            execute "cp #{release_path}/docker-compose.yml.dist #{release_path}/docker-compose.yml"
            execute "cd #{release_path}; docker-compose -p deploy_tmp build"
            execute "cd #{release_path}; docker-compose -p deploy_tmp run --rm nc_phpfpm composer install --no-interaction --prefer-dist"
            execute "cd #{release_path}; docker-compose -p deploy_tmp run --rm nc_phpfpm bin/console assets:install --symlink --relative"
            execute "cd #{release_path}; docker-compose -p deploy_tmp stop"
            execute "cd #{release_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{current_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{release_path}; sudo chmod g+w . -R"
            execute "cd #{current_path}; sudo chmod g+w . -R"
            execute "cd #{shared_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{shared_path}; sudo chmod g+w . -R"
        end
    end
end

