# 🚀 Instalación en Producción

Guía completa para desplegar el proyecto GIBSE en un servidor de producción.

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Preparación del Servidor](#preparación-del-servidor)
3. [Instalación de Dependencias](#instalación-de-dependencias)
4. [Configuración del Proyecto](#configuración-del-proyecto)
5. [Configuración de Permisos](#configuración-de-permisos)
6. [Configuración de Nginx](#configuración-de-nginx)
7. [Desplegar la Aplicación](#desplegar-la-aplicación)
8. [Configuración de DNS](#configuración-de-dns)
9. [Configuración de SSL](#configuración-de-ssl)
10. [Verificación](#verificación)
11. [Configuración del Webhook](#configuración-del-webhook)

---

## 🔧 Requisitos Previos

- **VPS** o servidor con acceso SSH
- **Dominio** configurado (recomendado)
- Acceso **root** o usuario con permisos **sudo**
- Al menos **1GB RAM** y **10GB** de espacio en disco

---

## 🖥️ Preparación del Servidor

### Conectarse al Servidor

```bash
ssh usuario@tu-ip-servidor
```

### Actualizar el Sistema

```bash
sudo apt-get update
sudo apt-get upgrade -y
```

---

## 📦 Instalación de Dependencias

### Instalar Docker

```bash
# Instalar Docker
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    rm get-docker.sh
    # Reiniciar sesión SSH para aplicar cambios
    exit
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

### Instalar Nginx

```bash
if ! command -v nginx &> /dev/null; then
    sudo apt-get update
    sudo apt-get install -y nginx
fi

# Verificar instalación
nginx -v
```

---

## 📥 Configuración del Proyecto

### Paso 1: Clonar el Repositorio

```bash
cd /var/www
git clone <url-del-repositorio> cdattg_gibse
cd cdattg_gibse

# Cambiar a la rama de producción
git checkout main
```

### Paso 2: Configurar Variables de Entorno

```bash
cp .env.example .env
nano .env
```

**Configuración mínima requerida:**

```env
# Reemplaza con tu dominio real
DOMAIN=tu-dominio.com

# Ruta donde está el proyecto en el servidor
PROJECT_DIR=/var/www/cdattg_gibse

# Entorno: production
ENVIRONMENT=production

# Rama de Git para despliegue
GIT_BRANCH=main  # ⚠️ IMPORTANTE: Producción usa rama 'main'

# Token secreto para webhook (generar con: openssl rand -hex 32)
WEBHOOK_SECRET=tu_token_secreto_aqui
```

**Proteger el archivo:**

```bash
chmod 600 .env
```

---

## ⚙️ Configuración de Permisos

### Paso 1: Configurar Permisos del Directorio de Logs

**⚠️ IMPORTANTE:** El directorio `logs/` debe existir y tener permisos correctos para que el webhook y otros scripts puedan crear archivos de log como `webhook.log` y `update.log`.

Si no configuras estos permisos, el webhook no podrá escribir logs y fallará silenciosamente.

```bash
# Crear directorio de logs si no existe
mkdir -p /var/www/cdattg_gibse/logs

# Configurar permisos (www-data debe poder escribir)
sudo chown -R www-data:www-data /var/www/cdattg_gibse/logs
sudo chmod 755 /var/www/cdattg_gibse/logs

# Verificar permisos
ls -la /var/www/cdattg_gibse/logs
```

**O usar el script de verificación (recomendado):**

```bash
# El script crea y configura automáticamente el directorio logs/
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

Este script también verifica y configura:
- ✅ Permisos del directorio `logs/` (crea si no existe)
- ✅ Permisos del script `update.sh`
- ✅ Acceso de Docker para `www-data`
- ✅ Funciones PHP necesarias

### Paso 2: Verificar Permisos de Docker

El usuario `www-data` (que ejecuta PHP) necesita permisos para ejecutar Docker:

```bash
# Agregar www-data al grupo docker
sudo usermod -aG docker www-data

# Reiniciar servicio web para aplicar cambios
sudo systemctl restart apache2  # O php8.4-fpm si usas PHP-FPM

# Verificar que funciona
sudo -u www-data docker ps
```

**O usar el script de verificación (recomendado):**

El script `webhook-check.sh` también configura esto automáticamente cuando se ejecuta con root.

---

**⚠️ IMPORTANTE:** El directorio `logs/` debe existir y tener permisos correctos para que el webhook y otros scripts puedan crear archivos de log.

```bash
# Crear directorio de logs si no existe
mkdir -p /var/www/cdattg_gibse/logs

# Configurar permisos (www-data debe poder escribir)
sudo chown -R www-data:www-data /var/www/cdattg_gibse/logs
sudo chmod 755 /var/www/cdattg_gibse/logs

# Verificar permisos
ls -la /var/www/cdattg_gibse/logs
```

**O usar el script de verificación (recomendado):**

```bash
# El script crea y configura automáticamente el directorio logs/
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

Este script también verifica y configura:
- Permisos del directorio `logs/`
- Permisos del script `update.sh`
- Acceso de Docker para `www-data`
- Funciones PHP necesarias

---

## 🌐 Configuración de Nginx

### Paso 1: Copiar Configuración

```bash
# Editar la plantilla de configuración
sudo nano config/nginx.conf
# Reemplaza 'tu-dominio.com' con tu dominio real

# Copiar a Nginx
DOMAIN="tu-dominio.com"  # Reemplaza con tu dominio
sudo cp config/nginx.conf /etc/nginx/sites-available/$DOMAIN

# Editar el archivo copiado para ajustar el dominio
sudo nano /etc/nginx/sites-available/$DOMAIN
```

### Paso 2: Habilitar el Sitio

```bash
# Habilitar el sitio
sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/$DOMAIN

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

---

## 🚀 Desplegar la Aplicación

```bash
cd /var/www/cdattg_gibse

# El script detecta automáticamente el entorno desde .env
./config/update.sh
```

Este script:
1. Hace `git pull` de la rama `main`
2. Reconstruye el contenedor Docker
3. Reinicia el servicio
4. Limpia imágenes antiguas

**💡 Características del modo producción:**
- Puerto: `127.0.0.1:8081` (solo localhost, Nginx hace proxy desde puerto 80)
- Sin volúmenes (código dentro de la imagen Docker para mejor rendimiento)
- Código optimizado

---

## 🌍 Configuración de DNS

### Paso 1: Obtener la IP del Servidor

```bash
curl ifconfig.me
```

### Paso 2: Configurar Registro A

En tu proveedor DNS, crea o edita un registro tipo **A**:

- **Tipo:** A
- **Nombre/Host:** `subdominio` (ej: `app` para `app.tudominio.com`)
- **Apunta a/Value:** IP de tu VPS
- **TTL:** `3600` o `Auto`

**⚠️ IMPORTANTE:** El campo "Apunta a" NO debe estar vacío.

### Paso 3: Esperar Propagación DNS

- Tiempo mínimo: 5-10 minutos
- Tiempo típico: 15-30 minutos
- Tiempo máximo: 24-48 horas (raro)

### Paso 4: Verificar DNS

```bash
nslookup tu-dominio.com
# Debe mostrar la IP de tu VPS
```

---

## 🔒 Configuración de SSL (HTTPS)

### Paso 1: Instalar Certbot

```bash
sudo apt-get install -y certbot python3-certbot-nginx
```

### Paso 2: Obtener Certificado SSL

```bash
sudo certbot --nginx -d tu-dominio.com
```

Sigue las instrucciones del asistente. El certificado se renovará automáticamente.

### Paso 3: Verificar Renovación Automática

```bash
# Probar renovación (no aplica cambios)
sudo certbot renew --dry-run
```

---

## ✅ Verificación

### Verificar el Sitio

Abre en tu navegador:
- HTTP: `http://tu-dominio.com`
- HTTPS: `https://tu-dominio.com`

### Verificar Servicios

```bash
# Ver estado del contenedor
docker ps

# Ver logs
docker logs cdattg-gibse-web

# Verificar Nginx
sudo systemctl status nginx

# Verificar DNS
ping tu-dominio.com
```

---

## 🔄 Configuración del Webhook

Para configurar la actualización automática con webhook de GitHub, consulta:

- [Configuración del Webhook](../configuracion-tecnica.md#actualización-automática-con-webhook)

### Verificación Rápida

```bash
# Verificar y configurar permisos automáticamente
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

---

## 📝 Próximos Pasos

- [Configuración Técnica](../configuracion-tecnica.md) - Configuración detallada
- [Preguntas Frecuentes](../faqs.md) - Solución de problemas comunes
- [Seguridad](../configuracion-tecnica.md#seguridad) - Mejores prácticas de seguridad

---

## 🔗 Enlaces Útiles

- [Docker Documentation](https://docs.docker.com/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)

