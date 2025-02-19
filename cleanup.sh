#!/bin/bash

# if [ -z "$1" ]; then
#     echo "Usage: ./cleanup.sh <project_name>"
#     exit 1
# fi

PROJECT_NAME="broker"
APP_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "Stopping and removing Docker containers, networks, and volumes for project $PROJECT_NAME..."


# Stop and remove Docker containers, networks, and volumes
docker compose down -v

# Delete the data in the app/config/jwt/ directory
echo "Deleting data in $APP_DIR/config/jwt/ file..."
rm -rf "$APP_DIR/app/config/jwt"


# Delete the data in the docker/db/data directory
if [ -d "$APP_DIR/docker/db/data" ]; then
    echo "Deleting data in $APP_DIR/docker/db/data directory..."
    rm -rf "$APP_DIR/docker/db/data"
    echo "Data in $APP_DIR/docker/db/data directory deleted."
else
    echo "Directory $APP_DIR/docker/db/data does not exist."
fi

echo "Cleanup complete for project $PROJECT_NAME."