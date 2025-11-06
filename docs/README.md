# Documentación del Proyecto GIBSE

Esta carpeta contiene toda la documentación necesaria para desplegar y mantener el proyecto.

## Guías Disponibles

### 🚀 [GUIA_DESPLIEGUE.md](GUIA_DESPLIEGUE.md)
Guía completa paso a paso para desplegar el proyecto en un VPS de Hostinger. Incluye:
- Configuración inicial del servidor
- Instalación de Docker y Nginx
- Configuración de SSL
- Comandos útiles
- Solución de problemas

**Cuándo usar:** Cuando vayas a desplegar el proyecto por primera vez o necesites una referencia completa.

### 🌐 [CONFIGURACION_DNS_HOSTINGER.md](CONFIGURACION_DNS_HOSTINGER.md)
Guía específica para configurar el DNS en Hostinger. Incluye:
- Cómo crear un registro A
- Configuración del subdominio
- Verificación del DNS
- Solución de problemas comunes

**Cuándo usar:** Cuando necesites configurar o verificar el DNS de `gibse.dataguaviare.com.co`.

### 🔐 [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md)
Guía para configurar variables de entorno usando archivos `.env`. Incluye:
- Creación y configuración del archivo `.env`
- Variables disponibles
- Uso en PHP y scripts Bash
- Seguridad y buenas prácticas

**Cuándo usar:** Cuando necesites configurar secretos, rutas o configuraciones específicas del entorno.

### 🔄 [ACTUALIZACION_AUTOMATICA.md](ACTUALIZACION_AUTOMATICA.md)
Guía para configurar la actualización automática del sitio. Incluye:
- Actualización manual
- Configuración de webhooks
- Actualización automática desde Git
- Verificación y solución de problemas

**Cuándo usar:** Cuando quieras automatizar las actualizaciones del sitio después de hacer push a Git.

### 🔒 [SEGURIDAD.md](SEGURIDAD.md)
Guía de seguridad y buenas prácticas. Incluye:
- Riesgos de scripts en producción
- Gestión segura de secretos
- Permisos de archivos
- Checklist de seguridad
- Respuesta a incidentes

**Cuándo usar:** Antes de desplegar en producción y para revisar la seguridad del proyecto.

## Orden Recomendado de Lectura

1. **Primera vez desplegando:**
   - [GUIA_DESPLIEGUE.md](GUIA_DESPLIEGUE.md) - Sigue todos los pasos
   - [CONFIGURACION_DNS_HOSTINGER.md](CONFIGURACION_DNS_HOSTINGER.md) - Para el paso de DNS
   - [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md) - Para configurar variables
   - [SEGURIDAD.md](SEGURIDAD.md) - ⚠️ **IMPORTANTE:** Revisa antes de producción

2. **Configurando actualización automática:**
   - [CONFIGURACION_ENV.md](CONFIGURACION_ENV.md) - Configura WEBHOOK_SECRET
   - [ACTUALIZACION_AUTOMATICA.md](ACTUALIZACION_AUTOMATICA.md) - Configura el webhook

3. **Solución de problemas:**
   - Revisa la sección "Solución de Problemas" en cada guía
   - Verifica que el `.env` esté configurado correctamente
   - Revisa los logs del contenedor y del webhook

4. **Revisión de seguridad:**
   - [SEGURIDAD.md](SEGURIDAD.md) - Revisa antes de producción
   - Verifica permisos de archivos
   - Confirma que no hay secretos en Git

## Referencias Rápidas

### Comandos Más Usados

```bash
# Construir y ejecutar el contenedor
docker-compose -f docker/docker-compose.prod.yml up -d

# Actualizar el sitio
./scripts/update.sh

# Ver logs del contenedor
docker logs -f gibse-web

# Ver logs del webhook
tail -f /var/www/gibse/webhook.log

# Verificar DNS
nslookup gibse.dataguaviare.com.co
```

### Archivos Importantes

- `.env` - Configuración del entorno (NO en Git)
- `.env.example` - Plantilla de configuración
- `docker/docker-compose.prod.yml` - Configuración de Docker para producción
- `scripts/update.sh` - Script de actualización
- `webhooks/webhook.php` - Endpoint para actualización automática

## Estructura del Proyecto

```
gibse/
├── config/          # Configuraciones (env-loader.php)
├── docker/          # Configuraciones de Docker
├── scripts/         # Scripts de actualización
├── webhooks/        # Webhooks para CI/CD
├── docs/           # Esta documentación
└── assets/         # Recursos estáticos
```

## Soporte

Si encuentras problemas:

1. Revisa la sección "Solución de Problemas" en la guía correspondiente
2. Verifica los logs del contenedor y del webhook
3. Asegúrate de que el archivo `.env` esté configurado correctamente
4. Verifica que el DNS esté propagado correctamente

## Actualización de la Documentación

Esta documentación se mantiene actualizada con la estructura del proyecto. Si encuentras inconsistencias, por favor actualiza la documentación correspondiente.

