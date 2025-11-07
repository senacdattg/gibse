<?php
/**
 * Webhook para actualización automática desde Git
 * 
 * Configuración:
 * 1. En GitHub/GitLab: Settings → Webhooks → Add webhook
 * 2. URL: https://gibse.dataguaviare.com.co/webhooks/webhook.php
 * 3. Content type: application/json
 * 4. Secret: (configura WEBHOOK_SECRET en .env)
 * 5. Events: Solo "Push events"
 */

// Cargar variables de entorno
require_once __DIR__ . '/../config/env-loader.php';

// Obtener configuración desde .env
$SECRET = getEnvVar('WEBHOOK_SECRET', '');
$PROJECT_DIR = getEnvVar('PROJECT_DIR', '/var/www/cdattg_gibse');
$LOG_FILE = getEnvVar('LOG_FILE', $PROJECT_DIR . '/webhook.log');
$UPDATE_SCRIPT = getEnvVar('UPDATE_SCRIPT', $PROJECT_DIR . '/scripts/update.sh');
$GIT_BRANCH = getEnvVar('GIT_BRANCH', 'main');

// Función para escribir logs
function writeLog($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Obtener el payload
$payload = file_get_contents('php://input');
$headers = getallheaders();

writeLog("Webhook recibido");

// Verificar el secreto (GitHub/GitLab)
if (!empty($SECRET)) {
    if (isset($headers['X-Hub-Signature-256'])) {
        $signature = hash_hmac('sha256', $payload, $SECRET);
        $expected = 'sha256=' . $signature;
        
        if (!hash_equals($expected, $headers['X-Hub-Signature-256'])) {
            writeLog("ERROR: Firma inválida");
            http_response_code(403);
            die(json_encode(['error' => 'Invalid signature']));
        }
    } elseif (isset($headers['X-Gitlab-Token'])) {
        // Para GitLab
        if ($headers['X-Gitlab-Token'] !== $SECRET) {
            writeLog("ERROR: Token inválido");
            http_response_code(403);
            die(json_encode(['error' => 'Invalid token']));
        }
    }
}

// Verificar que es un push a la rama configurada
$data = json_decode($payload, true);
$expectedRef = 'refs/heads/' . $GIT_BRANCH;

if (isset($data['ref']) && $data['ref'] === $expectedRef) {
    writeLog("Push detectado en rama $GIT_BRANCH, iniciando actualización...");
    
    // Ejecutar actualización en segundo plano
    if (file_exists($UPDATE_SCRIPT) && is_executable($UPDATE_SCRIPT)) {
        $command = "cd $PROJECT_DIR && bash $UPDATE_SCRIPT >> $LOG_FILE 2>&1 &";
        exec($command);
        
        writeLog("Comando de actualización ejecutado");
        http_response_code(200);
        echo json_encode([
            'status' => 'success', 
            'message' => 'Update triggered',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        writeLog("ERROR: Script de actualización no encontrado o sin permisos");
        http_response_code(500);
        echo json_encode(['error' => 'Update script not found or not executable']);
    }
} else {
    $branch = $data['ref'] ?? 'unknown';
    $branchName = str_replace('refs/heads/', '', $branch);
    writeLog("Push ignorado - rama recibida: $branchName, esperada: $GIT_BRANCH");
    http_response_code(200);
    echo json_encode([
        'status' => 'ignored', 
        'message' => "Not a push to configured branch ($GIT_BRANCH)",
        'received_branch' => $branchName,
        'expected_branch' => $GIT_BRANCH
    ]);
}

