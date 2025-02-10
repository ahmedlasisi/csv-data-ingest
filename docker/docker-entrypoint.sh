#!/bin/sh
set -e

# Wait for the database to be ready
echo "⏳ Waiting for the database to be ready..."
until nc -z -v -w30 mariadb 3306; do
  echo "Waiting for MariaDB..."
  sleep 5
done
echo "✅ MariaDB is ready!"

# Run database migrations
echo "⚙️ Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache and warm up
echo "🗑️ Clearing and warming up the cache..."
php bin/console cache:clear
php bin/console cache:warmup

# Load seed data if needed (Optional)
if [ "$LOAD_FIXTURES" = "true" ]; then
  echo "🌱 Loading fixtures..."
  php bin/console doctrine:fixtures:load --no-interaction
fi

# Execute the CMD from the Dockerfile (e.g., Apache or PHP-FPM)
exec "$@"
