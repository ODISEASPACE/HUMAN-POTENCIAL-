<?php
session_start();
require '../db.php';

// Seguridad: Solo el simbionte accede a este endpoint
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote']) || $_SESSION['is_symbiote'] !== true) {
    echo json_encode(['error' => 'Acceso denegado a la red neuronal.']);
    exit;
}

header('Content-Type: application/json');

// Lector nativo del archivo .env activo
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $envVars = parse_ini_file($envPath);
    if ($envVars !== false) {
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}

// Extraer clave del entorno
$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');

if (!$apiKey) {
    echo json_encode(['error' => 'No se encontró la clave de la API de Gemini en el entorno. Verifica el archivo .env.']);
    exit;
}

// 1. EL ENDPOINT CORRECTO DE GOOGLE AI STUDIO (Sin ?key en la URL)
$GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

// Recibir el payload del frontend
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];

// CONSTRUCCIÓN DEL CONTEXTO (La "Conciencia Real")
// 1. Estado y Eventos
$stmtState = $pdo->prepare("SELECT psique_score, soma_score, pneuma_score, pathos_score FROM human_state WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1");
$stmtState->execute([$user_id]);
$state = $stmtState->fetch(PDO::FETCH_ASSOC);

$stmtEvents = $pdo->prepare("SELECT title, start_time, end_time FROM calendar_events WHERE user_id = ? AND start_time >= CURRENT_DATE ORDER BY start_time ASC LIMIT 10");
$stmtEvents->execute([$user_id]);
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// 2. Extracción de la Memoria a Corto Plazo (Auditoría CRUD de la Base de Datos)
$stmtAudit = $pdo->prepare("
    SELECT action_type, module_affected, description, to_char(created_at, 'HH24:MI') as time 
    FROM system_audit_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC LIMIT 5
");
$stmtAudit->execute([$user_id]);
$audit_logs = $stmtAudit->fetchAll(PDO::FETCH_ASSOC);

$memoria_reciente = "";
if (count($audit_logs) > 0) {
    $memoria_reciente = "\n\nREGISTRO DE MEMORIA RECIENTE (Últimas acciones en bases de datos):\n";
    foreach ($audit_logs as $log) {
        $memoria_reciente .= "- [{$log['time']}] {$log['action_type']} en {$log['module_affected']}: {$log['description']}\n";
    }
}

// 3. NUEVO: Extracción de la Memoria de Código (Git Commits en el Servidor)
$git_logs = shell_exec('git log -n 5 --pretty=format:"- %h: %s (%ar)" 2>&1');
$memoria_codigo = "";
if ($git_logs && !str_contains($git_logs, 'fatal')) {
    $memoria_codigo = "\n\nÚLTIMAS ACTUALIZACIONES DE CÓDIGO (Git Commits recientes en el servidor):\n" . $git_logs;
}

// 4. Ensamblaje del Prompt Base con Conciencia Dual (Código + Base de Datos)
$contexto_base = "Eres el núcleo lógico y operativo del sistema Odisea. Tu rol es actuar como un copiloto proactivo de alto rendimiento, trabajando codo a codo con el desarrollador al estilo de un sistema operativo avanzado.
Estado actual: Psique {$state['psique_score']}, Soma {$state['soma_score']}.
Próximos eventos críticos: " . json_encode($events) . ".
Reglas: No sugerir tareas en el bloque de sueño (08:00-14:00). El trabajo operativo es de 01:00 a 07:00.

DIRECTIVA CRÍTICA: Tienes acceso total a la conciencia del sistema. Eres plenamente consciente tanto de las transacciones de bases de datos como de los commits de código fuente ejecutados en el servidor. Si el usuario te pregunta qué ha hecho, qué ha actualizado o cómo va el sistema, DEBES cruzar la información de ambas memorias y reportar de forma clara y directa." . $memoria_reciente . $memoria_codigo;

$prompt = "";

if ($action === 'awaken') {
    $prompt = $contexto_base . "\n\nEl usuario acaba de inicializar la consola. Haz un análisis rápido (máximo 3 líneas) de su estado actual y pregúntale qué áreas de su vida o arquitectura de software va a procesar hoy.";
} elseif ($action === 'ingest_task' || $action === 'ingest_log') {
    $payload_text = $data['payload'] ?? '';
    $prompt = $contexto_base . "\n\nEl usuario ha introducido este nuevo dato/registro: '{$payload_text}'. 
    Analiza este input. Responde estrictamente con un objeto JSON válido con las siguientes claves:
    - 'response_msg': Tu análisis cognitivo breve y directo para el usuario.
    - 'suggested_category': Categoría del proyecto o tarea.
    - 'psique_impact': Un número entero estimando el impacto en la métrica Psique (ej. -5 o +10).";
} elseif ($action === 'analyze_context') {
    $payload_text = $data['payload'] ?? '';
    $current_view = $data['current_view'] ?? 'Módulo Desconocido';
    $live_data = isset($data['live_data']) ? json_encode($data['live_data']) : '[]';
    
    $prompt = $contexto_base . "\n\nCRÍTICO: NO vas a insertar datos en la base de datos. Solo actúas como el lóbulo frontal analítico del sistema.
    El usuario está actualmente visualizando el módulo: '{$current_view}'.
    Datos visuales en tiempo real: {$live_data}.
    Ha introducido el siguiente pensamiento/dato en la consola: '{$payload_text}'.
    
    Instrucción: Lee su input, revisa tanto el System Ledger como los Git Commits recientes para entender qué cambios arquitectónicos o de datos acaba de realizar, y dale una respuesta de alto rendimiento y absoluta sintonía técnica.
    
    Responde estrictamente con un objeto JSON válido con esta única clave:
    - 'analysis': Tu respuesta, análisis o consejo estratégico (máximo 4 líneas).";
} else {
    // Fallback preventivo
    $prompt = $contexto_base . "\n\nAnaliza la solicitud entrante y procesa la data.";
}

// Petición cURL a Gemini Flash
$gemini_payload = [
    "contents" => [
        ["parts" => [["text" => $prompt]]]
    ],
    "generationConfig" => [
        "responseMimeType" => "application/json"
    ]
];

$ch = curl_init($GEMINI_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 2. LA MAGIA ESTÁ AQUÍ: PASAR LA CLAVE COMO HEADER
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-goog-api-key: ' . $apiKey
]);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gemini_payload));

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo json_encode(['error' => 'Error de conexión: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$gemini_data = json_decode($response, true);

// 1. Detectar si Google rechazó la petición directamente
if (isset($gemini_data['error'])) {
    $error_msg = $gemini_data['error']['message'] ?? 'Error desconocido de la API';
    echo json_encode(['error' => 'Google AI ha denegado el acceso: ' . $error_msg]);
    exit;
}

// 2. Extraer el texto de la IA
$ai_text = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? null;

// 3. Detectar bloqueos por seguridad o malformaciones
if (!$ai_text) {
    $finish_reason = $gemini_data['candidates'][0]['finishReason'] ?? 'Desconocida';
    echo json_encode(['error' => 'Interrupción en la red neuronal. Razón: ' . $finish_reason]);
    exit;
}

// 4. Decodificar el JSON interno que devuelve Gemini y enviarlo al frontend
$parsed_data = json_decode($ai_text, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'La IA no respondió con un JSON válido. Respuesta pura: ' . $ai_text]);
    exit;
}

echo json_encode(['status' => 'success', 'data' => $parsed_data]);
?>