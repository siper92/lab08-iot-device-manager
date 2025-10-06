# lab08-iot-device-manager
IOT device management system 

## Steps to setup and run the project

1. Build images - go to _env/dev directory and check build.md for details
2. Setup docker compose 
```bash
cp _env/dev/docker-compose.yaml docker-compose.yaml
```
3. Run docker compose
```bash
docker-compose up -d
```

4. Access the API container
```bash
docker-compose exec -it api bash
```

5. Project utils commands can be found in commands.md file

# Technical Details

## Technology Stack
- **Framework**: Laravel 12
- **Database**: MySQL
- **Message Queue**: Apache Kafka (optional true configuration, app_custom.php)
- **Extra PHP Package**: Firebase JWT, Junges Kafka

## Usage
When using docker the API is configured to responde to localhost:80/api/<endpoint>, for docker usage se _env/dev. 

## Extras
 - kafka integration for processing measurements - (allow salability, logs, and more)
 - JWT auth for all users
 - Flexible alert processing system, that can be extended, uses Interfaces and Services to process alerts.

## System routes and users  

#### Admin - admins are system created users who can manage devices and users
 - create regular users
 - create devices
 - attach/detach devices to/from users
 - delete users and devices
 - login to get auth token

#### Regular Users - regular users can manage their own devices and view measurements and alerts
- attach/detach devices to/from themselves
- view their own measurements
- view their own alerts
- login to get auth token

#### Devices - devices can submit measurements, require AUTH token received during attachment (simulate registration or someting like that)
- submit measurements to the system

------------------------------
[x] POST       admin/devices .......................................... AdminDeviceController@create
[x] POST       admin/devices/{deviceId}/attach ........................ AdminDeviceController@attach
[x] DELETE     admin/devices/{deviceId}/detach ........................ AdminDeviceController@detach
[x] DELETE     admin/devices/{id} ..................................... AdminDeviceController@delete
[x] POST       admin/login ............................................... AdminAuthController@login
[x] POST       admin/users .............................................. AdminUserController@create
[x] DELETE     admin/users/{id} ......................................... AdminUserController@delete
[X] POST       devices/{deviceId}/measurements .................. DeviceMeasurementController@submit
[x] POST       login ...................................................... UserAuthController@login
[X] GET|HEAD   users/{userId}/alerts ........................... UserMeasurementController@getAlerts
[X] POST       users/{userId}/devices/{deviceId}/attach ................ UserDeviceController@attach
[X] DELETE     users/{userId}/devices/{deviceId}/detach ................ UserDeviceController@detach
[X] GET|HEAD   users/{userId}/measurements ............... UserMeasurementController@getMeasurements