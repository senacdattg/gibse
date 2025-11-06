# Guía de Despliegue - GIBSE en Hostinger VPS

## Requisitos Previos

- VPS de Hostinger con acceso SSH
- Dominio configurado: `gibse.dataguaviare.com.co`
- Acceso root o usuario con permisos sudo

## Paso 1: Conectarse al VPS

```bash
ssh usuario@tu-ip-vps
```

## Paso 2: Subir los Archivos del Proyecto

### Opción A: Usando SCP (desde tu máquina local)

```bash
scp -r . usuario@tu-ip-vps:/var/www/gibse
```

### Opción B: Usando Git (recomendado)

```bash
# En el VPS
cd /var/www
git clone tu-repositorio.git gibse
cd gibse
```

### Opción C: Usando SFTP

Usa un cliente como FileZilla o WinSCP para subir todos los archivos.

## Paso 3: Configurar Variables de Entorno

**📖 Guía detallada:** Ver [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md)

Antes de ejecutar el despliegue, configura el archivo `.env`:

```bash
cd /var/www/gibse
cp .env.example .env
nano .env
```

Configura al menos:
- `DOMAIN` - Tu dominio (gibse.dataguaviare.com.co)
- `PROJECT_DIR` - Ruta del proyecto (/var/www/gibse)
- `WEBHOOK_SECRET` - Secreto para el webhook (genera uno: `openssl rand -hex 32`)
- `GIT_BRANCH` - Rama de Git a usar (main para producción, develop para desarrollo)

## Paso 4: Instalar Docker y Docker Compose

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

## Paso 5: Instalar y Configurar Nginx

```bash
# Instalar Nginx (si no está instalado)
if ! command -v nginx &> /dev/null; then
    sudo apt-get update
    sudo apt-get install -y nginx
fi

# Copiar configuración de Nginx
DOMAIN="gibse.dataguaviare.com.co"  # O desde .env
sudo cp /var/www/gibse/docker/nginx.conf /etc/nginx/sites-available/$DOMAIN

# Habilitar el sitio
sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/$DOMAIN

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

## Paso 6: Construir y Ejecutar el Contenedor Docker

### Opción A: Usando Docker Compose (Recomendado)

```bash
cd /var/www/gibse

# Construir y ejecutar en un solo comando
docker-compose -f docker/docker-compose.prod.yml up -d --build

# O por separado:
# docker-compose -f docker/docker-compose.prod.yml build
# docker-compose -f docker/docker-compose.prod.yml up -d

# Verificar que está corriendo
docker ps | grep gibse-web
```

### Opción B: Usando Docker directamente

```bash
cd /var/www/gibse

# Construir la imagen
docker build -t gibse-app .

# Ejecutar el contenedor
docker run -d \
  --name gibse-web \
  -p 127.0.0.1:8080:80 \
  --restart unless-stopped \
  gibse-app

# Verificar que está corriendo
docker ps | grep gibse-web
```

**💡 Recomendación:** Usa Docker Compose (Opción A) porque es más simple y maneja la configuración automáticamente.

## Paso 7: Configurar DNS

**📖 Guía detallada:** Ver [CONFIGURACION_DNS_HOSTINGER.md](CONFIGURACION_DNS_HOSTINGER.md)

### Resumen rápido:

En el panel de Hostinger (Dominios → dataguaviare.com.co → Zona DNS), crea un **Registro A**:

- **Tipo:** `A`
- **Nombre:** `gibse` (solo el subdominio, sin el dominio completo)
- **Puntos a/Value:** `[IP de tu VPS]` (ejemplo: 185.123.45.67)
- **TTL:** `3600` o `Auto`

**⚠️ IMPORTANTE:** 
- Solo escribe `gibse` en el campo Nombre (NO `gibse.dataguaviare.com.co`)
- Usa la IP pública de tu VPS de Hostinger

Espera 15-30 minutos para que el DNS se propague. Verifica con:
```bash
nslookup gibse.dataguaviare.com.co
```

## Paso 8: Instalar Certbot (para SSL)

```bash
# Instalar Certbot (si no está instalado)
if ! command -v certbot &> /dev/null; then
    sudo apt-get install -y certbot python3-certbot-nginx
fi
```

## Paso 9: Configurar SSL (HTTPS)

Una vez que el DNS esté configurado:

```bash
sudo certbot --nginx -d gibse.dataguaviare.com.co
```

Sigue las instrucciones para obtener el certificado SSL gratuito de Let's Encrypt.

## Paso 10: Verificar el Sitio

Abre en tu navegador:
- HTTP: `http://gibse.dataguaviare.com.co`
- HTTPS: `https://gibse.dataguaviare.com.co`

## Comandos Útiles

### Ver logs del contenedor
```bash
docker logs gibse-web
docker logs -f gibse-web  # Seguir logs en tiempo real
```

### Reiniciar el contenedor
```bash
cd /var/www/gibse
docker-compose -f docker/docker-compose.prod.yml restart
```

### Detener el contenedor
```bash
cd /var/www/gibse
docker-compose -f docker/docker-compose.prod.yml down
```

### Actualizar el sitio (después de cambios)

#### Opción 1: Actualización Manual
```bash
cd /var/www/gibse
chmod +x scripts/update.sh
./scripts/update.sh
```

#### Opción 2: Actualización Automática con Webhook (Recomendado)

1. **Configurar el webhook en tu repositorio Git (GitHub/GitLab):**
   - Ve a Settings → Webhooks → Add webhook
   - URL: `https://gibse.dataguaviare.com.co/webhooks/webhook.php`
   - Content type: `application/json`
   - Secret: (genera un secreto seguro y actualízalo en `webhooks/webhook.php`)
   - Eventos: Solo "Push events"
   - Active: ✓

2. **Configurar el secreto en el servidor:**
   
   **📖 Guía detallada:** Ver [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md)
   
   ```bash
   # Crear el archivo .env si no existe
   cd /var/www/gibse
   cp .env.example .env
   
   # Editar el archivo .env
   nano .env
   
   # Busca WEBHOOK_SECRET y configura el secreto que generaste en GitHub
   WEBHOOK_SECRET=tu_secreto_generado_en_github
   
   # Proteger el archivo
   chmod 600 .env
   ```

3. **Dar permisos al script:**
   ```bash
   chmod +x /var/www/gibse/scripts/update.sh
   chmod 644 /var/www/gibse/webhooks/webhook.php
   ```

Ahora, cada vez que hagas push a la rama configurada en `GIT_BRANCH` (por defecto `main`), el sitio se actualizará automáticamente.

#### Opción 3: Actualización Manual Simple
```bash
cd /var/www/gibse
# La rama se toma del .env (GIT_BRANCH), o usa main por defecto
git pull origin ${GIT_BRANCH:-main}
docker-compose -f docker/docker-compose.prod.yml build
docker-compose -f docker/docker-compose.prod.yml up -d
```

**💡 Nota:** La rama se configura en `.env` con `GIT_BRANCH`. Esto permite tener ambientes separados (producción con `main`, desarrollo con `develop`).

**📖 Guía completa:** Ver [ACTUALIZACION_AUTOMATICA.md](ACTUALIZACION_AUTOMATICA.md)

### Ver estado de los contenedores
```bash
docker ps
docker-compose -f docker/docker-compose.prod.yml ps
```

## Solución de Problemas

### El sitio no carga
1. Verifica que el contenedor esté corriendo: `docker ps`
2. Verifica los logs: `docker logs gibse-web`
3. Verifica Nginx: `sudo systemctl status nginx`
4. Verifica el DNS: `ping gibse.dataguaviare.com.co`

### Error de permisos
```bash
sudo chown -R $USER:$USER /var/www/gibse
```

### Puerto 80 ocupado
```bash
sudo netstat -tulpn | grep :80
sudo systemctl stop apache2  # Si Apache está corriendo
```

### Renovar certificado SSL
```bash
sudo certbot renew
```

## Estructura de Archivos en el VPS

```
/var/www/gibse/
├── Dockerfile
├── docker-compose.yml
├── .env                    # Archivo de configuración (NO en Git)
├── .env.example            # Plantilla de configuración
├── .htaccess
├── index.php
├── config/
│   └── env-loader.php      # Cargador de variables de entorno
├── docker/
│   ├── docker-compose.prod.yml
│   └── nginx.conf
├── scripts/
│   └── update.sh
├── webhooks/
│   └── webhook.php
├── docs/
│   ├── GUIA_DESPLIEGUE.md
│   ├── ACTUALIZACION_AUTOMATICA.md
│   ├── CONFIGURACION_DNS_HOSTINGER.md
│   └── CONFIGURACION_ENV.md
└── assets/
    ├── css/
    ├── js/
    ├── images/
    └── videos/
```

## Seguridad Adicional

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

