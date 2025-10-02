# Commands to build images

# API image
```bash
docker buildx build -f api/Dockerfile -t dev_env:php_84 .
docker tag dev_env:php_84 dev_env:php_84
```

# Server image
```bash
docker buildx build -f server/Dockerfile -t dev_env:server .
docker tag dev_env:server dev_env:server
```