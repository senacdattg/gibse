#!/bin/bash

set -e

# Cargar variables de entorno desde .env
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
if [ -f "$PROJECT_ROOT/.env" ]; then
    set -a
    source "$PROJECT_ROOT/.env"
    set +a
fi

# Variables con valores por defecto si no están en .env
PROJECT_DIR="${PROJECT_DIR:-/var/www/gibse}"
COMPOSE_FILE="${DOCKER_COMPOSE_FILE:-docker-compose.yml}"
GIT_BRANCH="${GIT_BRANCH:-main}"

echo "🔄 Iniciando actualización del sitio..."
echo "🌿 Rama configurada: $GIT_BRANCH"

cd $PROJECT_DIR

echo "📥 Obteniendo últimos cambios de Git..."
git fetch origin
git checkout $GIT_BRANCH
git pull origin $GIT_BRANCH

echo "🏗️ Reconstruyendo contenedor Docker..."
docker-compose -f $COMPOSE_FILE build --no-cache

echo "🔄 Reiniciando contenedor..."
docker-compose -f $COMPOSE_FILE down
docker-compose -f $COMPOSE_FILE up -d

echo "🧹 Limpiando imágenes antiguas..."
docker image prune -f

echo "✅ Actualización completada!"
echo ""
echo "📊 Estado del contenedor:"
docker ps | grep gibse-web

echo ""
echo "📝 Logs recientes:"
docker logs --tail 20 gibse-web

