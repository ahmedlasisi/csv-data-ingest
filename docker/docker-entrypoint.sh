#!/bin/bash
set -e

echo "🚀 Running Docker Entrypoint for Broker Ingestion Service..."

echo "🚀 Setting up environment variables dynamically..."

# Ensure database is ready
echo "⏳ Waiting for MariaDB to be ready..."
until mysqladmin ping -h"$DB_HOST" --silent; do
    sleep 2
done

echo "✅ MariaDB is ready."

echo "🔑 Ensuring correct database privileges..."
mysql -h "$DB_HOST" -u root -p"$DB_PASSWORD" <<EOF
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '$DB_PASSWORD' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

# Run migrations only if the database is empty
if ! php bin/console doctrine:migrations:status | grep -q "executed migrations"; then
    echo "📦 Running database migrations..."
    php bin/console doctrine:migrations:sync-metadata-storage
    php bin/console doctrine:migrations:migrate --no-interaction
fi

# Load seed data if needed (Optional)
if [ "$LOAD_FIXTURES" = "true" ]; then
  echo "🌱 Loading fixtures..."
  php bin/console doctrine:fixtures:load --no-interaction
fi

# Generate JWT keys if they don't exist
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "🔑 Generating JWT keys..."
    php bin/console lexik:jwt:generate-keypair --force
fi

# Clear and warm up Symfony cache
echo "🗑️ Clearing cache..."
php bin/console cache:clear
php bin/console cache:warmup

# Execute the CMD from the Dockerfile (e.g., Apache or PHP-FPM)
exec "$@"