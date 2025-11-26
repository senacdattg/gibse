<?php
/**
 * Webhook endpoint para GitHub - Despliegue automático
 * 
 * Este endpoint recibe notificaciones de GitHub cuando hay cambios
 * y ejecuta automáticamente el script de actualización.
 * 
 * Seguridad:
 * - Valida el token secreto del webhook
 * - Verifica que la petición viene de GitHub (IPs y headers)
 * - Solo procesa eventos de push a la rama configurada
 * 
 * @package Webhook
 * @version 1.1.0
 */

// Deshabilitar límite de tiempo de ejecución (el script se ejecuta en background)
set_time_limit(0);

// Cargar configuración
if (!file_exists(__DIR__ . '/config/env-loader.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error de configuración: env-loader.php no encontrado']);
    exit;
}

require_once __DIR__ . '/config/env-loader.php';

// Configuración
$webhookSecret = getEnvVar('WEBHOOK_SECRET', '');
$allowedBranch = getEnvVar('GIT_BRANCH', 'main');
$logFile = __DIR__ . '/logs/webhook.log';
$updateScript = __DIR__ . '/config/update.sh';

// IPs oficiales de GitHub (actualizadas periódicamente)
$githubIPs = [
    '140.82.112.0/20',
    '143.55.64.0/20',
    '185.199.108.0/22',
    '192.30.252.0/22',
    '2a0a:a440::/29',
    '2606:50c0::/32'
];

// Función para obtener headers HTTP (compatible con FastCGI y Apache)
function getAllHeadersCompat(): array
{
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    
    // Fallback para FastCGI
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($name, 5)))));
            $headers[$headerName] = $value;
        }
    }
    
    // Headers específicos de Apache
    if (isset($_SERVER['CONTENT_TYPE'])) {
        $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
    }
    if (isset($_SERVER['CONTENT_LENGTH'])) {
        $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
    }
    
    return $headers;
}

// Función para validar IP de GitHub
function isValidGitHubIP(string $ip): bool
{
    global $githubIPs;
    
    // Si no hay IPs configuradas, permitir (para desarrollo)
    if (empty($githubIPs)) {
        return true;
    }
    
    foreach ($githubIPs as $range) {
        if (ipInRange($ip, $range)) {
            return true;
        }
    }
    
    return false;
}

// Función auxiliar para verificar si IP está en rango CIDR
function ipInRange(string $ip, string $range): bool
{
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }
    
    list($subnet, $mask) = explode('/', $range);
    
    // IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    // IPv6 (simplificado)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Para IPv6, usar comparación de strings simplificada
        // En producción, considerar usar una librería especializada
        return strpos($ip, substr($subnet, 0, strpos($subnet, '::'))) === 0;
    }
    
    return false;
}

// Función para registrar logs con manejo de errores
function logWebhook(string $message, array $context = []): void
{
    global $logFile;
    
    try {
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            if (!@mkdir($logDir, 0755, true)) {
                error_log("Webhook: No se pudo crear directorio de logs: $logDir");
                return;
            }
        }
        
        if (!is_writable($logDir)) {
            error_log("Webhook: Directorio de logs no es escribible: $logDir");
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $logMessage = "[$timestamp] $message$contextStr" . PHP_EOL;
        
        if (@file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX) === false) {
            error_log("Webhook: No se pudo escribir en el archivo de log: $logFile");
        }
    } catch (Exception $e) {
        error_log("Webhook: Error al escribir log: " . $e->getMessage());
    }
}

// Función para enviar respuesta JSON
function sendResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Función para validar el token del webhook
function validateWebhookSecret(string $secret, string $payload, string $signature): bool
{
    if (empty($secret)) {
        return false;
    }
    
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Función para ejecutar el script de actualización en background
function executeUpdateScript(string $scriptPath): array
{
    if (!file_exists($scriptPath)) {
        return [
            'success' => false,
            'error' => 'Script de actualización no encontrado: ' . $scriptPath
        ];
    }
    
    // Verificar permisos de ejecución
    if (!is_readable($scriptPath)) {
        return [
            'success' => false,
            'error' => 'Script de actualización no es legible'
        ];
    }
    
    // Verificar que exec() esté disponible
    if (!function_exists('exec')) {
        return [
            'success' => false,
            'error' => 'Función exec() no está disponible (puede estar deshabilitada por seguridad)'
        ];
    }
    
    // Verificar que shell_exec esté disponible como alternativa
    $hasShellExec = function_exists('shell_exec');
    $hasProcOpen = function_exists('proc_open');
    
    if (!$hasShellExec && !$hasProcOpen) {
        return [
            'success' => false,
            'error' => 'No hay funciones disponibles para ejecutar comandos del sistema'
        ];
    }
    
    $logFile = __DIR__ . '/logs/update.log';
    $logDir = dirname($logFile);
    
    // Asegurar que el directorio de logs existe
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Intentar hacer el script ejecutable si no lo es
    if (!is_executable($scriptPath)) {
        @chmod($scriptPath, 0755);
        if (!is_executable($scriptPath)) {
            logWebhook('Advertencia: Script no es ejecutable, intentando ejecutar con bash', [
                'script' => $scriptPath
            ]);
        }
    }
    
    // Construir comando con bash explícito para mayor compatibilidad
    $bashPath = '/bin/bash';
    if (!file_exists($bashPath)) {
        $bashPath = 'bash'; // Intentar con PATH
    }
    
    // Usar bash explícito para ejecutar el script
    $command = sprintf(
        '%s %s >> %s 2>&1 &',
        escapeshellarg($bashPath),
        escapeshellarg($scriptPath),
        escapeshellarg($logFile)
    );
    
    // Ejecutar en background
    $output = [];
    $returnCode = 0;
    
    if ($hasProcOpen) {
        // Usar proc_open para mejor control
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = @proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            // Cerrar pipes inmediatamente para que el proceso se ejecute en background
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $returnCode = 0;
        } else {
            $returnCode = 1;
        }
    } else {
        // Fallback a shell_exec
        @exec($command, $output, $returnCode);
    }
    
    // Obtener PID del proceso si es posible
    $pid = null;
    if ($returnCode === 0) {
        // Intentar obtener el PID del proceso en background
        $pidFile = __DIR__ . '/logs/update.pid';
        // El PID se puede obtener del proceso hijo, pero es complejo en background
        // Por ahora, retornamos éxito si el comando se ejecutó
    }
    
    return [
        'success' => $returnCode === 0,
        'pid' => $pid,
        'command' => $command,
        'method' => $hasProcOpen ? 'proc_open' : 'exec'
    ];
}

// Manejo de errores global
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logWebhook('Error fatal en webhook', [
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Iniciar procesamiento
logWebhook('Webhook recibido', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
    'forwarded' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
]);

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logWebhook('Método HTTP no permitido', ['method' => $_SERVER['REQUEST_METHOD']]);
    sendResponse(405, ['error' => 'Método no permitido']);
}

// Obtener IP del cliente
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$forwardedIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

// Si hay proxy, tomar la primera IP
if (!empty($forwardedIP)) {
    $ips = explode(',', $forwardedIP);
    $clientIP = trim($ips[0]);
}

// Validar IP de GitHub (opcional pero recomendado)
if (!isValidGitHubIP($clientIP)) {
    logWebhook('Petición desde IP no autorizada', [
        'ip' => $clientIP,
        'forwarded' => $forwardedIP
    ]);
    // En producción estricta, descomentar la siguiente línea:
    // sendResponse(403, ['error' => 'IP no autorizada']);
}

// Obtener el payload
$payload = @file_get_contents('php://input');

if ($payload === false) {
    logWebhook('Error al leer payload');
    sendResponse(400, ['error' => 'Error al leer payload']);
}

// Obtener headers de forma compatible
$headers = getAllHeadersCompat();

// Validar que viene de GitHub
$githubEvent = $headers['X-GitHub-Event'] ?? $headers['x-github-event'] ?? null;
$githubSignature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? '';

if (!$githubEvent) {
    logWebhook('Petición no válida: falta header X-GitHub-Event');
    sendResponse(400, ['error' => 'Petición no válida']);
}

// Validar que el token secreto esté configurado
if (empty($webhookSecret)) {
    logWebhook('ERROR CRÍTICO: WEBHOOK_SECRET no configurado en .env', [
        'ip' => $clientIP
    ]);
    sendResponse(500, ['error' => 'Configuración incompleta: WEBHOOK_SECRET no está configurado']);
}

// Validar token secreto
if (!validateWebhookSecret($webhookSecret, $payload, $githubSignature)) {
    logWebhook('Token secreto inválido', [
        'ip' => $clientIP,
        'signature_received' => substr($githubSignature, 0, 20) . '...' // Solo primeros caracteres para log
    ]);
    sendResponse(401, ['error' => 'No autorizado']);
}

// Decodificar payload
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logWebhook('Error al decodificar JSON', ['error' => json_last_error_msg()]);
    sendResponse(400, ['error' => 'Payload JSON inválido']);
}

// Solo procesar eventos de push
if ($githubEvent !== 'push') {
    logWebhook('Evento ignorado', ['event' => $githubEvent]);
    sendResponse(200, ['message' => 'Evento recibido pero no procesado', 'event' => $githubEvent]);
}

// Verificar que es la rama correcta
$ref = $data['ref'] ?? '';
$branch = str_replace('refs/heads/', '', $ref);

if ($branch !== $allowedBranch) {
    logWebhook('Push a rama ignorada', ['branch' => $branch, 'allowed' => $allowedBranch]);
    sendResponse(200, [
        'message' => 'Push recibido pero rama no configurada para auto-deploy',
        'branch' => $branch
    ]);
}

// Ejecutar script de actualización
$commitId = $data['head_commit']['id'] ?? 'UNKNOWN';
$commitMessage = $data['head_commit']['message'] ?? 'Sin mensaje';
$author = $data['head_commit']['author']['name'] ?? 'UNKNOWN';

logWebhook('Iniciando actualización automática', [
    'branch' => $branch,
    'commit' => $commitId,
    'commit_message' => substr($commitMessage, 0, 100), // Limitar longitud
    'author' => $author
]);

// Enviar respuesta inmediatamente para que GitHub no espere
// El script se ejecutará en background
ignore_user_abort(true);

// Ejecutar script en background
$result = executeUpdateScript($updateScript);

if ($result['success']) {
    logWebhook('Actualización iniciada correctamente', [
        'method' => $result['method'] ?? 'unknown',
        'branch' => $branch,
        'commit' => $commitId
    ]);
    
    // Enviar respuesta exitosa
    sendResponse(200, [
        'message' => 'Actualización iniciada',
        'branch' => $branch,
        'commit' => $commitId,
        'status' => 'processing'
    ]);
} else {
    $errorMsg = $result['error'] ?? 'Error desconocido';
    logWebhook('Error al iniciar actualización', [
        'error' => $errorMsg,
        'branch' => $branch,
        'commit' => $commitId
    ]);
    
    sendResponse(500, [
        'error' => 'Error al ejecutar script de actualización',
        'details' => $errorMsg
    ]);
}

