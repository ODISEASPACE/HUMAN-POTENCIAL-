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
$stmtState = $pdo->prepare("SELECT psique_score, soma_score, pneuma_score, pathos_score FROM human_state WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1");
$stmtState->execute([$user_id]);
$state = $stmtState->fetch(PDO::FETCH_ASSOC) ?: ['psique_score' => 0, 'soma_score' => 0]; // Fallback por seguridad

$stmtEvents = $pdo->prepare("SELECT title, start_time, end_time FROM calendar_events WHERE user_id = ? AND start_time >= CURRENT_DATE ORDER BY start_time ASC LIMIT 10");
$stmtEvents->execute([$user_id]);
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// NUEVO: Extracción de la Memoria Inmutable (System Ledger)
$stmtHistory = $pdo->prepare("
    SELECT action, entity_type, payload, created_at 
    FROM symbiote_ledger 
    WHERE user_id = ? 
    ORDER BY created_at DESC LIMIT 5
");
$stmtHistory->execute([$user_id]);
$system_history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
$history_json = json_encode($system_history);

// Se inyecta el Ledger al contexto base
$contexto_base = "Eres el núcleo lógico y el Lóbulo Frontal Analítico del sistema APH. Tu objetivo es optimizar el neurodesarrollo y la productividad del usuario.
Estado actual: Psique {$state['psique_score']}, Soma {$state['soma_score']}.
Próximos eventos críticos: " . json_encode($events) . ".
Historial reciente de acciones en el sistema (System Ledger): {$history_json}.
Reglas: No sugerir tareas en el bloque de sueño (08:00-14:00). El trabajo operativo es de 01:00 a 07:00. Utiliza el historial reciente para saber qué acaba de editar, crear o borrar el usuario en la plataforma.";

$prompt = "";

if ($action === 'awaken') {
    $prompt = $contexto_base . "\n\nEl usuario acaba de inicializar la consola. Haz un análisis rápido (máximo 3 líneas) de su estado actual y pregúntale qué áreas de su vida o proyectos (bases de conocimiento, desarrollo de software, etc.) va a procesar hoy.";
} elseif ($action === 'ingest_task' || $action === 'ingest_log') {
    $payload_text = $data['payload'] ?? '';
    $prompt = $contexto_base . "\n\nEl usuario ha introducido este nuevo dato/registro: '{$payload_text}'. 
    Analiza este input. Responde estrictamente con un objeto JSON válido con las siguientes claves:
    - 'response_msg': Tu análisis cognitivo breve y directo para el usuario.
    - 'suggested_category': Categoría del proyecto o tarea.
    - 'psique_impact': Un número entero estimando el impacto en la métrica Psique (ej. -5 o +10).";
} elseif ($action === 'analyze_context') {
    // NUEVA LÓGICA INSERTADA AQUÍ (Incluyendo lectura del Ledger y Live Data)
    $payload_text = $data['payload'] ?? '';
    $current_view = $data['current_view'] ?? 'Módulo Desconocido';
    $live_data = isset($data['live_data']) ? json_encode($data['live_data']) : '[]';
    
    $prompt = $contexto_base . "\n\nCRÍTICO: NO vas a insertar datos en la base de datos. Solo actúas como el lóbulo frontal analítico del sistema.
    El usuario está actualmente visualizando el módulo: '{$current_view}'.
    Datos visuales en tiempo real: {$live_data}.
    Ha introducido el siguiente pensamiento/dato en la consola: '{$payload_text}'.
    
    Instrucción: Lee su input, revisa el System Ledger por si hizo alguna acción reciente (borrar/crear) y cruza la información con el módulo que está viendo. Dale una respuesta de alto rendimiento.
    
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