#!/bin/bash
set -e

echo "🚀 Setting up Data Ingestion Demo Environment..."

echo "🔄 Stopping running containers (if any)..."
docker compose down -v
docker compose build --no-cache

#  Start Docker Containers
echo "🐳 Starting Docker containers..."
docker compose up -d


# Restart Containers to Apply Changes
echo "🔄 Restarting Docker containers..."
# docker compose restart
symfony server:start -d

echo "🎉 Demo environment is ready! You can access the application at https://127.0.0.1:8000/"
