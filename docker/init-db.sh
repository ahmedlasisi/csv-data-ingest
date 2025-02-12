#!/bin/bash
set -e

# Start MariaDB server in the background
mysqld_safe &

# Wait for MariaDB server to be ready
# until mysqladmin ping -h "localhost" --silent; do
#     echo "Waiting for MariaDB server to be ready..."
#     sleep 2
# done

# Execute the SQL commands
mysql -u root -p${MYSQL_ROOT_PASSWORD} <<-EOSQL
    CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};
    CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
    GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

# Keep the container running
tail -f /dev/null