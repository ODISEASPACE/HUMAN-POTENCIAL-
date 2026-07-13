<?php
session_start();
require '../db.php'; 

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$node_id = $data['node_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? 1;

if (!$node_id) { echo json_encode(['success' => false, 'error' => 'ID no válido']); exit; }

try {
    // 1. Obtener límite del nodo
    $stmt = $pdo->prepare("SELECT max_level FROM specialization_nodes WHERE id = ?");
    $stmt->execute([$node_id]);
    $node = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$node) { echo json_encode(['success' => false, 'error' => 'Nodo inexistente']); exit; }

    // 2. Obtener nivel actual
    $stmt = $pdo->prepare("SELECT current_level FROM user_specialization_nodes WHERE user_id = ? AND node_id = ?");
    $stmt->execute([$user_id, $node_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_level = $current ? $current['current_level'] : 0;

    if ($current_level >= $node['max_level']) {
        echo json_encode(['success' => false, 'error' => 'Ya estás al nivel máximo']); exit;
    }

    // 3. Subir el punto
    $new_level = $current_level + 1;
    $stmt = $pdo->prepare("
        INSERT INTO user_specialization_nodes (user_id, node_id, current_level)
        VALUES (?, ?, 1)
        ON CONFLICT (user_id, node_id) 
        DO UPDATE SET current_level = user_specialization_nodes.current_level + 1
    ");
    $stmt->execute([$user_id, $node_id]);

    // 4. Responder con los nuevos datos
    echo json_encode([
        'success' => true,
        'new_level' => $new_level,
        'is_maxed' => ($new_level >= $node['max_level'])
    ]);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
}