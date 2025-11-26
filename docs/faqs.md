# ❓ Preguntas Frecuentes (FAQs)

Preguntas comunes y soluciones a problemas frecuentes del proyecto GIBSE.

## 📋 Tabla de Contenidos

1. [Problemas de Instalación](#problemas-de-instalación)
2. [Problemas de Despliegue](#problemas-de-despliegue)
3. [Problemas con Docker](#problemas-con-docker)
4. [Problemas con Nginx](#problemas-con-nginx)
5. [Problemas con Webhook](#problemas-con-webhook)
6. [Problemas de DNS](#problemas-de-dns)
7. [Problemas de Permisos](#problemas-de-permisos)
8. [Otros Problemas](#otros-problemas)

---

## 🔧 Problemas de Instalación

### ¿Cómo instalo Docker en mi servidor?

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

Luego reinicia tu sesión SSH.

### ¿Cómo instalo Docker Compose?

```bash
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### ¿Dónde debo clonar el proyecto?

Recomendado: `/var/www/cdattg_gibse`

Puedes usar otra ubicación, pero asegúrate de actualizar `PROJECT_DIR` en `.env`.

---

## 🚀 Problemas de Despliegue

### El sitio no carga después del despliegue

**Solución paso a paso:**

1. **Verifica que el contenedor esté corriendo:**
   ```bash
   docker ps
   ```
   Debe mostrar `cdattg-gibse-web` con estado "Up".

2. **Verifica los logs:**
   ```bash
   docker logs cdattg-gibse-web
   ```

3. **Verifica Nginx:**
   ```bash
   sudo systemctl status nginx
   sudo nginx -t
   ```

4. **Verifica el DNS:**
   ```bash
   ping tu-dominio.com
   nslookup tu-dominio.com
   ```

5. **Verifica que `ENVIRONMENT=production` en `.env`**

### El sitio muestra "localhost:8080" en producción

**Causa:** La variable `ENVIRONMENT` no está configurada como `production`.

**Solución:**
```bash
nano /var/www/cdattg_gibse/.env
# Cambia a: ENVIRONMENT=production
./config/update.sh
```

### El contenedor no se actualiza

**Solución:**

1. Verifica Git:
   ```bash
   git status
   git pull origin main  # Para producción
   # O git pull origin develop  # Para desarrollo
   ```

2. Ejecuta el script de actualización:
   ```bash
   ./config/update.sh
   ```

3. Verifica los logs:
   ```bash
   docker logs cdattg-gibse-web
   ```

---

## 🐳 Problemas con Docker

### Error: "Cannot connect to the Docker daemon"

**Causa:** El usuario actual no tiene permisos para usar Docker.

**Solución:**
```bash
sudo usermod -aG docker $USER
# Reinicia la sesión SSH
exit
# Vuelve a conectarte
```

### Error: "Port 8080 is already allocated"

**Causa:** Otro servicio está usando el puerto 8080.

**Solución:**
```bash
# Ver qué está usando el puerto
sudo netstat -tulpn | grep :8080

# Detener el servicio o cambiar el puerto en docker-compose.yml
```

### El contenedor se detiene inmediatamente

**Solución:**

1. Verifica los logs:
   ```bash
   docker logs cdattg-gibse-web
   ```

2. Verifica que el Dockerfile esté correcto

3. Reconstruye la imagen:
   ```bash
   docker-compose --profile prod build --no-cache
   ```

### Error: "www-data cannot execute docker"

**Causa:** El usuario `www-data` no está en el grupo `docker`.

**Solución:**
```bash
sudo usermod -aG docker www-data
sudo systemctl restart apache2  # O php8.4-fpm
```

O ejecuta el script de verificación:
```bash
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

---

## 🌐 Problemas con Nginx

### Error: "nginx: [emerg] bind() to 0.0.0.0:80 failed"

**Causa:** El puerto 80 está ocupado por otro servicio.

**Solución:**
```bash
# Ver qué está usando el puerto 80
sudo netstat -tulpn | grep :80

# Detener Apache si está corriendo
sudo systemctl stop apache2
```

### Error: "nginx: configuration file test failed"

**Causa:** Error de sintaxis en la configuración de Nginx.

**Solución:**
```bash
# Verificar configuración
sudo nginx -t

# Revisar el archivo de configuración
sudo nano /etc/nginx/sites-available/tu-dominio.com
```

### Nginx no redirige al contenedor Docker

**Solución:**

1. Verifica que el contenedor esté corriendo en `127.0.0.1:8081`:
   ```bash
   docker ps
   curl http://127.0.0.1:8081
   ```

2. Verifica la configuración de Nginx:
   ```bash
   sudo nginx -t
   sudo cat /etc/nginx/sites-available/tu-dominio.com
   ```

3. Reinicia Nginx:
   ```bash
   sudo systemctl reload nginx
   ```

---

## 🔄 Problemas con Webhook

### El webhook no se ejecuta

**Solución paso a paso:**

1. **Verifica la configuración:**
   ```bash
   sudo /var/www/cdattg_gibse/config/webhook-check.sh
   ```

2. **Verifica los logs:**
   ```bash
   tail -f /var/www/cdattg_gibse/logs/webhook.log
   ```

3. **Verifica que el dominio sea accesible desde internet:**
   ```bash
   curl https://tu-dominio.com/webhook.php
   ```

4. **Verifica en GitHub:**
   - Ve a Settings → Webhooks
   - Revisa los "Recent Deliveries"
   - Verifica el código de respuesta

### Error 403 Forbidden en webhook

**Causa:** El token secreto no coincide entre GitHub y `.env`.

**Solución:**

1. Verifica el token en `.env`:
   ```bash
   grep WEBHOOK_SECRET /var/www/cdattg_gibse/.env
   ```

2. Verifica el token en GitHub:
   - Settings → Webhooks → Tu webhook → Edit
   - Compara el "Secret" con el de `.env`

3. Si no coinciden, actualiza uno de los dos para que coincidan.

### Error: "Script no se ejecuta"

**Causa:** Permisos insuficientes o Docker no accesible.

**Solución:**

1. Verifica permisos del script:
   ```bash
   ls -l /var/www/cdattg_gibse/config/update.sh
   chmod +x /var/www/cdattg_gibse/config/update.sh
   ```

2. Verifica permisos de Docker:
   ```bash
   sudo -u www-data docker ps
   ```

3. Si falla, ejecuta:
   ```bash
   sudo usermod -aG docker www-data
   sudo systemctl restart apache2
   ```

### Error: "exec() disabled"

**Causa:** Algunos servidores deshabilitan `exec()` por seguridad.

**Solución:** El webhook intenta usar `proc_open()` como alternativa. Si ambas están deshabilitadas, contacta a tu proveedor de hosting.

---

## 🌍 Problemas de DNS

### El dominio no funciona

**Síntomas:** El proyecto funciona en Docker pero el dominio no responde.

**Solución:**

1. **Verifica el registro A en tu proveedor DNS:**
   - Tipo: A
   - Nombre: subdominio (ej: `app`)
   - Apunta a: IP de tu VPS
   - **⚠️ IMPORTANTE:** El campo "Apunta a" NO debe estar vacío

2. **Verifica el DNS:**
   ```bash
   nslookup tu-dominio.com
   dig tu-dominio.com
   ```
   Debe mostrar la IP de tu VPS.

3. **Espera la propagación DNS:**
   - Tiempo mínimo: 5-10 minutos
   - Tiempo típico: 15-30 minutos
   - Tiempo máximo: 24-48 horas (raro)

### El DNS apunta a la IP correcta pero el sitio no carga

**Solución:**

1. Verifica que Nginx esté corriendo:
   ```bash
   sudo systemctl status nginx
   ```

2. Verifica que el contenedor esté corriendo:
   ```bash
   docker ps
   ```

3. Verifica el firewall:
   ```bash
   sudo ufw status
   ```

---

## 🔐 Problemas de Permisos

### Error: "Permission denied" al ejecutar scripts

**Solución:**
```bash
chmod +x config/*.sh
```

### Error: "Cannot write to logs directory"

**Síntomas:** El webhook no puede crear archivos de log como `webhook.log` o `update.log`.

**Causa:** El directorio `logs/` no existe o no tiene permisos de escritura para `www-data`.

**Solución:**

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

**Verificar que funciona:**

```bash
# Probar escritura como www-data
sudo -u www-data touch /var/www/cdattg_gibse/logs/test.log
sudo -u www-data rm /var/www/cdattg_gibse/logs/test.log
```

### Error: "Cannot read .env file"

**Solución:**
```bash
chmod 600 /var/www/cdattg_gibse/.env
chown $USER:$USER /var/www/cdattg_gibse/.env
```

---

## 🔧 Otros Problemas

### ¿Cómo actualizo el sistema del servidor?

```bash
sudo apt-get update
sudo apt-get upgrade -y
```

### ¿Cómo renuevo el certificado SSL?

```bash
sudo certbot renew
```

El certificado se renueva automáticamente, pero puedes forzar la renovación.

### ¿Cómo hago backup del proyecto?

```bash
# Backup del código
tar -czf backup-$(date +%Y%m%d).tar.gz /var/www/cdattg_gibse

# Backup de la base de datos (si aplica)
# ... comandos específicos de tu base de datos ...
```

### ¿Cómo cambio el puerto del contenedor?

Edita `docker-compose.yml`:

```yaml
ports:
  - "127.0.0.1:8082:80"  # Cambia 8081 por 8082
```

Y actualiza la configuración de Nginx para apuntar al nuevo puerto.

### ¿Cómo veo los logs en tiempo real?

```bash
# Logs del contenedor
docker logs -f cdattg-gibse-web

# Logs del webhook
tail -f /var/www/cdattg_gibse/logs/webhook.log

# Logs de actualización
tail -f /var/www/cdattg_gibse/logs/update.log

# Logs de Nginx
sudo tail -f /var/log/nginx/error.log
```

### El sitio es muy lento

**Posibles causas y soluciones:**

1. **Imágenes Docker antiguas:**
   ```bash
   docker image prune -f
   ```

2. **Contenedor en modo desarrollo en producción:**
   Verifica que `ENVIRONMENT=production` en `.env`

3. **Falta de recursos en el servidor:**
   Verifica el uso de CPU y memoria

---

## 📞 ¿No encuentras la solución?

1. Revisa los logs detallados
2. Verifica la configuración con `webhook-check.sh`
3. Consulta la documentación técnica
4. Revisa los issues del repositorio (si aplica)

---

## 📚 Recursos Adicionales

- [Instalación para Desarrollo](../dev/instalacion.md)
- [Instalación en Producción](../prod/instalacion.md)
- [Configuración Técnica](../docs/configuracion-tecnica.md)
- [Docker Documentation](https://docs.docker.com/)
- [Docker Engine Troubleshooting](https://docs.docker.com/engine/daemon/troubleshoot/)
- [Docker Desktop Troubleshooting](https://docs.docker.com/desktop/troubleshoot-and-support/troubleshoot/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Nginx Configuration Guide](https://nginx.org/en/docs/beginners_guide.html)

