# ⚙️ Configuración Técnica

Documentación técnica detallada sobre la configuración del proyecto GIBSE. Esta documentación es común para desarrollo y producción.

## 📋 Tabla de Contenidos

1. [Variables de Entorno](#variables-de-entorno)
2. [Configuración de Docker](#configuración-de-docker)
3. [Configuración de Nginx](#configuración-de-nginx)
4. [Actualización Automática con Webhook](#actualización-automática-con-webhook)
5. [Seguridad](#seguridad)
6. [Arquitectura del Sistema](#arquitectura-del-sistema)

---

**📖 Nota:** Para información específica de cada entorno, consulta:
- [Instalación para Desarrollo](../dev/instalacion.md) - Configuración específica de desarrollo
- [Instalación en Producción](../prod/instalacion.md) - Configuración específica de producción
- [Webhook en Producción](../prod/webhook.md) - Configuración detallada del webhook
- [Seguridad en Producción](../prod/seguridad.md) - Mejores prácticas de seguridad

**📖 Documentación específica:**
- [Instalación para Desarrollo](../dev/instalacion.md)
- [Instalación en Producción](../prod/instalacion.md)
- [Webhook en Producción](../prod/webhook.md)
- [Seguridad en Producción](../prod/seguridad.md)

---

## 🔐 Variables de Entorno

### Archivo `.env`

El archivo `.env` contiene todas las configuraciones del proyecto. **NUNCA** debe subirse a Git.

### Variables Disponibles

#### Configuración del Dominio

```env
DOMAIN=tu-dominio.com
```

- **Descripción:** Dominio donde estará disponible el sitio
- **Requerido:** Sí (para producción)
- **Ejemplo:** `app.tudominio.com`

#### Configuración del Servidor

```env
PROJECT_DIR=/var/www/cdattg_gibse
NGINX_SITES=/etc/nginx/sites-available
NGINX_ENABLED=/etc/nginx/sites-enabled
```

- **Descripción:** Rutas del sistema donde está el proyecto
- **Requerido:** No (valores por defecto)
- **Nota:** Ajusta según tu configuración del servidor

#### Docker - Configuración del Entorno

```env
ENVIRONMENT=production
```

- **Valores posibles:** `development` | `production`
- **Descripción:** Determina qué perfil de Docker Compose usar
- **Requerido:** Sí

**Comportamiento:**
- `production` → perfil "prod" (puerto 127.0.0.1:8081, sin volúmenes)
- `development` → perfil "dev" (puerto 8080, con volúmenes)

```env
DOCKER_CONTAINER_NAME=cdattg-gibse-web
```

- **Descripción:** Nombre del contenedor Docker
- **Requerido:** No (valor por defecto)

#### Configuración de Git

```env
GIT_BRANCH=main  # Para producción
# O
GIT_BRANCH=develop  # Para desarrollo
```

- **Descripción:** Rama de Git que se usará para despliegue
- **Requerido:** No (valor por defecto: `main`)
- **Uso:** 
  - **Producción:** Usa `main` (rama estable)
  - **Desarrollo:** Usa `develop` (rama de desarrollo)

#### Configuración del Webhook

```env
WEBHOOK_SECRET=genera_un_token_secreto_aqui
```

- **Descripción:** Token secreto para validar webhooks de GitHub
- **Requerido:** Sí (si usas webhook)
- **Generar token:** `openssl rand -hex 32`
- **⚠️ IMPORTANTE:** Debe ser el mismo que configures en GitHub

### Uso en PHP

```php
require_once __DIR__ . '/config/env-loader.php';

$domain = getEnvVar('DOMAIN', 'localhost');
$projectDir = getEnvVar('PROJECT_DIR', '/var/www/cdattg_gibse');
```

### Seguridad de Variables de Entorno

**✅ Buenas Prácticas:**

1. **Nunca subas `.env` a Git** (está en `.gitignore`)
2. **Permisos del archivo:** `chmod 600 .env`
3. **No compartas el archivo `.env`**
4. **Usa diferentes `.env` para cada entorno**
5. **No uses valores por defecto para configuraciones sensibles**

---

## 🐳 Configuración de Docker

### Perfiles de Docker Compose

El proyecto usa perfiles de Docker Compose para separar desarrollo y producción.

#### Perfil de Desarrollo (`dev`)

```yaml
services:
  web:
    profiles: ["dev"]
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
```

**Características:**
- Puerto `8080` expuesto públicamente
- Volúmenes montados para hot-reload
- Cambios en archivos se reflejan inmediatamente

**Uso:**
```bash
docker-compose --profile dev up -d
```

#### Perfil de Producción (`prod`)

```yaml
services:
  web-prod:
    profiles: ["prod"]
    ports:
      - "127.0.0.1:8081:80"
    # Sin volúmenes (código dentro de la imagen)
```

**Características:**
- Puerto `127.0.0.1:8081` (solo localhost)
- Sin volúmenes (mejor rendimiento)
- Código dentro de la imagen Docker

**Uso:**
```bash
docker-compose --profile prod up -d
```

### Dockerfile

El Dockerfile está basado en `php:8.4-apache` e incluye:

- Configuración de Apache
- Módulos: `rewrite`, `headers`, `expires`
- Permisos correctos para `www-data`
- Document root configurable

### Comandos Útiles

```bash
# Ver logs
docker logs cdattg-gibse-web
docker logs -f cdattg-gibse-web  # Seguir logs

# Reiniciar contenedor
docker-compose --profile prod restart

# Detener contenedor
docker-compose --profile prod down

# Ver estado
docker ps
docker-compose --profile prod ps

# Limpiar imágenes antiguas
docker image prune -f
```

---

## 🌐 Configuración de Nginx

### Archivo de Configuración

El archivo `config/nginx.conf` es una plantilla que debes copiar y editar.

### Configuración Básica

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tu-dominio.com;

    # Bloquear acceso a carpetas de configuración
    location ~ ^/(config|docs)/ {
        deny all;
        return 403;
    }

    # Endpoint del webhook (solo POST)
    location = /webhook.php {
        proxy_pass http://127.0.0.1:8081;
        # ... headers ...
        limit_except POST {
            deny all;
        }
    }

    # Resto de las peticiones
    location / {
        proxy_pass http://127.0.0.1:8081;
        # ... headers ...
    }
}
```

### Instalación

```bash
# Copiar configuración
DOMAIN="tu-dominio.com"
sudo cp config/nginx.conf /etc/nginx/sites-available/$DOMAIN

# Editar dominio
sudo nano /etc/nginx/sites-available/$DOMAIN

# Habilitar sitio
sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/$DOMAIN

# Verificar
sudo nginx -t

# Reiniciar
sudo systemctl reload nginx
```

### SSL/HTTPS con Let's Encrypt

```bash
# Instalar Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com

# Renovación automática (ya configurada)
sudo certbot renew
```

---

## 🔄 Actualización Automática con Webhook

### ¿Qué es el Webhook?

El webhook permite que GitHub notifique automáticamente al servidor cuando hay cambios, ejecutando el script de actualización sin intervención manual.

### Configuración del Webhook

#### Paso 1: Generar Token Secreto

```bash
# En el servidor
openssl rand -hex 32
```

#### Paso 2: Agregar Token al `.env`

```bash
cd /var/www/cdattg_gibse
nano .env
```

Agrega:
```env
WEBHOOK_SECRET=el_token_que_generaste
```

#### Paso 3: Configurar Webhook en GitHub

1. Ve a tu repositorio en GitHub
2. Ve a **Settings** → **Webhooks** → **Add webhook**
3. Configura:
   - **Payload URL:** `https://tu-dominio.com/webhook.php`
   - **Content type:** `application/json`
   - **Secret:** El mismo token que configuraste en `.env`
   - **Which events:** Selecciona "Just the push event"
   - **Active:** ✅ Marcado
4. Haz clic en **Add webhook**

#### Paso 4: Verificar Configuración

```bash
# Verificar y configurar permisos automáticamente
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

Este script verifica:
- ✅ Configuración de variables de entorno
- ✅ Permisos de archivos y directorios
- ✅ Funciones PHP necesarias
- ✅ Acceso de Docker para www-data
- ✅ Y corrige problemas automáticamente si se ejecuta con root

#### Paso 5: Probar el Webhook

1. Haz un cambio pequeño en el repositorio
2. Haz commit y push a la rama configurada:
   ```bash
   git commit --allow-empty -m "Test webhook"
   git push origin main  # Para producción (rama main)
   # O
   git push origin develop  # Para desarrollo (rama develop)
   ```
3. Verifica los logs:
   ```bash
   tail -f /var/www/cdattg_gibse/logs/webhook.log
   tail -f /var/www/cdattg_gibse/logs/update.log
   ```

### Seguridad del Webhook

- ✅ Valida el token secreto usando HMAC SHA-256
- ✅ Solo acepta peticiones POST
- ✅ Solo procesa eventos de push a la rama configurada
- ✅ Registra todas las peticiones en `logs/webhook.log`
- ✅ Nginx bloquea métodos HTTP distintos a POST
- ✅ Validación opcional de IPs de GitHub

### Flujo de Actualización Automática

```
1. Desarrollas en local
   ↓
2. git add . && git commit -m "Cambios"
   ↓
3. git push origin develop  # Desarrollo → rama develop
   # O git push origin main  # Producción → rama main
   ↓
4. GitHub envía webhook al servidor
   ↓
5. webhook.php valida y ejecuta update.sh automáticamente
   ↓
6. git pull de la rama configurada (main para prod, develop para dev)
   ↓
7. Docker rebuild
   ↓
8. Contenedor reiniciado
   ↓
9. Sitio actualizado automáticamente ✅
```

### Actualización Manual

Si prefieres actualizar manualmente:

```bash
cd /var/www/cdattg_gibse
./config/update.sh
```

---

## 🔒 Seguridad

Para información detallada sobre seguridad en producción, consulta:

- **[Seguridad en Producción](../prod/seguridad.md)** - Checklist completo, firewall, SSL, backups y más

### Seguridad Básica (Común)

**Permisos de Archivos:**

```bash
# Archivo .env
chmod 600 .env

# Scripts de configuración
chmod 700 config/*.sh

# Directorio de logs
chmod 755 logs/
```

**⚠️ IMPORTANTE:** El archivo `.env` **NUNCA** debe subirse a Git.

---

## 🏗️ Arquitectura del Sistema

### Desarrollo Local

```
┌─────────────────┐
│   Navegador     │
│  localhost:8080 │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Docker (Apache) │
│   Puerto 8080   │
│  Con volúmenes  │
└─────────────────┘
```

### Producción

```
┌─────────────────┐
│   Navegador      │
│  tu-dominio.com │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Nginx (Host)    │
│  Puerto 80/443  │
│  SSL/HTTPS      │
└────────┬────────┘
         │ (reverse proxy)
         ▼
┌─────────────────┐
│  Docker (Apache) │
│  127.0.0.1:8081 │
│  Sin volúmenes  │
└─────────────────┘
```

### Separación de Responsabilidades

- **Nginx:** Maneja SSL, reverse proxy, seguridad
- **Apache (Docker):** Sirve la aplicación PHP
- **Docker:** Aísla la aplicación y sus dependencias

Esta arquitectura es una práctica estándar en la industria.

---

## 📝 Comandos Útiles

### Docker

```bash
# Ver logs
docker logs -f cdattg-gibse-web

# Reiniciar
docker-compose --profile prod restart

# Estado
docker ps
```

### Nginx

```bash
# Verificar configuración
sudo nginx -t

# Reiniciar
sudo systemctl reload nginx

# Estado
sudo systemctl status nginx
```

### SSL

```bash
# Renovar certificado
sudo certbot renew
```

---

## 📚 Recursos Adicionales

- [Docker Documentation](https://docs.docker.com/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [GitHub Webhooks Documentation](https://docs.github.com/en/developers/webhooks-and-events/webhooks)

