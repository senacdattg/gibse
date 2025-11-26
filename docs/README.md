# 📚 Documentación del Proyecto GIBSE

Índice de la documentación del proyecto, organizada por entorno y tema.

## 📁 Estructura de la Documentación

```
docs/
├── dev/                    # Documentación de Desarrollo
│   └── instalacion.md     # Guía de instalación para desarrollo local
├── prod/                   # Documentación de Producción
│   ├── instalacion.md      # Guía de instalación en producción
│   ├── webhook.md          # Configuración del webhook
│   └── seguridad.md        # Mejores prácticas de seguridad
├── configuracion-tecnica.md # Configuración técnica (común)
├── faqs.md                 # Preguntas frecuentes
└── README.md               # Este archivo
```

---

## 💻 Documentación de Desarrollo

### [Instalación para Desarrollo](dev/instalacion.md)

Guía completa para configurar y ejecutar el proyecto en tu entorno de desarrollo local.

**Incluye:**
- Requisitos previos
- Instalación paso a paso
- Configuración de variables de entorno
- Ejecución del proyecto
- Comandos útiles
- Troubleshooting

---

## 🚀 Documentación de Producción

### [Instalación en Producción](prod/instalacion.md)

Guía completa para desplegar el proyecto en un servidor de producción.

**Incluye:**
- Preparación del servidor
- Instalación de dependencias (Docker, Nginx)
- Configuración del proyecto
- Configuración de DNS
- Configuración de SSL/HTTPS
- Verificación

### [Configuración del Webhook](prod/webhook.md)

Guía detallada para configurar el webhook de GitHub que permite actualización automática.

**Incluye:**
- ¿Qué es el webhook?
- Configuración inicial
- Configuración en GitHub
- Verificación
- Troubleshooting

### [Seguridad en Producción](prod/seguridad.md)

Mejores prácticas de seguridad para el despliegue en producción.

**Incluye:**
- Checklist de seguridad
- Configuración de firewall
- Protección de archivos sensibles
- SSL/HTTPS
- Actualizaciones del sistema
- Backups
- Monitoreo

---

## 📋 Documentación General

### [Configuración Técnica](configuracion-tecnica.md)

Documentación técnica detallada común para desarrollo y producción.

**Incluye:**
- Variables de entorno
- Configuración de Docker
- Configuración de Nginx
- Actualización automática con webhook
- Arquitectura del sistema

### [Preguntas Frecuentes](faqs.md)

Solución de problemas comunes y preguntas frecuentes.

**Incluye:**
- Problemas de instalación
- Problemas de despliegue
- Problemas con Docker
- Problemas con Nginx
- Problemas con webhook
- Problemas de DNS
- Problemas de permisos

---

## 🗺️ Guía de Navegación

### ¿Eres nuevo en el proyecto?

1. Lee el [README principal](../README.md)
2. Si vas a desarrollar: [Instalación para Desarrollo](dev/instalacion.md)
3. Si vas a desplegar: [Instalación en Producción](prod/instalacion.md)

### ¿Necesitas configurar algo específico?

- **Variables de entorno:** [Configuración Técnica - Variables](configuracion-tecnica.md#variables-de-entorno)
- **Docker:** [Configuración Técnica - Docker](configuracion-tecnica.md#configuración-de-docker)
- **Nginx:** [Configuración Técnica - Nginx](configuracion-tecnica.md#configuración-de-nginx)
- **Webhook:** [Webhook en Producción](prod/webhook.md)
- **Seguridad:** [Seguridad en Producción](prod/seguridad.md)

### ¿Tienes un problema?

Consulta [Preguntas Frecuentes](faqs.md) para soluciones comunes.

---

## 🔗 Enlaces Rápidos

- [README Principal](../README.md)
- [Inicio Rápido - Desarrollo](../README.md#desarrollo-local)
- [Inicio Rápido - Producción](../README.md#producción)
- [Configuración Técnica](configuracion-tecnica.md)
- [Preguntas Frecuentes](faqs.md)

---

## 📝 Notas

- La documentación está organizada por entorno (desarrollo/producción) y tema
- La documentación técnica común está en `configuracion-tecnica.md`
- Para problemas específicos, consulta las FAQs
- Todas las rutas son relativas desde la raíz del proyecto

