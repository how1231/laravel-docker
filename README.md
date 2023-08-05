# laravel-docker

- Simple setup for laravel + mysql + phpmyadmin + docker
- api ready (tested with postman)

### Steps:

* Download docker desktop
* Clone this laravel project
* Follow the commands below

### Commands:

1) setup
```
docker-compose build --no-cache --force-rm
docker-compose up -d
docker exec provider_integration bash -c "composer update"
```

2) configure db connection in src/.env
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=provider_integration
DB_USERNAME=root
DB_PASSWORD=password
```

3) configure storage permission
```
docker exec provider_integration bash -c "php artisan storage:link"
docker exec provider_integration bash -c "chmod -R 777 storage"
docker exec provider_integration bash -c "php artisan cache:clear"
docker exec provider_integration bash -c "php artisan config:clear"
docker exec provider_integration bash -c "php artisan config:cache"
```

4) rebuild image and container
```
docker-compose up -d --build --force-recreate
```

5) migrate and seed
```
docker exec provider_integration bash -c "php artisan migrate:refresh --seed"
```

### Debugger
```
docker exec provider_integration bash -c "composer dump-autoload"
```

### Local Dev Env
```
web/api: localhost:9000
phpmyadmin: localhost:9001
```
