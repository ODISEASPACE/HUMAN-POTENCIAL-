<?php
session_start();
require 'db.php';

// 1. Cabecera CRÍTICA para que JS sepa que la respuesta es un JSON puro
header('Content-Type: application/json'); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Función centralizada para el Ledger (La memoria inmutable de APH)
if (!function_exists('recordSymbioteLedger')) {
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
}

try {
    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO projects_items (user_id, title, category, description, status) 
            VALUES (?, ?, ?, ?, 'Activo') 
            RETURNING id
        ");
        $stmt->execute([$user_id, $data['title'], $data['category'], $data['description']]);
        
        $new_id = $stmt->fetchColumn(); 
        
        // REGISTRO EN LA MEMORIA INMUTABLE
        recordSymbioteLedger($pdo, $user_id, 'CREATE', 'projects_items', $new_id, [
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description']
        ]);
        
        echo json_encode(['status' => 'success', 'id' => $new_id]);
        
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM projects_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $user_id]);
        
        // REGISTRO EN LA MEMORIA INMUTABLE
        recordSymbioteLedger($pdo, $user_id, 'DELETE', 'projects_items', $data['id'], [
            'nota' => 'El nodo fue purgado del repositorio central.'
        ]);
        
        echo json_encode(['status' => 'success']);
        
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE projects_items SET title = ?, category = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['title'], $data['category'], $data['description'], $data['id'], $user_id]);
        
        // REGISTRO EN LA MEMORIA INMUTABLE
        recordSymbioteLedger($pdo, $user_id, 'UPDATE', 'projects_items', $data['id'], [
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description']
        ]);
        
        echo json_encode(['status' => 'success']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>