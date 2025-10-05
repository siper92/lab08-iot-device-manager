# lab08-iot-device-manager test command utils

## Run migrations and seed the database
```bash
docker exec -it api php artisan migrate:refresh --seed
```

## Run tests
```bash
docker exec -it api php artisan test
```

## Run specific test file or test case
```bash
docker exec -it api php artisan test --filter UserManagementTest
```