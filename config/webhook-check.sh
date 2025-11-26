#!/bin/bash

# Script unificado para verificar y configurar el webhook
# - Si se ejecuta sin root: solo verifica
# - Si se ejecuta con root: verifica y configura automáticamente

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
IS_ROOT=false
NEEDS_SETUP=false

# Verificar si tiene permisos de root
if [ "$EUID" -eq 0 ]; then
    IS_ROOT=true
fi

APACHE_USER="www-data"

echo "🔍 Verificando configuración del webhook..."
if [ "$IS_ROOT" = true ]; then
    echo "   (Modo configuración: se aplicarán correcciones automáticas)"
fi
echo ""

# ============================================================================
# VERIFICACIONES BÁSICAS
# ============================================================================

# Verificar archivo .env
if [ ! -f "$PROJECT_ROOT/.env" ]; then
    echo "❌ Archivo .env no encontrado"
    echo "   Crea el archivo .env desde .env.example"
    exit 1
fi
echo "✅ Archivo .env encontrado"

# Cargar variables de entorno
set -a
source "$PROJECT_ROOT/.env" 2>/dev/null || {
    echo "❌ Error al cargar .env"
    exit 1
}
set +a

# Verificar WEBHOOK_SECRET
if [ -z "$WEBHOOK_SECRET" ]; then
    echo "❌ WEBHOOK_SECRET no configurado en .env"
    echo ""
    echo "Genera un token con:"
    echo "  openssl rand -hex 32"
    echo ""
    echo "Y agrégalo a .env como:"
    echo "  WEBHOOK_SECRET=tu_token_aqui"
    exit 1
else
    echo "✅ WEBHOOK_SECRET configurado"
fi

# Verificar webhook.php
if [ ! -f "$PROJECT_ROOT/webhook.php" ]; then
    echo "❌ webhook.php no encontrado"
    exit 1
fi
echo "✅ webhook.php existe"

# Verificar update.sh
if [ ! -f "$PROJECT_ROOT/config/update.sh" ]; then
    echo "❌ config/update.sh no encontrado"
    exit 1
fi
echo "✅ config/update.sh existe"

# ============================================================================
# VERIFICACIONES Y CORRECCIONES DE PERMISOS
# ============================================================================

# Verificar permisos del script de actualización
if [ ! -x "$PROJECT_ROOT/config/update.sh" ]; then
    echo "⚠️  update.sh no tiene permisos de ejecución"
    if [ "$IS_ROOT" = true ]; then
        chmod +x "$PROJECT_ROOT/config/update.sh"
        echo "   ✅ Permisos corregidos automáticamente"
    else
        echo "   Ejecuta: chmod +x $PROJECT_ROOT/config/update.sh"
        NEEDS_SETUP=true
    fi
else
    echo "✅ update.sh tiene permisos de ejecución"
fi

# Verificar directorio de logs
if [ ! -d "$PROJECT_ROOT/logs" ]; then
    echo "⚠️  Directorio logs/ no existe"
    if [ "$IS_ROOT" = true ]; then
        mkdir -p "$PROJECT_ROOT/logs"
        if id "$APACHE_USER" &>/dev/null; then
            chown -R $APACHE_USER:$APACHE_USER "$PROJECT_ROOT/logs"
        fi
        chmod 755 "$PROJECT_ROOT/logs"
        echo "   ✅ Directorio creado automáticamente"
    else
        echo "   Ejecuta: mkdir -p $PROJECT_ROOT/logs"
        NEEDS_SETUP=true
    fi
else
    echo "✅ Directorio logs/ existe"
    
    # Verificar permisos de escritura
    if [ ! -w "$PROJECT_ROOT/logs" ]; then
        echo "⚠️  Directorio logs/ no es escribible"
        if [ "$IS_ROOT" = true ]; then
            if id "$APACHE_USER" &>/dev/null; then
                chown -R $APACHE_USER:$APACHE_USER "$PROJECT_ROOT/logs"
            fi
            chmod 755 "$PROJECT_ROOT/logs"
            echo "   ✅ Permisos corregidos automáticamente"
        else
            echo "   Ejecuta: chmod 755 $PROJECT_ROOT/logs"
            NEEDS_SETUP=true
        fi
    fi
fi

# ============================================================================
# CONFIGURACIÓN DEL SISTEMA (solo con root)
# ============================================================================

if [ "$IS_ROOT" = true ]; then
    echo ""
    echo "🔧 Configurando permisos del sistema..."
    
    # Verificar usuario www-data
    if ! id "$APACHE_USER" &>/dev/null; then
        echo "⚠️  Usuario $APACHE_USER no encontrado"
        echo "   Verifica que Apache/PHP esté instalado"
    else
        echo "✅ Usuario $APACHE_USER encontrado"
        
        # Agregar usuario al grupo docker
        if getent group docker > /dev/null 2>&1; then
            if groups $APACHE_USER | grep -q "\bdocker\b"; then
                echo "✅ $APACHE_USER ya está en el grupo docker"
            else
                usermod -aG docker $APACHE_USER
                echo "✅ $APACHE_USER agregado al grupo docker"
                echo "   ⚠️  Reinicia Apache/PHP-FPM para que surta efecto"
            fi
        else
            echo "⚠️  Grupo docker no existe"
            echo "   Verifica que Docker esté instalado correctamente"
        fi
        
        # Asegurar permisos de archivos
        chmod +x "$PROJECT_ROOT/config/update.sh"
        if [ -d "$PROJECT_ROOT/logs" ]; then
            chown -R $APACHE_USER:$APACHE_USER "$PROJECT_ROOT/logs" 2>/dev/null || true
        fi
    fi
fi

# ============================================================================
# VERIFICACIONES ADICIONALES
# ============================================================================

# Verificar funciones PHP necesarias
echo ""
echo "📋 Verificando funciones PHP necesarias..."
if command -v php &> /dev/null; then
    PHP_FUNCTIONS=("exec" "proc_open" "shell_exec" "file_get_contents" "file_put_contents" "json_encode" "json_decode" "hash_hmac" "hash_equals")
    MISSING_FUNCTIONS=()
    
    for func in "${PHP_FUNCTIONS[@]}"; do
        if php -r "echo function_exists('$func') ? '1' : '0';" 2>/dev/null | grep -q "1"; then
            echo "   ✅ $func: Disponible"
        else
            echo "   ❌ $func: NO disponible"
            MISSING_FUNCTIONS+=("$func")
        fi
    done
    
    if [ ${#MISSING_FUNCTIONS[@]} -gt 0 ]; then
        echo "   ⚠️  Funciones faltantes: ${MISSING_FUNCTIONS[*]}"
        echo "      El webhook puede no funcionar correctamente"
        NEEDS_SETUP=true
    fi
else
    echo "   ⚠️  PHP no encontrado en PATH (no se pueden verificar funciones)"
fi

echo ""
echo "📋 Verificando Docker..."

# Verificar si Docker está instalado
if command -v docker &> /dev/null; then
    echo "✅ Docker está instalado"
    
    # Verificar si el usuario actual puede ejecutar docker
    if docker ps &> /dev/null; then
        echo "✅ Docker es accesible desde este usuario"
    else
        echo "⚠️  Docker no es accesible desde este usuario"
        if [ "$IS_ROOT" = false ]; then
            echo "   Ejecuta como root para configurar permisos:"
            echo "   sudo $0"
            NEEDS_SETUP=true
        fi
    fi
    
    # Si es root, verificar www-data
    if [ "$IS_ROOT" = true ] && id "$APACHE_USER" &>/dev/null; then
        if sudo -u $APACHE_USER docker ps &> /dev/null 2>&1; then
            echo "✅ Docker es accesible desde $APACHE_USER"
        else
            echo "⚠️  Docker NO es accesible desde $APACHE_USER"
            echo "   Reinicia Apache/PHP-FPM después de agregar al grupo docker"
        fi
    fi
else
    echo "⚠️  Docker no encontrado en PATH"
    NEEDS_SETUP=true
fi

# ============================================================================
# INFORMACIÓN DE CONFIGURACIÓN
# ============================================================================

echo ""
echo "📋 Configuración del webhook:"
DOMAIN="${DOMAIN:-gibse.dataguaviare.com.co}"
echo "   Dominio: $DOMAIN"
echo "   Rama: ${GIT_BRANCH:-main}"
echo "   Entorno: ${ENVIRONMENT:-production}"
echo ""
echo "🔗 URL del webhook:"
echo "   https://$DOMAIN/webhook.php"
echo ""

# ============================================================================
# RESUMEN Y PRÓXIMOS PASOS
# ============================================================================

if [ "$NEEDS_SETUP" = true ] && [ "$IS_ROOT" = false ]; then
    echo "⚠️  Se detectaron problemas que requieren permisos de root"
    echo ""
    echo "📝 Para corregir automáticamente, ejecuta:"
    echo "   sudo $0"
    echo ""
fi

if [ "$IS_ROOT" = true ]; then
    echo "📝 Próximos pasos:"
    echo "   1. Reinicia Apache/PHP-FPM para aplicar cambios de grupo:"
    echo "      sudo systemctl restart apache2"
    echo "      # O si usas PHP-FPM:"
    echo "      sudo systemctl restart php8.4-fpm"
    echo ""
    echo "   2. Verifica que $APACHE_USER puede ejecutar Docker:"
    echo "      sudo -u $APACHE_USER docker ps"
    echo ""
fi

echo "📝 Para configurar el webhook en GitHub:"
echo "   1. Ve a: https://github.com/senacdattg/gibse/settings/hooks"
echo "   2. Click en 'Add webhook'"
echo "   3. Payload URL: https://$DOMAIN/webhook.php"
echo "   4. Content type: application/json"
echo "   5. Secret: $WEBHOOK_SECRET"
echo "   6. Events: Just the push event"
echo "   7. Active: ✅"
echo ""

if [ "$NEEDS_SETUP" = false ] || [ "$IS_ROOT" = true ]; then
    echo "✅ Verificación completada"
    exit 0
else
    echo "⚠️  Verificación completada con advertencias"
    exit 1
fi

