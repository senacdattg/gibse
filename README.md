# GIBSE - Gestión Integral de la Biodiversidad

Aplicación web PHP para el programa de Tecnología en Gestión Integral de la Biodiversidad y los Servicios Ecosistémicos - SENA

## 📋 Tabla de Contenidos

1. [Estructura del Proyecto](#estructura-del-proyecto)
2. [Configuración Inicial](#configuración-inicial)
3. [Desarrollo Local](#desarrollo-local)
4. [Despliegue en Producción](#despliegue-en-producción)
5. [Configuración DNS](#configuración-dns)
6. [Variables de Entorno](#variables-de-entorno)
7. [Actualización Automática](#actualización-automática)
8. [Seguridad](#seguridad)
9. [Solución de Problemas](#solución-de-problemas)
10. [Tecnologías](#tecnologías)

---

## 📁 Estructura del Proyecto

```
gibse/
├── assets/              # Recursos estáticos (CSS, JS, imágenes, videos)
├── config/              # Configuraciones (env-loader.php)
├── docker/              # Configuraciones de Docker
│   └── nginx.conf       # Configuración de Nginx para producción
├── docker-compose.yml   # Docker Compose (un solo archivo para dev/prod)
├── scripts/             # Scripts de actualización
│   └── update.sh        # Script para actualizar el sitio
├── webhooks/            # Webhooks para actualización automática
│   └── webhook.php      # Endpoint para recibir webhooks de Git
├── Dockerfile           # Configuración de la imagen Docker
├── index.php            # Página principal
└── .htaccess            # Configuración de Apache
```

---

## ⚙️ Configuración Inicial

### Variables de Entorno

1. **Copia el archivo de ejemplo:**
   ```bash
   cp .env.example .env
   ```

2. **Edita `.env` con tus valores:**
   ```bash
   nano .env
   ```

3. **IMPORTANTE:** Genera un secreto seguro para el webhook:
   ```bash
   openssl rand -hex 32
   ```

4. **Protege el archivo:**
   ```bash
   chmod 600 .env
   ```

**⚠️ IMPORTANTE:** El archivo `.env` contiene información confidencial y NO debe subirse a Git.

---

## 💻 Desarrollo Local

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

**💡 Nota:** El archivo `docker-compose.yml` viene configurado por defecto para desarrollo (puerto 8080, con volúmenes para hot-reload).

---

## 🚀 Despliegue en Producción

### Requisitos Previos

- VPS de Hostinger con acceso SSH
- Dominio configurado: `gibse.dataguaviare.com.co`
- Acceso root o usuario con permisos sudo

### Paso 1: Conectarse al VPS

```bash
ssh usuario@tu-ip-vps
```

### Paso 2: Subir los Archivos del Proyecto

#### Opción A: Usando Git (Recomendado)

```bash
# En el VPS
cd /var/www
git clone tu-repositorio.git gibse
cd gibse
```

#### Opción B: Usando SCP (desde tu máquina local)

```bash
scp -r . usuario@tu-ip-vps:/var/www/gibse
```

#### Opción C: Usando SFTP

Usa un cliente como FileZilla o WinSCP para subir todos los archivos.

### Paso 3: Configurar Variables de Entorno

```bash
cd /var/www/gibse
cp .env.example .env
nano .env
```

**Configura al menos:**
- `DOMAIN` - Tu dominio (gibse.dataguaviare.com.co)
- `PROJECT_DIR` - Ruta del proyecto (/var/www/gibse)
- `WEBHOOK_SECRET` - Secreto para el webhook (genera uno: `openssl rand -hex 32`)
- `GIT_BRANCH` - Rama de Git a usar (main para producción, develop para desarrollo)

**Proteger el archivo:**
```bash
chmod 600 .env
```

Ver más detalles en [Variables de Entorno](#variables-de-entorno).

### Paso 4: Instalar Docker y Docker Compose

```bash
# Instalar Docker (si no está instalado)
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    rm get-docker.sh
    # Reinicia la sesión SSH para aplicar los cambios de grupo
    exit
fi

# Instalar Docker Compose (si no está instalado)
if ! command -v docker-compose &> /dev/null; then
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
fi

# Verificar instalación
docker --version
docker-compose --version
```

### Paso 5: Instalar y Configurar Nginx

```bash
# Instalar Nginx (si no está instalado)
if ! command -v nginx &> /dev/null; then
    sudo apt-get update
    sudo apt-get install -y nginx
fi

# Copiar configuración de Nginx
DOMAIN="gibse.dataguaviare.com.co"
sudo cp /var/www/gibse/docker/nginx.conf /etc/nginx/sites-available/$DOMAIN

# Habilitar el sitio
sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/$DOMAIN

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

### Paso 6: Configurar Docker Compose para Producción

**⚠️ IMPORTANTE:** Antes de construir el contenedor, debes configurar `docker-compose.yml` para producción. El archivo viene configurado por defecto para desarrollo (puerto 8080, con volúmenes).

```bash
cd /var/www/gibse
nano docker-compose.yml
```

**Modifica las siguientes líneas:**

1. **Puertos:** Comenta la línea de desarrollo y descomenta la de producción:
   ```yaml
   ports:
     # DESARROLLO: Usa esta línea (puerto 8080)
     # - "${DOCKER_PORT:-8080}:80"  # ← Comenta esta línea
     
     # PRODUCCIÓN: Descomenta esta línea (puerto 80 en localhost)
     - "127.0.0.1:80:80"              # ← Descomenta esta línea
   ```

2. **Volúmenes:** Comenta o elimina la sección de volúmenes:
   ```yaml
   # DESARROLLO: Descomenta para hot-reload de archivos
   # PRODUCCIÓN: Comenta o elimina esta sección (archivos van dentro de la imagen)
   # volumes:
   #   - .:/var/www/html  # ← Comenta esta sección
   ```

**Resultado final para producción:**
```yaml
ports:
  - "127.0.0.1:80:80"

# Sin sección volumes (comentada o eliminada)
```

Guarda el archivo (Ctrl+X, luego Y, luego Enter).

**💡 Nota:** En producción, el contenedor solo escucha en `127.0.0.1:80` (localhost), por lo que Nginx actúa como reverse proxy. Esto es más seguro que exponer el puerto directamente.

### Paso 7: Construir y Ejecutar el Contenedor Docker

```bash
cd /var/www/gibse

# Construir y ejecutar en un solo comando
docker-compose up -d --build

# O por separado:
# docker-compose build
# docker-compose up -d

# Verificar que está corriendo
docker ps | grep gibse-web
```

### Paso 8: Configurar DNS

Ver sección [Configuración DNS](#configuración-dns) más abajo.

### Paso 9: Instalar Certbot (para SSL)

```bash
# Instalar Certbot (si no está instalado)
if ! command -v certbot &> /dev/null; then
    sudo apt-get install -y certbot python3-certbot-nginx
fi
```

### Paso 10: Configurar SSL (HTTPS)

Una vez que el DNS esté configurado:

```bash
sudo certbot --nginx -d gibse.dataguaviare.com.co
```

Sigue las instrucciones para obtener el certificado SSL gratuito de Let's Encrypt.

### Paso 11: Verificar el Sitio

Abre en tu navegador:
- HTTP: `http://gibse.dataguaviare.com.co`
- HTTPS: `https://gibse.dataguaviare.com.co`

---

## 🌐 Configuración DNS

### ¿Qué es un Registro A?

Un **Registro A** (Address) es un tipo de registro DNS que apunta un dominio o subdominio a una dirección IP. En tu caso, necesitas apuntar el subdominio `gibse.dataguaviare.com.co` a la IP de tu VPS de Hostinger.

### Paso 1: Obtener la IP de tu VPS

1. **Desde el panel de Hostinger:**
   - Inicia sesión en [hpanel.hostinger.com](https://hpanel.hostinger.com)
   - Ve a **VPS** → Selecciona tu VPS
   - La IP está visible en el panel principal

2. **Desde el VPS (si ya tienes acceso SSH):**
   ```bash
   curl ifconfig.me
   # O
   hostname -I
   ```

**Ejemplo de IP:** `185.123.45.67` (tu IP será diferente)

### Paso 2: Acceder a la Configuración DNS en Hostinger

#### Opción A: Si el dominio está gestionado en Hostinger

1. Inicia sesión en [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Ve a **Dominios** → Selecciona `dataguaviare.com.co`
3. Busca la sección **Zona DNS** o **DNS Zone**
4. Haz clic en **Gestionar** o **Editar**

#### Opción B: Si el dominio está en otro proveedor

Si `dataguaviare.com.co` está gestionado en otro proveedor (GoDaddy, Namecheap, etc.), debes configurar el DNS allí, no en Hostinger.

### Paso 3: Crear el Registro A para el Subdominio

En la sección de **Zona DNS**, busca el botón **Agregar registro** o **Add Record**.

**Configuración del Registro A:**

| Campo | Valor | Descripción |
|-------|-------|-------------|
| **Tipo** | `A` | Tipo de registro DNS |
| **Nombre/Host** | `gibse` | Solo el subdominio (sin el dominio completo) |
| **Puntos a/Value** | `185.123.45.67` | La IP de tu VPS (reemplaza con tu IP real) |
| **TTL** | `3600` o `Auto` | Tiempo de vida del registro (1 hora) |

**⚠️ IMPORTANTE:**
- **Nombre:** Solo escribe `gibse` (NO escribas `gibse.dataguaviare.com.co`)
- **IP:** Debe ser la IP pública de tu VPS
- **TTL:** Puedes dejar el valor por defecto o usar 3600 segundos

### Paso 4: Guardar y Esperar la Propagación

1. Haz clic en **Guardar** o **Add Record**
2. **Espera la propagación DNS:**
   - Tiempo mínimo: 5-10 minutos
   - Tiempo típico: 15-30 minutos
   - Tiempo máximo: 24-48 horas (raro)

### Paso 5: Verificar que Funciona

```bash
# Windows (PowerShell)
nslookup gibse.dataguaviare.com.co

# Linux/Mac
dig gibse.dataguaviare.com.co
# O
host gibse.dataguaviare.com.co
```

**Resultado esperado:**
```
gibse.dataguaviare.com.co tiene la dirección 185.123.45.67
```

---

## 🔐 Variables de Entorno

Este proyecto utiliza archivos `.env` para gestionar información confidencial y configuraciones que no deben estar en el repositorio Git.

### Variables Disponibles

#### Configuración del Dominio
```env
DOMAIN=gibse.dataguaviare.com.co
```

#### Configuración del Servidor
```env
PROJECT_DIR=/var/www/gibse
NGINX_SITES=/etc/nginx/sites-available
NGINX_ENABLED=/etc/nginx/sites-enabled
```

#### Webhook - Secreto de GitHub/GitLab
```env
WEBHOOK_SECRET=tu_secreto_super_seguro_aqui
```

**⚠️ IMPORTANTE:** Genera un secreto seguro:
```bash
openssl rand -hex 32
```

#### Docker
```env
# Nota: DOCKER_PORT solo se usa en desarrollo
# En producción, edita docker-compose.yml directamente
DOCKER_PORT=8080  # Solo para desarrollo local
DOCKER_CONTAINER_NAME=gibse-web
```

**⚠️ IMPORTANTE para Producción:**
- En producción NO uses `DOCKER_PORT` en `.env`
- En su lugar, edita `docker-compose.yml` manualmente:
  - Comenta la línea de desarrollo: `# - "${DOCKER_PORT:-8080}:80"`
  - Descomenta la línea de producción: `- "127.0.0.1:80:80"`

#### Logs
```env
LOG_FILE=/var/www/gibse/webhook.log
```

#### Script de Actualización
```env
UPDATE_SCRIPT=/var/www/gibse/scripts/update.sh
```

#### Configuración de Git
```env
GIT_BRANCH=main
```

**💡 Uso para ambientes separados:**
- **Producción:** `GIT_BRANCH=main`
- **Desarrollo:** `GIT_BRANCH=develop`
- Puedes tener diferentes `.env` para cada ambiente con diferentes ramas

### Uso en PHP

Las variables de entorno se cargan automáticamente en PHP usando `config/env-loader.php`:

```php
require_once __DIR__ . '/../config/env-loader.php';

$secret = getEnvVar('WEBHOOK_SECRET');
$domain = getEnvVar('DOMAIN', 'localhost');
```

### Uso en Scripts Bash

El script bash (`update.sh`) carga automáticamente el `.env` si existe:

```bash
# El script ya carga el .env automáticamente
# No necesitas hacer nada adicional
./scripts/update.sh
```

### Seguridad de Variables de Entorno

**✅ Buenas Prácticas:**
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

**⚠️ Advertencias:**
- ❌ No incluyas `.env` en commits
- ❌ No compartas el secreto del webhook
- ❌ No uses el mismo secreto en desarrollo y producción
- ✅ Usa diferentes `.env` para cada entorno

---

## 🔄 Actualización Automática

### ¿Cómo funciona?

Cuando haces `git push` a la rama configurada en `.env` (por defecto `main`), el sitio se actualiza automáticamente en el servidor.

**💡 Ambientes separados:** Puedes configurar diferentes ramas para diferentes ambientes:
- **Producción:** `GIT_BRANCH=main` en `.env`
- **Desarrollo:** `GIT_BRANCH=develop` en `.env` (en un servidor diferente o con otro dominio)

### Opción 1: Actualización Manual (Más Simple)

Cada vez que hagas cambios y quieras actualizar el sitio:

```bash
# En el VPS
cd /var/www/gibse
./scripts/update.sh
```

Este script:
1. Hace `git pull` de la rama configurada en `GIT_BRANCH` (por defecto `main`)
2. Reconstruye el contenedor Docker
3. Reinicia el servicio
4. Limpia imágenes antiguas

**💡 Nota:** La rama se configura en el archivo `.env` con la variable `GIT_BRANCH`.

### Opción 2: Actualización Automática con Webhook (Recomendado)

#### Paso 1: Configurar el Webhook en GitHub

1. Ve a tu repositorio en GitHub
2. Settings → Webhooks → Add webhook
3. Configura:
   - **Payload URL**: `https://gibse.dataguaviare.com.co/webhooks/webhook.php`
   - **Content type**: `application/json`
   - **Secret**: Genera un secreto seguro (guárdalo)
   - **Which events**: Solo "Just the push event" (o "Push events" para todas las ramas)
   - **Active**: ✓
4. Click "Add webhook"

**💡 Importante:** El webhook solo se activará para la rama configurada en `GIT_BRANCH` en tu `.env`.

#### Paso 2: Configurar el Secreto en el Servidor

```bash
# Conectarse al VPS
ssh usuario@tu-ip-vps

# Crear el archivo .env si no existe
cd /var/www/gibse
cp .env.example .env

# Editar el archivo .env
nano .env

   # Busca y configura:
   WEBHOOK_SECRET=tu_secreto_generado_en_github
   GIT_BRANCH=main  # O 'develop' para ambiente de desarrollo

# Guardar y salir (Ctrl+X, Y, Enter)

# Proteger el archivo .env
chmod 600 .env
```

**⚠️ IMPORTANTE:** El webhook ahora lee el secreto desde el archivo `.env`, no desde `webhooks/webhook.php`.

#### Paso 3: Dar Permisos

```bash
chmod +x /var/www/gibse/scripts/update.sh
chmod 644 /var/www/gibse/webhooks/webhook.php
```

#### Paso 4: Probar

1. Haz un cambio en tu código
2. Haz commit y push a main:
   ```bash
   git add .
   git commit -m "Test de actualización automática"
   git push origin main
   ```
3. Ve a GitHub → Settings → Webhooks → Tu webhook
4. Deberías ver una entrega (delivery) reciente
5. Verifica los logs:
   ```bash
   tail -f /var/www/gibse/webhook.log
   ```

### Opción 3: Actualización con GitLab

Si usas GitLab, el proceso es similar:

1. Ve a tu proyecto → Settings → Webhooks
2. URL: `https://gibse.dataguaviare.com.co/webhooks/webhook.php`
3. Secret token: (el mismo que configuraste en `.env`)
4. Trigger: Solo "Push events"
5. SSL verification: ✓

### Flujo Completo

```
1. Desarrollas en local
   ↓
2. git add . && git commit -m "Cambios"
   ↓
3. git push origin [rama] (main, develop, etc.)
   ↓
4. GitHub/GitLab envía webhook
   ↓
5. webhooks/webhook.php recibe la petición
   ↓
6. Verifica que la rama coincida con GIT_BRANCH del .env
   ↓
7. scripts/update.sh se ejecuta automáticamente
   ↓
8. git pull de la rama configurada (GIT_BRANCH)
   ↓
9. Docker rebuild
   ↓
10. Contenedor reiniciado
   ↓
11. Sitio actualizado ✅
```

---

## 🔒 Seguridad

### Archivos Confidenciales

#### `.env`
- **Contiene:** Secretos, configuraciones sensibles
- **Permisos:** `600` (solo propietario)
- **Git:** ❌ NO debe estar en Git (está en `.gitignore`)
- **Backup:** ⚠️ No incluir en backups públicos

#### `webhooks/webhook.php`
- **Contiene:** Lógica de webhook (lee secretos desde `.env`)
- **Permisos:** `644` (lectura para todos, escritura solo propietario)
- **Acceso:** Solo debe ser accesible vía HTTPS

### Buenas Prácticas de Seguridad

#### 1. Gestión de Secretos

```bash
# Generar secretos seguros
openssl rand -hex 32

# NUNCA uses:
# - Valores por defecto
# - Secretos compartidos entre ambientes
# - Secretos en el código
```

#### 2. Permisos de Archivos

```bash
# .env debe ser solo lectura/escritura para el propietario
chmod 600 .env

# Scripts ejecutables solo para el propietario
chmod 700 scripts/*.sh

# Archivos PHP con permisos estándar
chmod 644 webhooks/*.php
```

#### 3. Validación de Entorno

- ✅ Verifica que estás en el entorno correcto antes de ejecutar scripts
- ✅ Usa diferentes secretos para desarrollo y producción
- ✅ No ejecutes scripts de desarrollo en producción

#### 4. Logs y Auditoría

```bash
# Los logs del webhook pueden contener información sensible
# Asegúrate de que solo el propietario pueda leerlos
chmod 600 webhook.log
```

### Checklist de Seguridad para Producción

Antes de desplegar en producción:

- [ ] `.env` creado manualmente (NO con script)
- [ ] `WEBHOOK_SECRET` generado con `openssl rand -hex 32`
- [ ] `GIT_BRANCH` configurado correctamente
- [ ] Permisos de `.env` son `600`
- [ ] Propietario de `.env` es el usuario correcto
- [ ] `.env` NO está en Git (verificar con `git status`)
- [ ] SSL/HTTPS configurado
- [ ] Firewall configurado (solo puertos necesarios)
- [ ] Scripts de desarrollo no ejecutables en producción
- [ ] Logs con permisos adecuados
- [ ] `docker-compose.yml` configurado para producción (puerto 80, sin volúmenes)

### Seguridad Adicional

1. **Firewall (UFW)**
```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

2. **Actualizar sistema regularmente**
```bash
sudo apt-get update && sudo apt-get upgrade -y
```

3. **Backups automáticos**
Configura backups regulares de `/var/www/gibse`

### Incidentes de Seguridad

Si sospechas que un secreto ha sido comprometido:

1. **Genera un nuevo secreto inmediatamente:**
   ```bash
   openssl rand -hex 32
   ```

2. **Actualiza el .env:**
   ```bash
   nano .env
   # Cambia WEBHOOK_SECRET
   ```

3. **Actualiza el webhook en GitHub/GitLab:**
   - Ve a Settings → Webhooks
   - Edita el webhook
   - Actualiza el Secret

4. **Revisa los logs:**
   ```bash
   tail -100 webhook.log
   ```

5. **Considera rotar todos los secretos** si hay evidencia de compromiso

---

## 🔧 Comandos Útiles

### Ver logs del contenedor
```bash
docker logs gibse-web
docker logs -f gibse-web  # Seguir logs en tiempo real
```

### Reiniciar el contenedor
```bash
cd /var/www/gibse
docker-compose restart
```

### Detener el contenedor
```bash
cd /var/www/gibse
docker-compose down
```

### Ver estado de los contenedores
```bash
docker ps
docker-compose ps
```

### Ver logs del webhook
```bash
tail -f /var/www/gibse/webhook.log
```

### Verificar DNS
```bash
nslookup gibse.dataguaviare.com.co
```

### Renovar certificado SSL
```bash
sudo certbot renew
```

---

## 🐛 Solución de Problemas

### El sitio no carga

1. Verifica que el contenedor esté corriendo: `docker ps`
2. Verifica los logs: `docker logs gibse-web`
3. Verifica Nginx: `sudo systemctl status nginx`
4. Verifica el DNS: `ping gibse.dataguaviare.com.co`
5. **Verifica que `docker-compose.yml` esté configurado para producción:**
   - El puerto debe ser `127.0.0.1:80:80` (NO `8080:80`)
   - Los volúmenes deben estar comentados
   - Ver [Paso 6 del Despliegue](#paso-6-configurar-docker-compose-para-producción)

### El sitio muestra "localhost:8080" en producción

**Causa:** El archivo `docker-compose.yml` no está configurado para producción.

**Solución:**
1. Edita `docker-compose.yml`:
   ```bash
   nano docker-compose.yml
   ```
2. Asegúrate de que los puertos estén así:
   ```yaml
   ports:
     # - "${DOCKER_PORT:-8080}:80"  # ← Comentado
     - "127.0.0.1:80:80"              # ← Activo
   ```
3. Reinicia el contenedor:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

### Error de permisos

```bash
sudo chown -R $USER:$USER /var/www/gibse
```

### Puerto 80 ocupado

```bash
sudo netstat -tulpn | grep :80
sudo systemctl stop apache2  # Si Apache está corriendo
```

### El DNS no resuelve después de 30 minutos

1. **Verifica que el registro esté correcto:**
   - Nombre: Solo `gibse` (sin el dominio)
   - IP: Correcta y sin espacios
   - Tipo: `A` (no AAAA, CNAME, etc.)

2. **Limpia la caché DNS:**
   ```bash
   # Windows
   ipconfig /flushdns
   
   # Linux
   sudo systemd-resolve --flush-caches
   
   # Mac
   sudo dscacheutil -flushcache
   ```

3. **Verifica desde otro lugar:**
   - Usa [whatsmydns.net](https://www.whatsmydns.net)
   - Busca `gibse.dataguaviare.com.co`
   - Verifica que apunte a tu IP

### El webhook no se ejecuta

1. **Verifica que el secreto coincida en .env:**
   ```bash
   # Verificar que el secreto está configurado en .env
   grep WEBHOOK_SECRET /var/www/gibse/.env
   
   # Verificar que el .env se carga correctamente
   cd /var/www/gibse
   php -r "require 'config/env-loader.php'; echo getEnvVar('WEBHOOK_SECRET') ? 'OK' : 'FALTA';"
   ```

2. **Verifica los logs:**
   ```bash
   tail -20 /var/www/gibse/webhook.log
   ```

3. **Verifica que el script tenga permisos:**
   ```bash
   ls -la /var/www/gibse/scripts/update.sh
   # Debe mostrar: -rwxr-xr-x
   ```

4. **Prueba el webhook manualmente:**
   ```bash
   curl -X POST https://gibse.dataguaviare.com.co/webhooks/webhook.php
   ```

### El contenedor no se actualiza

1. **Verifica que Git esté funcionando:**
   ```bash
   cd /var/www/gibse
   git status
   git pull origin main
   ```

2. **Ejecuta el script manualmente:**
   ```bash
   cd /var/www/gibse
   ./scripts/update.sh
   ```

3. **Verifica los logs de Docker:**
   ```bash
   docker logs gibse-web
   ```

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

---

## 🛠️ Tecnologías

- **PHP 8.2** - Lenguaje de programación
- **Apache** - Servidor web
- **Docker** - Contenedorización
- **Docker Compose** - Orquestación de contenedores
- **Nginx** - Reverse proxy en producción
- **Let's Encrypt** - Certificados SSL gratuitos

---

## 📝 Licencia

SENA - Gestión Integral de la Biodiversidad

---

## 📚 Recursos Adicionales

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Docker Security Best Practices](https://docs.docker.com/engine/security/)
- [Nginx Security Headers](https://www.nginx.com/blog/http-strict-transport-security-hsts-and-nginx/)
