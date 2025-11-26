# 🔒 Seguridad en Producción

Guía de seguridad y mejores prácticas para el despliegue en producción.

## 📋 Tabla de Contenidos

1. [Checklist de Seguridad](#checklist-de-seguridad)
2. [Configuración de Firewall](#configuración-de-firewall)
3. [Protección de Archivos Sensibles](#protección-de-archivos-sensibles)
4. [SSL/HTTPS](#sslhttps)
5. [Actualizaciones del Sistema](#actualizaciones-del-sistema)
6. [Backups](#backups)
7. [Monitoreo](#monitoreo)

---

## ✅ Checklist de Seguridad

Antes de poner el sitio en producción, verifica:

- [ ] `.env` creado manualmente (NO con script)
- [ ] `WEBHOOK_SECRET` configurado y único
- [ ] Permisos de `.env` son `600`
- [ ] `.env` NO está en Git (verificar con `git status`)
- [ ] SSL/HTTPS configurado
- [ ] Firewall configurado (solo puertos necesarios)
- [ ] `ENVIRONMENT=production` configurado en `.env`
- [ ] Nginx bloquea acceso a `/config/` y `/logs/`
- [ ] `GIT_BRANCH=main` configurado (rama de producción)
- [ ] Contraseñas y tokens son seguros y únicos

---

## 🛡️ Configuración de Firewall

### Instalar y Configurar UFW

```bash
# Instalar UFW (si no está instalado)
sudo apt-get install -y ufw

# Permitir solo puertos necesarios
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS

# Habilitar firewall
sudo ufw enable

# Verificar estado
sudo ufw status
```

### Reglas Adicionales (Opcional)

```bash
# Limitar intentos de conexión SSH
sudo ufw limit 22/tcp

# Bloquear todo lo demás por defecto
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

---

## 🔐 Protección de Archivos Sensibles

### Archivo `.env`

```bash
# Permisos restrictivos
chmod 600 .env

# Verificar que no esté en Git
git status
git check-ignore .env  # Debe mostrar .env
```

### Scripts de Configuración

```bash
# Permisos de ejecución solo para propietario
chmod 700 config/*.sh
```

### Directorio de Logs

**⚠️ IMPORTANTE:** El directorio `logs/` debe existir y tener permisos correctos para que el webhook y otros scripts puedan crear archivos de log como `webhook.log` y `update.log`.

```bash
# Crear directorio si no existe
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

### Bloquear Acceso a Carpetas Sensibles

Nginx ya está configurado para bloquear:
- `/config/` - Archivos de configuración
- `/logs/` - Logs de la aplicación

Verifica en `config/nginx.conf`:

```nginx
location ~ ^/(config|docs)/ {
    deny all;
    return 403;
}
```

---

## 🔒 SSL/HTTPS

### Configurar Let's Encrypt

```bash
# Instalar Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com

# Verificar renovación automática
sudo certbot renew --dry-run
```

### Renovación Automática

Let's Encrypt configura automáticamente un cron job para renovar los certificados. Verifica:

```bash
# Ver tareas programadas
sudo systemctl list-timers | grep certbot
```

### Headers de Seguridad (Opcional)

Puedes agregar headers de seguridad en Nginx:

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
```

---

## 🔄 Actualizaciones del Sistema

### Actualizar Sistema Regularmente

```bash
# Actualizar lista de paquetes
sudo apt-get update

# Actualizar paquetes instalados
sudo apt-get upgrade -y

# Limpiar paquetes no necesarios
sudo apt-get autoremove -y
sudo apt-get autoclean
```

### Actualizar Docker

```bash
# Verificar versión actual
docker --version

# Actualizar Docker (si es necesario)
# Sigue las instrucciones oficiales de Docker
```

---

## 💾 Backups

### Backup del Código

```bash
# Crear backup del proyecto
tar -czf backup-$(date +%Y%m%d).tar.gz /var/www/cdattg_gibse

# Backup del archivo .env (importante)
cp /var/www/cdattg_gibse/.env /backup/.env-$(date +%Y%m%d)
```

### Backup Automático (Cron)

Crea un script de backup:

```bash
#!/bin/bash
# /usr/local/bin/backup-gibse.sh

BACKUP_DIR="/backup"
PROJECT_DIR="/var/www/cdattg_gibse"
DATE=$(date +%Y%m%d)

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Backup del proyecto
tar -czf $BACKUP_DIR/gibse-$DATE.tar.gz $PROJECT_DIR

# Backup del .env
cp $PROJECT_DIR/.env $BACKUP_DIR/.env-$DATE

# Eliminar backups antiguos (más de 30 días)
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
find $BACKUP_DIR -name ".env-*" -mtime +30 -delete
```

Agregar a cron:

```bash
# Editar crontab
sudo crontab -e

# Agregar (backup diario a las 2 AM)
0 2 * * * /usr/local/bin/backup-gibse.sh
```

---

## 📊 Monitoreo

### Ver Logs Regularmente

```bash
# Logs del contenedor
docker logs --tail 100 cdattg-gibse-web

# Logs del webhook
tail -100 /var/www/cdattg_gibse/logs/webhook.log

# Logs de Nginx
sudo tail -100 /var/log/nginx/error.log
```

### Monitorear Recursos

```bash
# Uso de CPU y memoria
htop

# Espacio en disco
df -h

# Uso de Docker
docker stats
```

### Alertas (Opcional)

Configura alertas para:
- Espacio en disco bajo
- Contenedor detenido
- Errores en logs
- Certificado SSL próximo a vencer

---

## 🔍 Auditoría de Seguridad

### Verificar Permisos

```bash
# Verificar permisos de archivos sensibles
ls -la /var/www/cdattg_gibse/.env
ls -la /var/www/cdattg_gibse/config/*.sh

# Verificar que .env no esté en Git
cd /var/www/cdattg_gibse
git status
git check-ignore .env
```

### Verificar Configuración

```bash
# Verificar configuración de Nginx
sudo nginx -t

# Verificar configuración del webhook
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

### Escanear Vulnerabilidades (Opcional)

```bash
# Actualizar base de datos de vulnerabilidades
sudo apt-get update

# Escanear paquetes instalados
apt list --upgradable
```

---

## 📚 Recursos Adicionales

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Docker Security Best Practices](https://docs.docker.com/engine/security/)
- [Nginx Security Headers](https://www.nginx.com/blog/http-strict-transport-security-hsts-and-nginx/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)

---

## ⚠️ Recordatorios Importantes

1. **Nunca** subas el archivo `.env` a Git
2. **Nunca** compartas tokens o secretos
3. **Siempre** usa HTTPS en producción
4. **Siempre** mantén el sistema actualizado
5. **Siempre** haz backups regularmente
6. **Siempre** verifica los logs periódicamente

