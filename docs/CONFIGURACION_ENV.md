# Configuración de Variables de Entorno

Este proyecto utiliza archivos `.env` para gestionar información confidencial y configuraciones que no deben estar en el repositorio Git.

## Configuración Inicial

### Paso 1: Crear el archivo .env

```bash
# En el directorio raíz del proyecto
cp .env.example .env
```

**⚠️ IMPORTANTE:** 
- El archivo `.env.example` ya está en el repositorio
- Simplemente cópialo a `.env` y edita los valores
- Nunca uses los valores por defecto en producción

### Paso 2: Editar el archivo .env

Abre el archivo `.env` y completa los valores según tu entorno:

```bash
nano .env
```

**⚠️ IMPORTANTE:** El archivo `.env` contiene información confidencial y NO debe subirse a Git.

## Variables Disponibles

### Configuración del Dominio
```env
DOMAIN=gibse.dataguaviare.com.co
```

### Configuración del Servidor
```env
PROJECT_DIR=/var/www/gibse
NGINX_SITES=/etc/nginx/sites-available
NGINX_ENABLED=/etc/nginx/sites-enabled
```

### Webhook - Secreto de GitHub/GitLab
```env
WEBHOOK_SECRET=tu_secreto_super_seguro_aqui
```

**⚠️ IMPORTANTE**: Genera un secreto seguro:
```bash
openssl rand -hex 32
```

### Docker
```env
DOCKER_COMPOSE_FILE=docker/docker-compose.prod.yml
DOCKER_CONTAINER_NAME=gibse-web
DOCKER_PORT=8080
```

### Logs
```env
LOG_FILE=/var/www/gibse/webhook.log
```

### Script de Actualización
```env
UPDATE_SCRIPT=/var/www/gibse/scripts/update.sh
```

### Configuración de Git
```env
GIT_BRANCH=main
```

**💡 Uso para ambientes separados:**
- **Producción:** `GIT_BRANCH=main`
- **Desarrollo:** `GIT_BRANCH=develop`
- Puedes tener diferentes `.env` para cada ambiente con diferentes ramas

## Uso en PHP

Las variables de entorno se cargan automáticamente en PHP usando `config/env-loader.php`:

```php
require_once __DIR__ . '/../config/env-loader.php';

$secret = getEnvVar('WEBHOOK_SECRET');
$domain = getEnvVar('DOMAIN', 'localhost');
```

## Uso en Scripts Bash

El script bash (`update.sh`) carga automáticamente el `.env` si existe:

```bash
# El script ya carga el .env automáticamente
# No necesitas hacer nada adicional
./scripts/update.sh
```

Si necesitas cargar el `.env` manualmente en un script:

```bash
if [ -f .env ]; then
    set -a
    source .env
    set +a
fi
```

## Seguridad

### ✅ Buenas Prácticas

1. **Nunca subas `.env` a Git**
   - El archivo `.env` está en `.gitignore`
   - Solo `.env.example` está en el repositorio

2. **Usa secretos seguros**
   ```bash
   # Generar un secreto seguro
   openssl rand -hex 32
   ```

3. **Permisos del archivo .env**
   ```bash
   chmod 600 .env  # Solo lectura/escritura para el propietario
   ```

4. **No compartas el archivo .env**
   - Comparte solo `.env.example`
   - Cada entorno debe tener su propio `.env`

### ⚠️ Advertencias

- ❌ No incluyas `.env` en commits
- ❌ No compartas el secreto del webhook
- ❌ No uses el mismo secreto en desarrollo y producción
- ✅ Usa diferentes `.env` para cada entorno

## Configuración en Producción

### En el VPS (Producción):

1. **Crear el archivo .env**
   ```bash
   cd /var/www/gibse
   cp .env.example .env
   nano .env
   ```

2. **Configurar los valores**
   - Ajusta `PROJECT_DIR` si es necesario (por defecto: `/var/www/gibse`)
   - Genera y configura `WEBHOOK_SECRET`: `openssl rand -hex 32`
   - Verifica `DOMAIN` (debe ser: `gibse.dataguaviare.com.co`)
   - Ajusta otras variables según tu entorno

3. **Proteger el archivo**
   ```bash
   chmod 600 .env
   chown $USER:$USER .env
   ```

4. **Verificar que funciona**
   ```bash
   # Probar carga de variables en PHP
   php -r "require 'config/env-loader.php'; echo getEnvVar('DOMAIN');"
   
   # Debería mostrar: gibse.dataguaviare.com.co
   ```

**📖 Relacionado:** 
- Para configurar el webhook, ver [ACTUALIZACION_AUTOMATICA.md](ACTUALIZACION_AUTOMATICA.md)
- Para el despliegue completo, ver [GUIA_DESPLIEGUE.md](GUIA_DESPLIEGUE.md)

## Solución de Problemas

### El archivo .env no se carga

1. **Verifica que existe:**
   ```bash
   ls -la .env
   ```

2. **Verifica permisos:**
   ```bash
   chmod 600 .env
   ```

3. **Verifica la ruta:**
   - El `.env` debe estar en el directorio raíz del proyecto
   - La misma ubicación que `.env.example`

### Variables no se cargan en PHP

1. **Verifica que el loader se incluye:**
   ```php
   require_once __DIR__ . '/../config/env-loader.php';
   ```

2. **Verifica la ruta del .env:**
   ```php
   // El loader busca en: __DIR__ . '/../.env'
   // Ajusta si tu estructura es diferente
   ```

### Variables no se cargan en Bash

1. **Verifica que el .env existe:**
   ```bash
   ls -la .env
   ```

2. **Verifica la sintaxis del .env:**
   - Sin espacios alrededor del `=`
   - Sin comillas innecesarias
   - Una variable por línea

## Ejemplo Completo

### .env.example (en el repositorio)
```env
DOMAIN=ejemplo.com
WEBHOOK_SECRET=GENERA_UNO_SEGURO
```

### .env Producción (NO en Git)
```env
DOMAIN=gibse.dataguaviare.com.co
WEBHOOK_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
GIT_BRANCH=main
```

### .env Desarrollo (NO en Git)
```env
DOMAIN=dev.gibse.dataguaviare.com.co
WEBHOOK_SECRET=otro_secreto_diferente_aqui
GIT_BRANCH=develop
```

## Migración desde Configuración Hardcodeada

Si ya tienes valores hardcodeados en los archivos:

1. **Identifica los valores confidenciales:**
   - Secretos
   - Rutas específicas del servidor
   - Configuraciones sensibles

2. **Mueve a .env:**
   - Crea las variables en `.env.example`
   - Actualiza el código para usar `getEnvVar()`

3. **Prueba:**
   - Verifica que funciona sin el valor hardcodeado
   - Confirma que el `.env` se carga correctamente

