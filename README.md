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