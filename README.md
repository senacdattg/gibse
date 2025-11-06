# GIBSE - Gestión Integral de la Biodiversidad

Aplicación web PHP para el programa de Tecnología en Gestión Integral de la Biodiversidad y los Servicios Ecosistémicos - SENA

## Estructura del Proyecto

```
gibse/
├── assets/              # Recursos estáticos (CSS, JS, imágenes, videos)
├── docker/              # Configuraciones de Docker
│   ├── docker-compose.prod.yml
│   └── nginx.conf
├── scripts/              # Scripts de actualización
│   └── update.sh
├── webhooks/             # Webhooks para actualización automática
│   └── webhook.php
├── docs/                 # Documentación
│   ├── GUIA_DESPLIEGUE.md
│   └── ACTUALIZACION_AUTOMATICA.md
├── Dockerfile           # Configuración de la imagen Docker
├── docker-compose.yml   # Docker Compose para desarrollo
├── index.php            # Página principal
└── .htaccess            # Configuración de Apache
```

## Configuración Inicial

### Variables de Entorno

1. Copia el archivo de ejemplo:
   ```bash
   cp .env.example .env
   ```

2. Edita `.env` con tus valores:
   ```bash
   nano .env
   ```

3. **IMPORTANTE**: Genera un secreto seguro para el webhook:
   ```bash
   openssl rand -hex 32
   ```

Ver documentación completa en: [docs/CONFIGURACION_ENV.md](docs/CONFIGURACION_ENV.md)

## Desarrollo Local

### Requisitos
- Docker
- Docker Compose

### Ejecutar

```bash
# Opción 1: Usando Docker Compose (Recomendado)
docker-compose up -d --build

# Opción 2: Construir y ejecutar manualmente
docker build -t gibse-app .
docker run -d --name gibse-web -p 8080:80 gibse-app
```

El sitio estará disponible en: `http://localhost:8080`

**💡 Nota:** Docker Compose es más simple porque maneja la configuración automáticamente.

## Despliegue en Producción

Ver la guía completa en: [docs/GUIA_DESPLIEGUE.md](docs/GUIA_DESPLIEGUE.md)

### Resumen rápido:

1. Subir archivos al VPS
2. Instalar Docker y Docker Compose
3. Configurar Nginx
4. Construir y ejecutar: `docker-compose -f docker/docker-compose.prod.yml up -d`
5. Configurar DNS
6. Configurar SSL: `sudo certbot --nginx -d gibse.dataguaviare.com.co`

## Actualización Automática

Ver la guía completa en: [docs/ACTUALIZACION_AUTOMATICA.md](docs/ACTUALIZACION_AUTOMATICA.md)

### Configuración rápida:

1. Configurar webhook en GitHub/GitLab apuntando a: `https://gibse.dataguaviare.com.co/webhooks/webhook.php`
2. Actualizar el secreto en `webhooks/webhook.php`
3. Cada push a `main` actualizará automáticamente el sitio

## Tecnologías

- PHP 8.2
- Apache
- Docker
- Nginx (reverse proxy en producción)

## Licencia

SENA - Gestión Integral de la Biodiversidad

