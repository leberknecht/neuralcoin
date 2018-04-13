set :stage, :staging
set :symfony_env, "prod"
set :front_controller, "app.php"

role :app,          %w{ubuntu@54.93.188.47}
role :dev_server,   %w{ubuntu@54.93.188.47}

server "54.93.188.47", user: "ubuntu", roles: %w{app dev_server}

