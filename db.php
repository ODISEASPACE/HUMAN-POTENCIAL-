<?php
$host = '127.0.0.1'; // Si pgAdmin está en el mismo servidor EC2
$dbname = 'prod2';
$user = 'postgres';
$password = 'Limitless20xx';

$dsn = "pgsql:host=$host;port=5432;dbname=$dbname;";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión a la infraestructura: " . $e->getMessage());
}
function recordSymbioteLedger($pdo, $user_id, $action, $entity_type, $entity_id, $payload_array) {
    $stmt = $pdo->prepare("
        INSERT INTO symbiote_ledger (user_id, action, entity_type, entity_id, payload) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, 
        strtoupper($action), 
        $entity_type, 
        $entity_id, 
        json_encode($payload_array) // Convertimos el array a JSONB
    ]);
}
?>
