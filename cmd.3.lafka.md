# lab08-iot-device-manager kafka command utils

## Starting Kafka and Zookeeper with Docker (in the docker config)

### View Kafka logs
```bash
# View Kafka logs
docker-compose logs -f kafka
```

### View Zookeeper logs
```bash
# View Zookeeper logs only
docker-compose logs -f zookeeper
```

## Working with Topics

### Create a Topic

```bash
docker exec -it lab08_kafka kafka-topics --create \
  --bootstrap-server localhost:9092 \
  --replication-factor 1 \
  --partitions 3 \
  --topic measurements_test
```

### List Topics

```bash
docker exec -it lab08_kafka kafka-topics --list \
  --bootstrap-server localhost:9092
```

### Describe a Topic

```bash
docker exec -it lab08_kafka kafka-topics --describe \
  --bootstrap-server localhost:9092 \
  --topic measurements_test
```

### Delete a Topic

```bash
docker exec -it lab08_kafka kafka-topics --delete \
  --bootstrap-server localhost:9092 \
  --topic measurements_test
```

## Testing Message Flow

### Produce Test Messages

```bash
echo '{"sensor_id":"temp-001","value":23.5}' | docker exec -i lab08_kafka kafka-console-producer \
  --bootstrap-server localhost:9092 \
  --topic measurements_test
```

Type your messages and press Enter after each one. Press Ctrl+C to exit.

### Consume Messages

```bash
docker exec -it lab08_kafka kafka-console-consumer \
  --bootstrap-server localhost:9092 \
  --topic measurements_test \
  --from-beginning
```

Press Ctrl+C to exit.