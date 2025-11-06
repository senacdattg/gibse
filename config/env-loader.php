<?php
/**
 * Cargador de variables de entorno desde .env
 * 
 * Uso:
 * require_once __DIR__ . '/config/env-loader.php';
 * $secret = getEnvVar('WEBHOOK_SECRET');
 */

/**
 * Carga las variables de entorno desde el archivo .env
 * 
 * @param string $envFile Ruta al archivo .env
 * @return void
 */
function loadEnv($envFile = null) {
    if ($envFile === null) {
        // Buscar .env en el directorio raíz del proyecto
        $envFile = __DIR__ . '/../.env';
    }
    
    if (!file_exists($envFile)) {
        return;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Ignorar líneas sin =
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        
        $key = trim($key);
        $value = trim($value);
        
        // Remover comillas si existen
        $value = trim($value, '"\'');
        
        // No sobrescribir variables de entorno existentes
        if (!isset($_ENV[$key]) && !getenv($key)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Obtiene una variable de entorno
 * 
 * @param string $key Nombre de la variable
 * @param mixed $default Valor por defecto si no existe
 * @return mixed
 */
function getEnvVar($key, $default = null) {
    // Primero intentar $_ENV
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    // Luego intentar getenv()
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    
    // Retornar valor por defecto
    return $default;
}

// Cargar .env automáticamente
loadEnv();

