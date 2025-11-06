# Guía de Seguridad - GIBSE

## Archivos y Scripts Sensibles

### ⚠️ Creación de archivo .env

**Método correcto (desarrollo y producción):**
```bash
# Simplemente copia el .env.example
cp .env.example .env

# Edita con valores seguros
nano .env

# Establece permisos seguros
chmod 600 .env
chown $USER:$USER .env
```

**⚠️ IMPORTANTE:**
- ❌ NO uses los valores por defecto del `.env.example`
- ✅ Genera secretos seguros: `openssl rand -hex 32`
- ✅ Verifica todos los valores antes de usar
- ✅ En producción, crea el `.env` manualmente (no automatizado)

## Archivos Confidenciales

### `.env`
- **Contiene:** Secretos, configuraciones sensibles
- **Permisos:** `600` (solo propietario)
- **Git:** ❌ NO debe estar en Git (está en `.gitignore`)
- **Backup:** ⚠️ No incluir en backups públicos

### `webhooks/webhook.php`
- **Contiene:** Lógica de webhook (lee secretos desde `.env`)
- **Permisos:** `644` (lectura para todos, escritura solo propietario)
- **Acceso:** Solo debe ser accesible vía HTTPS

## Buenas Prácticas de Seguridad

### 1. Gestión de Secretos

```bash
# Generar secretos seguros
openssl rand -hex 32

# NUNCA uses:
# - Valores por defecto
# - Secretos compartidos entre ambientes
# - Secretos en el código
```

### 2. Permisos de Archivos

```bash
# .env debe ser solo lectura/escritura para el propietario
chmod 600 .env

# Scripts ejecutables solo para el propietario
chmod 700 scripts/*.sh

# Archivos PHP con permisos estándar
chmod 644 webhooks/*.php
```

### 3. Validación de Entorno

- ✅ Verifica que estás en el entorno correcto antes de ejecutar scripts
- ✅ Usa diferentes secretos para desarrollo y producción
- ✅ No ejecutes scripts de desarrollo en producción

### 4. Logs y Auditoría

```bash
# Los logs del webhook pueden contener información sensible
# Asegúrate de que solo el propietario pueda leerlos
chmod 600 webhook.log
```

## Checklist de Seguridad para Producción

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

## Comandos de Verificación

```bash
# Verificar que .env no está en Git
git status | grep .env

# Verificar permisos de .env
ls -la .env
# Debe mostrar: -rw------- (600)

# Verificar que el secreto está configurado (sin mostrarlo)
grep -q WEBHOOK_SECRET .env && echo "OK" || echo "FALTA"

# Verificar permisos de scripts
ls -la scripts/
# Deben ser ejecutables solo por el propietario en producción
```

## Incidentes de Seguridad

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

## Recursos Adicionales

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Docker Security Best Practices](https://docs.docker.com/engine/security/)
- [Nginx Security Headers](https://www.nginx.com/blog/http-strict-transport-security-hsts-and-nginx/)

