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
PROJECT_DIR="${PROJECT_DIR:-/var/www/cdattg_gibse}"
ENVIRONMENT="${ENVIRONMENT:-development}"
GIT_BRANCH="${GIT_BRANCH:-main}"

# Seleccionar perfil de Docker Compose según el entorno
if [ "$ENVIRONMENT" = "production" ]; then
    COMPOSE_PROFILE="prod"
else
    COMPOSE_PROFILE="dev"
fi

echo "🔄 Iniciando actualización del sitio..."
echo "🌿 Rama configurada: $GIT_BRANCH"
echo "🔧 Entorno: $ENVIRONMENT"
echo "📦 Perfil Docker Compose: $COMPOSE_PROFILE"

cd $PROJECT_DIR

echo "📥 Obteniendo últimos cambios de Git..."
git fetch origin
git checkout $GIT_BRANCH
git pull origin $GIT_BRANCH

echo "🏗️ Reconstruyendo contenedor Docker..."
docker-compose --profile $COMPOSE_PROFILE build --no-cache

echo "🔄 Reiniciando contenedor..."
docker-compose --profile $COMPOSE_PROFILE down
docker-compose --profile $COMPOSE_PROFILE up -d

echo "🧹 Limpiando imágenes antiguas..."
docker image prune -f

echo "✅ Actualización completada!"
echo ""
echo "📊 Estado del contenedor:"
docker ps | grep cdattg-gibse-web

echo ""
echo "📝 Logs recientes:"
docker logs --tail 20 cdattg-gibse-web

