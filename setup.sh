#!/bin/bash
set -euo pipefail  # Strict error handling

echo "🚀 Setting up project environment..."

APP_DIR="$(cd "$(dirname "$0")" && pwd)"

PROJECT_NAME="broker"
HTTP_PORT=${2:-8080}  # Default HTTP port to 8080 if not provided
HTTPS_PORT=${3:-443}  # Default HTTPS port to 443 if not provided
DB_PORT=3306
DB_PORT_WEB=3806
DOMAIN="$PROJECT_NAME.test"
DB_HOST="${PROJECT_NAME}_mariadb"
DB_NAME="${PROJECT_NAME}_db"
DB_USER="${PROJECT_NAME}_user"
PHP_VERSION="8.2"
DB_IMAGE=mariadb:11.4.5
DB_VERSION=11.4.5-MariaDB-1
EMAIL="hello@$PROJECT_NAME.com"
DATABASE_URL="mysql://\${DB_USER}:\${DB_PASSWORD}@\${DB_HOST}:\${DB_PORT}/\${DB_NAME}?serverVersion=\${DB_VERSION}&charset=utf8mb4"

# Function to check if a port is available
check_port() {
    local PORT=$1
    if lsof -i:$PORT >/dev/null; then
        echo "Port $PORT is already in use by the following process:"
        lsof -i:$PORT
        read -p "Do you want to free up port $PORT? (y/n): " choice
        case "$choice" in
            y|Y )
                echo "Freeing up port $PORT..."
                PID=$(lsof -ti:$PORT)
                if [ -n "$PID" ]; then
                    kill -9 $PID
                    echo "Port $PORT has been freed."
                else
                    echo "Failed to free up port $PORT."
                    exit 1
                fi
                ;;
            n|N )
                echo "Port $PORT is in use. Exiting..."
                exit 1
                ;;
            * )
                echo "Invalid choice. Exiting..."
                exit 1
                ;;
        esac
    fi
}

# Check if the specified ports are available
check_port $HTTP_PORT

echo "Creating project directory in $APP_DIR..."
mkdir -p app && cd app

echo "Checking .env file..."
echo "📄 Creating .env from .env.docker..."
cp "$APP_DIR/.env.docker" "$APP_DIR/app/.env"

APP_SECRET=$(openssl rand -hex 32)
DB_PASSWORD=$(openssl rand -hex 20)
DB_ROOT_PASSWORD=$(openssl rand -hex 25)
JWT_PASSPHRASE=$(openssl rand -hex 64)
OAUTH_PASSPHRASE=$(openssl rand -hex 32)
OAUTH_ENCRYPTION_KEY=$(openssl rand -hex 32)

echo "🚀 Setting up environment variables dynamically..."

sed -i '' "s|__APP_SECRET__|$APP_SECRET|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_HOST__|$DB_HOST|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_PORT__|$DB_PORT|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_PORT_WEB__|$DB_PORT_WEB|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_NAME__|$DB_NAME|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_USER__|$DB_USER|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_PASSWORD__|$DB_PASSWORD|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_ROOT_PASSWORD__|$DB_ROOT_PASSWORD|" "$APP_DIR/app/.env"
sed -i '' "s|__DB_VERSION__|$DB_VERSION|" "$APP_DIR/app/.env"
sed -i '' "s|__JWT_PASSPHRASE__|$JWT_PASSPHRASE|" "$APP_DIR/app/.env"
sed -i '' "s|__OAUTH_PASSPHRASE__|$OAUTH_PASSPHRASE|" "$APP_DIR/app/.env"
sed -i '' "s|__OAUTH_ENCRYPTION_KEY__|$OAUTH_ENCRYPTION_KEY|" "$APP_DIR/app/.env"

# Create .env file for Docker Compose
cat <<EOF > "$APP_DIR/.env"
APP_ENV=dev
PROJECT_NAME=$PROJECT_NAME
DB_PORT=$DB_PORT
DB_VERSION=$DB_VERSION
DB_USER=${PROJECT_NAME}_user
DB_HOST=${PROJECT_NAME}_mariadb
DB_NAME=${PROJECT_NAME}_db
DB_PASSWORD=$DB_PASSWORD
DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD
HTTP_PORT=$HTTP_PORT
HTTPS_PORT=$HTTPS_PORT

EOF

sleep 3

echo "Installing mkcert..."
if ! command -v mkcert &> /dev/null; then
    echo "Install mkcert. Required mkcert command not found. Exiting..."
    exit 1
fi

echo "Creating local CA with mkcert..."
mkcert -install
mkdir -p "$APP_DIR/certs"

echo "Generating SSL certificates with mkcert..."
mkcert -cert-file "$APP_DIR/certs/$DOMAIN.pem" -key-file "$APP_DIR/certs/$DOMAIN-key.pem" $DOMAIN phpmyadmin.$DOMAIN

echo "Updating /etc/hosts file..."
# Add entry to /etc/hosts
if ! grep -q "$DOMAIN" /etc/hosts; then
    echo "127.0.0.1 $DOMAIN" | sudo tee -a /etc/hosts
fi
if ! grep -q "phpmyadmin.$DOMAIN" /etc/hosts; then
    echo "127.0.0.1 phpmyadmin.$DOMAIN" | sudo tee -a /etc/hosts
fi

echo "Starting Docker containers..."
cd "$APP_DIR"
docker compose down
docker compose up -d --build

echo "Ensuring correct file permissions..."
if [ -d "$APP_DIR/app/var" ]; then
    chmod -R 775 "$APP_DIR/app/var"
else
    echo "Directory $APP_DIR/app/var does not exist."
fi

if [ -d "$APP_DIR/app/public" ]; then
    chmod -R 775 "$APP_DIR/app/public"
else
    echo "Directory $APP_DIR/app/public does not exist."
fi

docker compose exec -T app composer install

# Ensure database is ready
echo "⏳ Waiting for MariaDB to be ready..."
timeout=100
elapsed=0
while ! docker compose exec -T database mariadb -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
    sleep 2
    elapsed=$((elapsed + 2))
    if [ $elapsed -ge $timeout ]; then
        echo "❌ MariaDB is not ready after $timeout seconds."
        exit 1
    fi
done

echo "✅ MariaDB is ready."

# Run migrations only if the database is empty
if ! docker compose exec -T app php bin/console doctrine:migrations:status | grep -q "executed migrations"; then
    echo "📦 Running database migrations..."
    docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
fi

echo "🔑 Generating JWT Keys (Skip if they already exist)."
docker compose exec -T app php bin/console lexik:jwt:generate-keypair --skip-if-exists

# Load Symfony fixtures if any exist
if docker compose exec -T app php bin/console doctrine:fixtures:load --no-interaction; then
    echo "📦 Loaded Symfony fixtures."
else
    echo "⚠️ No Symfony fixtures found or failed to load."
fi

# Load SQL queries from init.sql if it exists
if [ -f "$APP_DIR/init/init.sql" ]; then
    echo "📄 Loading SQL queries from init.sql..."
    docker compose exec -T database mariadb -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "init/init.sql"
    echo "✅ SQL queries from init.sql loaded."
fi

echo "Setup complete! Visit https://$DOMAIN in your browser."
echo "phpMyAdmin is accessible at https://phpmyadmin.$DOMAIN"
