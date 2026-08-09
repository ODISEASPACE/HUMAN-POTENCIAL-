<?php
session_start();
require 'db.php';

// Seguridad: Solo el simbionte accede a este endpoint
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote']) || $_SESSION['is_symbiote'] !== true) {
    echo json_encode(['error' => 'Acceso denegado a la red neuronal.']);
    exit;
}

header('Content-Type: application/json');

// OPCIONAL: Si no usas composer/vlucas-phpdotenv, descomenta estas líneas para leer el .env nativamente
/*
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $envVars = parse_ini_file($envPath);
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}
*/

// Extraer clave (Asegúrate de que el nombre coincida exactamente con tu .env)
$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');

if (!$apiKey) {
    echo json_encode(['error' => 'No se encontró la clave de la API de Gemini en el entorno.']);
    exit;
}

$GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;

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

$contexto_base = "Eres el núcleo lógico del sistema APH. Tu objetivo es optimizar el neurodesarrollo y la productividad del usuario.
Estado actual: Psique {$state['psique_score']}, Soma {$state['soma_score']}.
Próximos eventos críticos: " . json_encode($events) . ".
Reglas: No sugerir tareas en el bloque de sueño (08:00-14:00). El trabajo operativo es de 01:00 a 07:00.";

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
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
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
$ai_text = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '{"response_msg": "Error cognitivo en la red Gemini."}';

echo json_encode(['status' => 'success', 'data' => json_decode($ai_text, true)]);
?>