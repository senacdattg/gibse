# 🔄 Configuración del Webhook en Producción

Guía completa para configurar el webhook de GitHub que permite actualización automática del sitio en producción.

## 📋 Tabla de Contenidos

1. [¿Qué es el Webhook?](#qué-es-el-webhook)
2. [Configuración Inicial](#configuración-inicial)
3. [Configuración en GitHub](#configuración-en-github)
4. [Verificación](#verificación)
5. [Troubleshooting](#troubleshooting)

---

## 🔍 ¿Qué es el Webhook?

El webhook permite que GitHub notifique automáticamente al servidor cuando hay cambios en el repositorio, ejecutando el script de actualización sin intervención manual.

### Flujo de Actualización Automática

```
1. Desarrollas en local
   ↓
2. git add . && git commit -m "Cambios"
   ↓
3. git push origin main  # Producción → rama main
   ↓
4. GitHub envía webhook al servidor
   ↓
5. webhook.php valida y ejecuta update.sh automáticamente
   ↓
6. git pull de la rama main
   ↓
7. Docker rebuild
   ↓
8. Contenedor reiniciado
   ↓
9. Sitio actualizado automáticamente ✅
```

---

## ⚙️ Configuración Inicial

### Paso 1: Generar Token Secreto

```bash
# En el servidor
openssl rand -hex 32
```

Copia el token generado, lo necesitarás en los siguientes pasos.

### Paso 2: Agregar Token al `.env`

```bash
cd /var/www/cdattg_gibse
nano .env
```

Agrega la siguiente línea:

```env
WEBHOOK_SECRET=el_token_que_generaste
```

**Proteger el archivo:**

```bash
chmod 600 .env
```

### Paso 3: Configurar Permisos del Directorio de Logs

**⚠️ IMPORTANTE:** El directorio `logs/` debe tener permisos correctos para que el webhook pueda crear archivos de log.

```bash
# Crear directorio de logs si no existe
mkdir -p /var/www/cdattg_gibse/logs

# Configurar permisos (www-data debe poder escribir)
sudo chown -R www-data:www-data /var/www/cdattg_gibse/logs
sudo chmod 755 /var/www/cdattg_gibse/logs
```

### Paso 4: Verificar Configuración

```bash
# Verificar y configurar permisos automáticamente
sudo /var/www/cdattg_gibse/config/webhook-check.sh
```

Este script verifica:
- ✅ Configuración de variables de entorno
- ✅ Permisos de archivos y directorios
- ✅ **Permisos del directorio `logs/` (crea si no existe)**
- ✅ Funciones PHP necesarias
- ✅ Acceso de Docker para www-data
- ✅ Y corrige problemas automáticamente si se ejecuta con root

---

## 🐙 Configuración en GitHub

### Paso 1: Acceder a la Configuración del Webhook

1. Ve a tu repositorio en GitHub
2. Ve a **Settings** → **Webhooks**
3. Haz clic en **Add webhook**

### Paso 2: Configurar el Webhook

Configura los siguientes valores:

- **Payload URL:** `https://tu-dominio.com/webhook.php`
  - ⚠️ Reemplaza `tu-dominio.com` con tu dominio real
  - Debe ser HTTPS si tienes SSL configurado

- **Content type:** `application/json`

- **Secret:** El mismo token que configuraste en `.env`
  - ⚠️ IMPORTANTE: Debe ser exactamente el mismo

- **Which events:** Selecciona "Just the push event"
  - Esto asegura que solo se ejecute en push, no en otros eventos

- **Active:** ✅ Marcado

### Paso 3: Guardar

Haz clic en **Add webhook**

GitHub intentará enviar un webhook de prueba. Si hay errores, se mostrarán en la página.

---

## ✅ Verificación

### Paso 5: Probar el Webhook

1. Haz un cambio pequeño en el repositorio:
   ```bash
   git commit --allow-empty -m "Test webhook"
   git push origin main
   ```

2. Verifica los logs del webhook:
   ```bash
   tail -f /var/www/cdattg_gibse/logs/webhook.log
   ```

3. Verifica los logs de actualización:
   ```bash
   tail -f /var/www/cdattg_gibse/logs/update.log
   ```

### Paso 6: Verificar en GitHub

1. Ve a **Settings** → **Webhooks** → Tu webhook
2. Revisa la sección **Recent Deliveries**
3. Verifica que las entregas tengan código `200` (éxito)

---

## 🔒 Seguridad del Webhook

El webhook implementa múltiples medidas de seguridad:

- ✅ **Validación HMAC SHA-256** - Valida el token secreto
- ✅ **Solo POST** - Solo acepta peticiones POST
- ✅ **Filtrado de ramas** - Solo procesa push a la rama `main`
- ✅ **Logging** - Registra todas las peticiones en `logs/webhook.log`
- ✅ **Protección Nginx** - Nginx bloquea métodos HTTP distintos a POST
- ✅ **Validación de IPs** - Opcionalmente valida IPs de GitHub

---

## 🐛 Troubleshooting

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

3. **Verifica que el dominio sea accesible:**
   ```bash
   curl https://tu-dominio.com/webhook.php
   ```

4. **Verifica en GitHub:**
   - Ve a Settings → Webhooks → Tu webhook
   - Revisa los "Recent Deliveries"
   - Verifica el código de respuesta

### Error 403 Forbidden

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
   sudo systemctl restart apache2  # O php8.4-fpm
   ```

### Error: "exec() disabled"

**Causa:** Algunos servidores deshabilitan `exec()` por seguridad.

**Solución:** El webhook intenta usar `proc_open()` como alternativa. Si ambas están deshabilitadas, contacta a tu proveedor de hosting.

---

## 📝 Comandos Útiles

### Ver Logs en Tiempo Real

```bash
# Logs del webhook
tail -f /var/www/cdattg_gibse/logs/webhook.log

# Logs de actualización
tail -f /var/www/cdattg_gibse/logs/update.log

# Logs del contenedor
docker logs -f cdattg-gibse-web
```

### Probar Webhook Manualmente

```bash
# Desde el servidor (solo para pruebas)
curl -X POST https://tu-dominio.com/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: push" \
  -H "X-Hub-Signature-256: sha256=invalid" \
  -d '{"ref":"refs/heads/main"}'
```

---

## 📚 Recursos Adicionales

- [Configuración Técnica - Webhook](../configuracion-tecnica.md#actualización-automática-con-webhook)
- [Preguntas Frecuentes - Webhook](../faqs.md#problemas-con-webhook)
- [GitHub Webhooks Documentation](https://docs.github.com/en/developers/webhooks-and-events/webhooks)

