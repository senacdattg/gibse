# GIBSE - Gestión Integral de la Biodiversidad

Aplicación web PHP para el programa de **Tecnología en Gestión Integral de la Biodiversidad y los Servicios Ecosistémicos** del SENA.

[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-SENA-green.svg)](LICENSE)

---

## 📖 Descripción

Este proyecto es una aplicación web informativa desarrollada para el programa de **Tecnología en Gestión Integral de la Biodiversidad y los Servicios Ecosistémicos** del SENA.

El sitio web proporciona información sobre:
- Información general del programa (ID: 222212)
- Centros de formación donde está disponible
- Estructura curricular con 6 competencias profesionales
- Información de contacto a nivel nacional

---

## ✨ Características Principales

- ✅ **Diseño Responsive** - Compatible con dispositivos móviles, tablets y desktop
- ✅ **Despliegue con Docker** - Contenedorización para fácil despliegue
- ✅ **Actualización Automática** - Webhook de GitHub para despliegue automático
- ✅ **Separación de Ambientes** - Perfiles de desarrollo y producción
- ✅ **Seguridad** - Validación de webhooks, SSL/HTTPS, protección de archivos sensibles
- ✅ **Logs y Monitoreo** - Sistema de logging para debugging

---

## 🚀 Inicio Rápido

### Desarrollo Local

```bash
# Clonar el repositorio
git clone <url-del-repositorio> cdattg_gibse
cd cdattg_gibse
git checkout develop  # Cambiar a rama de desarrollo

# Configurar variables de entorno
cp .env.example .env
nano .env  # Configura ENVIRONMENT=development y GIT_BRANCH=develop

# Ejecutar
docker-compose --profile dev up -d --build
```

El sitio estará disponible en: `http://localhost:8080`

📖 **Documentación completa:** [Instalación para Desarrollo](docs/dev/instalacion.md)

### Producción

```bash
# Clonar en el servidor
cd /var/www
git clone <url-del-repositorio> cdattg_gibse
cd cdattg_gibse
git checkout main  # Cambiar a rama de producción

# Configurar
cp .env.example .env
nano .env  # Configura ENVIRONMENT=production, GIT_BRANCH=main y DOMAIN

# Desplegar
./config/update.sh
```

📖 **Documentación completa:** [Instalación en Producción](docs/prod/instalacion.md)

**⚠️ IMPORTANTE:** Después de configurar `.env`, asegúrate de configurar los permisos del directorio `logs/`:
```bash
sudo mkdir -p /var/www/cdattg_gibse/logs
sudo chown -R www-data:www-data /var/www/cdattg_gibse/logs
sudo chmod 755 /var/www/cdattg_gibse/logs
```
O ejecuta: `sudo ./config/webhook-check.sh` para configurarlo automáticamente.

---

## 📁 Estructura del Proyecto

```
cdattg_gibse/
├── assets/              # Recursos estáticos (CSS, JS, imágenes, videos)
├── config/              # Configuraciones del proyecto
│   ├── env-loader.php   # Cargador de variables de entorno
│   ├── nginx.conf       # Plantilla de configuración de Nginx
│   ├── update.sh        # Script para actualizar el sitio
│   └── webhook-check.sh # Script para verificar/configurar webhook
├── docs/                # Documentación técnica
│   ├── dev/             # Documentación de desarrollo
│   │   └── instalacion.md
│   ├── prod/            # Documentación de producción
│   │   ├── instalacion.md
│   │   ├── webhook.md
│   │   └── seguridad.md
│   ├── configuracion-tecnica.md  # Configuración detallada (común)
│   ├── faqs.md          # Preguntas frecuentes
│   └── README.md         # Índice de documentación
├── logs/                # Logs de la aplicación (ignorado en Git)
├── docker-compose.yml   # Docker Compose con perfiles (dev/prod)
├── Dockerfile           # Configuración de la imagen Docker
├── .env.example         # Plantilla de variables de entorno
├── webhook.php          # Endpoint para webhooks de GitHub
└── index.php            # Página principal de la aplicación
```

---

## 📚 Documentación

### 📖 Guías por Entorno

#### 💻 Desarrollo
- **[Instalación para Desarrollo](docs/dev/instalacion.md)** - Guía completa para desarrollo local
- **[Configuración Técnica](docs/configuracion-tecnica.md)** - Configuración detallada (común)

#### 🚀 Producción
- **[Instalación en Producción](docs/prod/instalacion.md)** - Guía completa para despliegue en producción
- **[Configuración del Webhook](docs/prod/webhook.md)** - Configuración del webhook de GitHub
- **[Seguridad en Producción](docs/prod/seguridad.md)** - Mejores prácticas de seguridad

#### 📋 Documentación General
- **[Configuración Técnica](docs/configuracion-tecnica.md)** - Variables de entorno, Docker, Nginx
- **[Preguntas Frecuentes](docs/faqs.md)** - Solución de problemas comunes

### 🔗 Enlaces Rápidos

- [Inicio Rápido - Desarrollo](#desarrollo-local)
- [Inicio Rápido - Producción](#producción)
- [Variables de Entorno](docs/configuracion-tecnica.md#variables-de-entorno)
- [Solución de Problemas](docs/faqs.md)

---

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 8.4** - Lenguaje de programación del lado del servidor
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

---

## 🏗️ Arquitectura

### Desarrollo Local
- Apache en Docker (puerto 8080)
- Volúmenes montados para hot-reload

### Producción
- Nginx en el host (puerto 80/443) → Reverse proxy
- Apache en Docker (127.0.0.1:8081)
- SSL/HTTPS gestionado por Nginx
- Código dentro de la imagen Docker (sin volúmenes)

Esta separación de responsabilidades es una práctica estándar en la industria.

---

## 🔒 Seguridad

El proyecto implementa múltiples medidas de seguridad:

- ✅ Validación HMAC SHA-256 para webhooks
- ✅ Protección de archivos sensibles (`.env` no está en Git)
- ✅ Nginx bloquea acceso a carpetas de configuración
- ✅ SSL/HTTPS con Let's Encrypt
- ✅ Firewall recomendado (UFW)

Ver [Configuración Técnica - Seguridad](docs/configuracion-tecnica.md#seguridad) para más detalles.

---

## 📝 Comandos Útiles

### Desarrollo

```bash
# Iniciar
docker-compose --profile dev up -d

# Ver logs
docker logs -f cdattg-gibse-web

# Detener
docker-compose --profile dev down
```

### Producción

```bash
# Actualizar sitio
./config/update.sh

# Ver logs
docker logs -f cdattg-gibse-web
tail -f logs/webhook.log

# Verificar configuración
sudo ./config/webhook-check.sh
```

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📝 Licencia

SENA - Gestión Integral de la Biodiversidad

---

## 📧 Contacto

Para más información sobre el programa, visita el sitio web oficial del SENA.

---

## 📚 Recursos Adicionales

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Docker Security Best Practices](https://docs.docker.com/engine/security/)
- [Nginx Security Headers](https://www.nginx.com/blog/http-strict-transport-security-hsts-and-nginx/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [PHP 8.4 Documentation](https://www.php.net/manual/es/)

---

**¿Necesitas ayuda?** Consulta la [documentación completa](docs/) o las [preguntas frecuentes](docs/faqs.md).
