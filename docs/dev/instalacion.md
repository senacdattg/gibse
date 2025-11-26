# 💻 Instalación para Desarrollo Local

Guía paso a paso para configurar y ejecutar el proyecto GIBSE en tu entorno de desarrollo local.

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Instalación](#instalación)
3. [Configuración](#configuración)
4. [Ejecución](#ejecución)
5. [Comandos Útiles](#comandos-útiles)
6. [Troubleshooting](#troubleshooting)

---

## 🔧 Requisitos Previos

- **Docker Desktop** (o Docker Engine + Docker Compose)
- **Git** instalado
- Puerto **8080** disponible

---

## 📦 Instalación

### Paso 1: Clonar el Repositorio

```bash
git clone <url-del-repositorio> cdattg_gibse
cd cdattg_gibse
```

### Paso 2: Cambiar a la Rama de Desarrollo

```bash
git checkout develop
```

---

## ⚙️ Configuración

### Paso 1: Configurar Variables de Entorno

```bash
# Copiar plantilla
cp .env.example .env

# Editar con tus valores
nano .env  # o usa tu editor preferido
```

**Configuración mínima para desarrollo:**

```env
ENVIRONMENT=development
GIT_BRANCH=develop  # ⚠️ IMPORTANTE: Desarrollo usa rama 'develop'
```

**Proteger el archivo:**

```bash
chmod 600 .env
```

**⚠️ IMPORTANTE:** El archivo `.env` contiene información confidencial y **NO debe subirse a Git**.

---

## 🚀 Ejecución

### Iniciar el Proyecto

```bash
docker-compose --profile dev up -d --build
```

El sitio estará disponible en: `http://localhost:8080`

### Verificar que Funciona

```bash
# Ver logs
docker logs -f cdattg-gibse-web

# Ver estado del contenedor
docker ps
```

### Detener el Proyecto

```bash
docker-compose --profile dev down
```

---

## 💡 Características del Modo Desarrollo

- ✅ Puerto: `8080` (accesible desde fuera)
- ✅ Volúmenes montados para hot-reload (cambios se reflejan inmediatamente)
- ✅ Logs en tiempo real
- ✅ Código editable directamente en el host

---

## 🔧 Comandos Útiles

### Ver Logs

```bash
# Logs en tiempo real
docker logs -f cdattg-gibse-web

# Últimas 50 líneas
docker logs --tail 50 cdattg-gibse-web
```

### Reiniciar el Contenedor

```bash
docker-compose --profile dev restart
```

### Reconstruir la Imagen

```bash
docker-compose --profile dev build --no-cache
docker-compose --profile dev up -d
```

### Ver Estado

```bash
docker ps
docker-compose --profile dev ps
```

### Acceder al Contenedor

```bash
docker exec -it cdattg-gibse-web bash
```

---

## 🐛 Troubleshooting

### El puerto 8080 está ocupado

**Solución:**

```bash
# Ver qué está usando el puerto
sudo netstat -tulpn | grep :8080

# O cambiar el puerto en docker-compose.yml
# Edita la línea: "8080:80" → "8081:80"
```

### Los cambios no se reflejan

**Solución:**

1. Verifica que estés usando el perfil `dev`:
   ```bash
   docker-compose --profile dev ps
   ```

2. Verifica que los volúmenes estén montados:
   ```bash
   docker inspect cdattg-gibse-web | grep Mounts -A 10
   ```

3. Reinicia el contenedor:
   ```bash
   docker-compose --profile dev restart
   ```

### Error al iniciar Docker

**Solución:**

1. Verifica que Docker esté corriendo:
   ```bash
   docker ps
   ```

2. Verifica los logs de Docker:
   ```bash
   docker-compose --profile dev logs
   ```

### El contenedor se detiene inmediatamente

**Solución:**

1. Verifica los logs:
   ```bash
   docker logs cdattg-gibse-web
   ```

2. Verifica la configuración:
   ```bash
   docker-compose --profile dev config
   ```

---

## 📝 Próximos Pasos

- [Configuración Técnica](../configuracion-tecnica.md) - Configuración detallada
- [Preguntas Frecuentes](../faqs.md) - Solución de problemas comunes
- [Documentación de Producción](../prod/instalacion.md) - Para despliegue en producción

---

## 🔗 Enlaces Útiles

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP 8.4 Documentation](https://www.php.net/manual/es/)

