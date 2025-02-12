#!/bin/bash
set -e

echo "🚀 Setting up Data Ingestion Demo Environment..."

echo "🚀 Setting up environment variables dynamically..."

# Generate secure values for placeholders
APP_SECRET=$(openssl rand -hex 32)
DB_PASSWORD=$(openssl rand -hex 40)
JWT_PASSPHRASE=$(openssl rand -hex 64)
OAUTH_PASSPHRASE=$(openssl rand -hex 32)
OAUTH_ENCRYPTION_KEY=$(openssl rand -hex 32)

# Replace placeholders in .env.docker
sed -i '' "s|__APP_SECRET__|$APP_SECRET|" ./.env.docker
sed -i '' "s|__DB_PASSWORD__|$DB_PASSWORD|" ./.env.docker
sed -i '' "s|__JWT_PASSPHRASE__|$JWT_PASSPHRASE|" ./.env.docker
sed -i '' "s|__OAUTH_PASSPHRASE__|$OAUTH_PASSPHRASE|" ./.env.docker
sed -i '' "s|__OAUTH_ENCRYPTION_KEY__|$OAUTH_ENCRYPTION_KEY|" ./.env.docker

# Copy .env.docker to .env (if not exists)
if [ ! -f .env ]; then
echo "📄 Creating .env from .env.docker..."
cp .env.docker .env
fi

echo "🔄 Stopping running containers (if any)..."
docker compose down -v
docker compose build --no-cache

#  Start Docker Containers
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

# Generate JWT Keys
echo "🔑 Generating JWT keys..."
docker compose exec broker_app php bin/console lexik:jwt:generate-keypair --skip-if-exists

# Restart Containers to Apply Changes
echo "🔄 Starting Up Server ..."
symfony server:start -d

echo "🎉 Demo environment is ready! You can access the application at https://127.0.0.1:8000/"
