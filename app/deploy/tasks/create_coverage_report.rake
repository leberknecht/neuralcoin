namespace :deploy do
    task :coverage_report do
        on roles(:app) do
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bin/console doc:data:create --env test || true"
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bin/console doc:schem:up --force --env test"
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bin/console doc:fix:load --env test"
            execute "cd #{release_path}; docker-compose -p neuralcoin run --rm nc_phpfpm bash -c 'umask 002 && vendor/bin/phpunit --coverage-html web/coverage'"
            execute "cd #{release_path}; sudo chown ubuntu:www-data . -R"
            execute "cd #{release_path}; sudo chmod g+w . -R"
        end
    end
end

