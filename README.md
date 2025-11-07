# GIBSE - Gestión Integral de la Biodiversidad

Aplicación web PHP para el programa de Tecnología en Gestión Integral de la Biodiversidad y los Servicios Ecosistémicos - SENA

## 📋 Tabla de Contenidos

1. [Descripción del Proyecto](#descripción-del-proyecto)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Configuración Inicial](#configuración-inicial)
4. [Desarrollo Local](#desarrollo-local)
5. [Despliegue en Producción](#despliegue-en-producción)
6. [Configuración DNS](#configuración-dns)
7. [Variables de Entorno](#variables-de-entorno)
8. [Actualización del Sitio](#actualización-del-sitio)
9. [Seguridad](#seguridad)
10. [Solución de Problemas](#solución-de-problemas)
11. [Tecnologías](#tecnologías)

---

## 📖 Descripción del Proyecto

Este proyecto es una aplicación web informativa desarrollada para el programa de **Tecnología en Gestión Integral de la Biodiversidad y los Servicios Ecosistémicos** del SENA. 

El sitio web proporciona información sobre:
- Información general del programa (ID: 222212)
- Centros de formación donde está disponible (41 centros en todo el país)
- Estructura curricular con 6 competencias profesionales
- Información de contacto a nivel nacional

---

## 📁 Estructura del Proyecto

```
cdattg_gibse/
├── assets/                      # Recursos estáticos
│   ├── css/                     # Hojas de estilo CSS
│   │   ├── colors-sena.css      # Paleta de colores SENA
│   │   ├── header-logo.css      # Estilos del header y logo
│   │   ├── responsive.css       # Media queries y diseño responsive
│   │   ├── section.css          # Estilos generales de secciones
│   │   ├── section_1.css        # Estilos de sección 1
│   │   ├── section_2.css        # Estilos de sección 2
│   │   ├── section_3.css        # Estilos de sección 3
│   │   ├── section_4.css        # Estilos de sección 4
│   │   └── style.css            # Estilos principales
│   ├── images/                  # Imágenes del proyecto
│   ├── js/                      # Scripts JavaScript
│   │   ├── acordeon.js          # Funcionalidad de acordeón
│   │   └── main.js              # Script principal
│   └── videos/                  # Videos del proyecto
├── config/                      # Configuraciones del proyecto
│   ├── env-loader.php           # Cargador de variables de entorno
│   ├── nginx.conf               # Configuración de Nginx para producción
│   └── update.sh                # Script para actualizar el sitio
├── logs/                        # Logs de la aplicación (ignorado en Git)
├── docker-compose.yml           # Docker Compose con perfiles (dev/prod)
├── Dockerfile                   # Configuración de la imagen Docker
├── .htaccess                    # Configuración de Apache
├── .gitignore                   # Archivos ignorados por Git
└── index.php                    # Página principal de la aplicación
```

---

## ⚙️ Configuración Inicial

### Requisitos Previos

- Docker y Docker Compose instalados
- Git instalado
- Acceso SSH al servidor (para producción)

### Variables de Entorno

1. **Crea el archivo `.env` desde el ejemplo:**
   ```bash
   cp .env.example .env
   ```

2. **Edita `.env` con tus valores:**
   ```bash
   nano .env
   ```

3. **Protege el archivo:**
   ```bash
   chmod 600 .env
   ```

**⚠️ IMPORTANTE:** El archivo `.env` contiene información confidencial y NO debe subirse a Git.

---

## 💻 Desarrollo Local

### Requisitos

- Docker Desktop (o Docker Engine + Docker Compose)
- Puerto 8080 disponible

### Ejecutar el Proyecto

```bash
# Clonar el repositorio
git clone <url-del-repositorio> cdattg_gibse
cd cdattg_gibse

# Configurar variables de entorno
cp .env.example .env
nano .env  # Configura ENVIRONMENT=development

# Ejecutar con Docker Compose (perfil de desarrollo)
docker-compose --profile dev up -d --build
```

El sitio estará disponible en: `http://localhost:8080`

**💡 Características del modo desarrollo:**
- Puerto: `8080` (accesible desde fuera)
- Volúmenes montados para hot-reload (cambios en archivos se reflejan inmediatamente)
- Logs en tiempo real: `docker logs -f cdattg-gibse-web`

### Detener el Proyecto

```bash
docker-compose --profile dev down
```

---

## 🚀 Despliegue en Producción

### Requisitos Previos

- VPS de Hostinger (o similar) con acceso SSH
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
git clone <url-del-repositorio> cdattg_gibse
cd cdattg_gibse
```

#### Opción B: Usando SCP (desde tu máquina local)

```bash
scp -r . usuario@tu-ip-vps:/var/www/cdattg_gibse
```

### Paso 3: Configurar Variables de Entorno

```bash
cd /var/www/cdattg_gibse
cp .env.example .env
nano .env
```

**Configura al menos:**
```env
DOMAIN=gibse.dataguaviare.com.co
PROJECT_DIR=/var/www/cdattg_gibse
ENVIRONMENT=production
GIT_BRANCH=main
```

**Proteger el archivo:**
```bash
chmod 600 .env
```

### Paso 4: Instalar Docker y Docker Compose

```bash
# Instalar Docker
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    rm get-docker.sh
    exit  # Reiniciar sesión SSH
fi

# Instalar Docker Compose
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
# Instalar Nginx
if ! command -v nginx &> /dev/null; then
    sudo apt-get update
    sudo apt-get install -y nginx
fi

# Copiar configuración de Nginx
DOMAIN="gibse.dataguaviare.com.co"
sudo cp /var/www/cdattg_gibse/config/nginx.conf /etc/nginx/sites-available/$DOMAIN

# Habilitar el sitio
sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/$DOMAIN

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

### Paso 6: Construir y Ejecutar el Contenedor

```bash
cd /var/www/cdattg_gibse

# El script detecta automáticamente el entorno desde .env
./config/update.sh

# O manualmente:
docker-compose --profile prod up -d --build
```

**💡 Características del modo producción:**
- Puerto: `127.0.0.1:8081` (solo localhost, Nginx hace proxy desde puerto 80)
- Sin volúmenes (código dentro de la imagen Docker para mejor rendimiento)
- Código optimizado y sin archivos de desarrollo

### Paso 7: Configurar DNS

Ver sección [Configuración DNS](#configuración-dns) más abajo.

### Paso 8: Configurar SSL (HTTPS)

```bash
# Instalar Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d gibse.dataguaviare.com.co
```

### Paso 9: Verificar el Sitio

Abre en tu navegador:
- HTTP: `http://gibse.dataguaviare.com.co`
- HTTPS: `https://gibse.dataguaviare.com.co`

---

## 🌐 Configuración DNS

### ¿Qué es un Registro A?

Un **Registro A** apunta un dominio o subdominio a una dirección IP. Necesitas apuntar `gibse.dataguaviare.com.co` a la IP de tu VPS.

### Paso 1: Obtener la IP de tu VPS

```bash
curl ifconfig.me
# O desde el panel de Hostinger: VPS → Tu VPS → Ver IP
```

### Paso 2: Configurar el Registro A en Hostinger

1. Inicia sesión en [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Ve a **Dominios** → Selecciona `dataguaviare.com.co`
3. Busca **Zona DNS** o **DNS Zone**
4. Crea o edita un registro tipo **A**:
   - **Nombre/Host:** `gibse` (solo el subdominio, sin el dominio completo)
   - **Tipo:** `A`
   - **Apunta a/Value:** `185.123.45.67` (tu IP real)
   - **TTL:** `3600` o `Auto`

**⚠️ IMPORTANTE:** El campo "Apunta a" NO debe estar vacío.

### Paso 3: Esperar la Propagación DNS

- Tiempo mínimo: 5-10 minutos
- Tiempo típico: 15-30 minutos
- Tiempo máximo: 24-48 horas (raro)

### Paso 4: Verificar que Funciona

```bash
# Windows (PowerShell)
nslookup gibse.dataguaviare.com.co

# Linux/Mac
dig gibse.dataguaviare.com.co
```

Debe mostrar la IP de tu VPS.

---

## 🔐 Variables de Entorno

### Variables Disponibles

#### Configuración del Dominio
```env
DOMAIN=gibse.dataguaviare.com.co
```

#### Configuración del Servidor
```env
PROJECT_DIR=/var/www/cdattg_gibse
NGINX_SITES=/etc/nginx/sites-available
NGINX_ENABLED=/etc/nginx/sites-enabled
```

#### Docker - Configuración del Entorno
```env
# Valores posibles: development | production
ENVIRONMENT=production

# El sistema usa Docker Compose profiles automáticamente:
# - production → perfil "prod" (puerto 127.0.0.1:8081, Nginx hace proxy desde 80, sin volúmenes)
# - development → perfil "dev" (puerto 8080, con volúmenes)
DOCKER_CONTAINER_NAME=cdattg-gibse-web
```

#### Script de Actualización
```env
UPDATE_SCRIPT=/var/www/cdattg_gibse/config/update.sh
```

#### Configuración de Git
```env
GIT_BRANCH=main
```

**💡 Uso para ambientes separados:**
- **Producción:** `GIT_BRANCH=main`
- **Desarrollo:** `GIT_BRANCH=develop`

### Uso en PHP

```php
require_once __DIR__ . '/../config/env-loader.php';

$domain = getEnvVar('DOMAIN', 'localhost');
$projectDir = getEnvVar('PROJECT_DIR', '/var/www/cdattg_gibse');
```

### Uso en Scripts Bash

El script `config/update.sh` carga automáticamente el `.env` si existe.

### Seguridad de Variables de Entorno

**✅ Buenas Prácticas:**
1. **Nunca subas `.env` a Git** (está en `.gitignore`)
2. **Permisos del archivo:** `chmod 600 .env`
3. **No compartas el archivo `.env`**
4. **Usa diferentes `.env` para cada entorno**

---

## 🔄 Actualización del Sitio

### Actualización Manual

Para actualizar el sitio después de hacer cambios:

```bash
# En el VPS
cd /var/www/cdattg_gibse
./config/update.sh
```

Este script:
1. Hace `git pull` de la rama configurada en `GIT_BRANCH`
2. Reconstruye el contenedor Docker
3. Reinicia el servicio
4. Limpia imágenes antiguas

### Flujo de Actualización

```
1. Desarrollas en local
   ↓
2. git add . && git commit -m "Cambios"
   ↓
3. git push origin [rama]
   ↓
4. En el servidor: ./config/update.sh
   ↓
5. git pull de la rama configurada (GIT_BRANCH)
   ↓
6. Docker rebuild
   ↓
7. Contenedor reiniciado
   ↓
8. Sitio actualizado ✅
```

---

## 🔒 Seguridad

### Archivos Confidenciales

#### `.env`
- **Contiene:** Configuraciones sensibles
- **Permisos:** `600` (solo propietario)
- **Git:** ❌ NO debe estar en Git

### Buenas Prácticas de Seguridad

#### 1. Gestión de Archivos Confidenciales
- No uses valores por defecto para configuraciones sensibles
- No compartas configuraciones entre ambientes
- No incluyas secretos en el código

#### 2. Permisos de Archivos
```bash
chmod 600 .env
chmod 700 config/*.sh
```

#### 3. Validación de Entorno
- Verifica que estás en el entorno correcto antes de ejecutar scripts
- No ejecutes scripts de desarrollo en producción

### Checklist de Seguridad para Producción

- [ ] `.env` creado manualmente (NO con script)
- [ ] `GIT_BRANCH` configurado correctamente
- [ ] Permisos de `.env` son `600`
- [ ] `.env` NO está en Git (verificar con `git status`)
- [ ] SSL/HTTPS configurado
- [ ] Firewall configurado (solo puertos necesarios)
- [ ] `ENVIRONMENT=production` configurado en `.env`

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
Configura backups regulares de `/var/www/cdattg_gibse`

---

## 🔧 Comandos Útiles

### Ver logs del contenedor
```bash
docker logs cdattg-gibse-web
docker logs -f cdattg-gibse-web  # Seguir logs en tiempo real
```

### Reiniciar el contenedor
```bash
cd /var/www/cdattg_gibse
docker-compose --profile prod restart  # Producción
docker-compose --profile dev restart   # Desarrollo
```

### Detener el contenedor
```bash
docker-compose --profile prod down  # Producción
docker-compose --profile dev down   # Desarrollo
```

### Ver estado de los contenedores
```bash
docker ps
docker-compose --profile prod ps  # Producción
docker-compose --profile dev ps   # Desarrollo
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

### El dominio no funciona

**Síntomas:** El proyecto funciona en Docker pero el dominio no responde.

**Causa más común:** El registro A en Hostinger está mal configurado o el campo "Apunta a" está vacío.

**Solución:**
1. Verifica el registro A en Hostinger (Panel → Dominios → Zona DNS)
2. Verifica que el campo "Apunta a" NO esté vacío (debe tener la IP de tu VPS)
3. Verifica el DNS: `nslookup gibse.dataguaviare.com.co`
4. Espera 5-15 minutos para la propagación DNS

### El sitio no carga

1. Verifica que el contenedor esté corriendo: `docker ps`
2. Verifica los logs: `docker logs cdattg-gibse-web`
3. Verifica Nginx: `sudo systemctl status nginx`
4. Verifica el DNS: `ping gibse.dataguaviare.com.co`
5. Verifica que `ENVIRONMENT=production` en `.env`

### El sitio muestra "localhost:8080" en producción

**Causa:** La variable `ENVIRONMENT` en `.env` no está configurada como `production`.

**Solución:**
1. Edita `.env`: `nano /var/www/cdattg_gibse/.env`
2. Cambia a: `ENVIRONMENT=production`
3. Reinicia: `./config/update.sh`

### Error de permisos

```bash
sudo chown -R $USER:$USER /var/www/cdattg_gibse
```

### Puerto 80 ocupado

```bash
sudo netstat -tulpn | grep :80
sudo systemctl stop apache2  # Si Apache está corriendo
```

### El contenedor no se actualiza

1. Verifica Git: `git status && git pull origin main`
2. Ejecuta el script: `./config/update.sh`
3. Verifica los logs: `docker logs cdattg-gibse-web`

---

## 🛠️ Tecnologías

### Backend
- **PHP 8.2** - Lenguaje de programación del lado del servidor
- **Apache** - Servidor web dentro del contenedor Docker

### Frontend
- **HTML5** - Estructura semántica
- **CSS3** - Estilos personalizados y responsive design
- **JavaScript (Vanilla)** - Interactividad sin frameworks
- **Bootstrap 5.3.3** - Framework CSS para diseño responsive
- **Bootstrap Icons** - Iconografía
- **Google Fonts (Inter)** - Tipografía

### Infraestructura
- **Docker** - Contenedorización de la aplicación
- **Docker Compose** - Orquestación de contenedores con perfiles
- **Nginx** - Reverse proxy en producción (para SSL y seguridad)
- **Let's Encrypt** - Certificados SSL gratuitos

### Arquitectura

**Desarrollo Local:**
- Apache en Docker (puerto 8080)
- Volúmenes montados para hot-reload

**Producción:**
- Nginx en el host (puerto 80/443) → Reverse proxy
- Apache en Docker (127.0.0.1:8081)
- SSL/HTTPS gestionado por Nginx
- Código dentro de la imagen Docker (sin volúmenes)

Esta separación de responsabilidades es una práctica estándar en la industria.

---

## 📝 Licencia

SENA - Gestión Integral de la Biodiversidad

---

## 📚 Recursos Adicionales

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Docker Security Best Practices](https://docs.docker.com/engine/security/)
- [Nginx Security Headers](https://www.nginx.com/blog/http-strict-transport-security-hsts-and-nginx/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [PHP 8.2 Documentation](https://www.php.net/manual/es/)
