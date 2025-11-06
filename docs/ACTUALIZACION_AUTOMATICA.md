# Guía de Actualización Automática

## ¿Cómo funciona?

Cuando haces `git push` a la rama configurada en `.env` (por defecto `main`), el sitio se actualiza automáticamente en el servidor.

**💡 Ambientes separados:** Puedes configurar diferentes ramas para diferentes ambientes:
- **Producción:** `GIT_BRANCH=main` en `.env`
- **Desarrollo:** `GIT_BRANCH=develop` en `.env` (en un servidor diferente o con otro dominio)

## Opciones de Actualización

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

**💡 Importante:** El webhook solo se activará para la rama configurada en `GIT_BRANCH` en tu `.env`. Si quieres recibir notificaciones de múltiples ramas, necesitarás configurar webhooks separados o ajustar la lógica del webhook.

#### Paso 2: Configurar el Secreto en el Servidor

**📖 Guía detallada:** Ver [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md)

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
3. Secret token: (el mismo que configuraste en `webhooks/webhook.php`)
4. Trigger: Solo "Push events"
5. SSL verification: ✓

## Verificación

### Ver logs del webhook
```bash
tail -f /var/www/gibse/webhook.log
```

### Ver logs del contenedor
```bash
docker logs -f gibse-web
```

### Verificar que el sitio está actualizado
```bash
# Ver la fecha de los archivos
ls -la /var/www/gibse/

# Ver el estado del contenedor
docker ps | grep gibse-web
```

## Solución de Problemas

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

### Error de permisos

```bash
sudo chown -R $USER:$USER /var/www/gibse
chmod +x /var/www/gibse/scripts/update.sh
```

## Flujo Completo

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

## Seguridad

- ✅ El webhook verifica la firma del secreto
- ✅ Solo acepta pushes a la rama configurada en `GIT_BRANCH`
- ✅ Los logs se guardan para auditoría
- ✅ El secreto se gestiona mediante `.env` (no hardcodeado)
- ✅ La rama se configura mediante `.env` (flexible para diferentes ambientes)
- ⚠️ **IMPORTANTE**: 
  - Configura `WEBHOOK_SECRET` en el archivo `.env`
  - Configura `GIT_BRANCH` según tu ambiente (main para producción, develop para desarrollo)
  - Ver [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md)

