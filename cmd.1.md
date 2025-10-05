# lab08-iot-device-manager command utils

## Run docker compose
```bash
docker-compose up -d
```

## Restart containers
```bash
docker-compose restart
```

## SSH into the api container
```bash
docker exec -it api bash
``` 

## Install composer dependencies
```bash
docker exec -it api composer install
```

## Require composer dependencies
```bash
docker exec -it api composer require mateusjunges/laravel-kafka
```

## Run migrations and seed the database
```bash
docker exec -it api php artisan migrate:refresh --seed
```

## Run tests
```bash
docker exec -it api php artisan test
```

## Clear database
```bash
rm -rf ./_env/.database/*
```