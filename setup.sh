#!/bin/bash
set -euo pipefail  # Strict error handling

echo "🚀 Setting up project environment..."

# Check and create necessary Docker volumes
REQUIRED_VOLUMES=("mariadb_data" "redis_data")
for VOLUME in "${REQUIRED_VOLUMES[@]}"; do
    if ! docker volume inspect "$VOLUME" &>/dev/null; then
        echo "⚠️  Volume $VOLUME not found. Creating it..."
        docker volume create "$VOLUME"
    else
        echo "✅ Volume $VOLUME exists."
    fi
done

echo "🚀 Setting up environment variables dynamically..."

# Generate secure values for placeholders
APP_SECRET=$(openssl rand -hex 32)
DB_PASSWORD=$(openssl rand -hex 20)
JWT_PASSPHRASE=$(openssl rand -hex 64)
OAUTH_PASSPHRASE=$(openssl rand -hex 32)
OAUTH_ENCRYPTION_KEY=$(openssl rand -hex 32)

# Replace placeholders in .env.docker
sed -i '' "s|__APP_SECRET__|$APP_SECRET|" ./.env.docker
sed -i '' "s|__DB_PASSWORD__|$DB_PASSWORD|" ./.env.docker
sed -i '' "s|__JWT_PASSPHRASE__|$JWT_PASSPHRASE|" ./.env.docker
sed -i '' "s|__OAUTH_PASSPHRASE__|$OAUTH_PASSPHRASE|" ./.env.docker
sed -i '' "s|__OAUTH_ENCRYPTION_KEY__|$OAUTH_ENCRYPTION_KEY|" ./.env.docker

# Copy .env.docker to .env if not exists
if [ ! -f .env ]; then
    echo "📄 Creating .env from .env.docker..."
    cp .env.docker .env
fi

echo "🔄 Stopping running containers (if any)..."
# docker compose down --remove-orphans -v
# docker compose build

# Step 2: Start Docker Containers
echo "🐳 Starting Docker containers..."
docker compose up -d

# Ensure MariaDB is fully ready before proceeding
echo "⏳ Waiting for MariaDB to be ready..."
max_attempts=5
attempts=0
until docker exec broker_mariadb mysqladmin ping -h"broker_mariadb" --silent &>/dev/null || [ $attempts -eq $max_attempts ]; do
    echo "⏳ DB is still initializing... retrying in 2 seconds ($((++attempts))/$max_attempts)"
    sleep 2
done

# If max attempts are reached, exit with error
if [ $attempts -eq $max_attempts ]; then
    echo "❌ DB failed to start after multiple attempts. Check logs with 'docker logs broker_mariadb'."
    exit 1
fi

echo "✅ Database is ready!"

# Generate JWT Keys (Skip if they already exist)
echo "🔑 Generating JWT keys..."
# Ensure the broker_app container is running before generating JWT keys
if docker ps | grep -q broker_app; then
    docker compose exec broker_app php bin/console lexik:jwt:generate-keypair --skip-if-exists
else
    echo "❌ broker_app container is not running. Attempting to start the container..."
    docker compose up -d broker_app

    # Run fixture code
    if docker ps | grep -q broker_app; then
        docker compose exec broker_app bash -c "
            if [ ! -f /var/task/fixtures_loaded ]; then
                php bin/console doctrine:fixtures:load --no-interaction && \
                touch /var/task/fixtures_loaded
            fi && \
            php-fpm
        "
    fi
    sleep 5  # Give some time for the container to start


    if docker ps | grep -q broker_app; then
        echo "✅ broker_app container started successfully. Rerunning the JWT key generation..."
        docker compose exec broker_app php bin/console lexik:jwt:generate-keypair --skip-if-exists
    # else
    #     echo "❌ Failed to start broker_app container. Please check the container logs and try again."
    #     exit 1
    fi
fi

# Ensure Cache & Log Directories Exist
echo "🛠️ Ensuring cache & log directories exist..."
if ! docker compose exec broker_app bash -c "[ -d var/cache ] && [ -d var/log ]"; then
    docker compose exec broker_app bash -c "
        mkdir -p var/cache var/log && \
        chown -R www-data:www-data var/cache var/log && \
        chmod -R 777 var/cache var/log
    "
else
    echo "✅ Cache & log directories already exist."
fi

# Clear and Warm Up Cache Properly
echo "🗑️ Clearing cache safely..."
docker compose exec broker_app bash -c "
    php bin/console cache:clear --no-warmup && \
    php bin/console cache:warmup
"

# Restart Symfony Server (Ensure Old Instances Are Stopped)
docker compose restart broker_app
symfony server:stop -all
symfony server:start

echo "🎉 Demo environment is ready! You can access the application."