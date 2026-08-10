<?php
// PEGA AQUÍ EL ENDPOINT EXACTO COPIADO DESDE EL BOTÓN DE AWS
$host = 'aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com'; 

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
        json_encode($payload_array) 
    ]);
}
?>