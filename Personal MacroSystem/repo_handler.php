<?php
session_start();
require '../db.php';

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
        // 1. Crear el nodo
        $stmt = $pdo->prepare("INSERT INTO projects_items (user_id, title, category, description, status) VALUES (?, ?, ?, ?, 'Activo')");
        $stmt->execute([$user_id, $data['title'], $data['category'], $data['description']]);
        $new_id = $pdo->lastInsertId();
        
        // 2. Registrar en Auditoría (Memoria del Sistema)
        $stmtLog = $pdo->prepare("INSERT INTO system_audit_logs (user_id, action_category, action_type, module_affected, description) VALUES (?, 'CRUD', 'CREATE', 'Repositorio Central', ?)");
        $stmtLog->execute([$user_id, "Creó el nodo '{$data['title']}' en la categoría {$data['category']}"]);
        
        echo json_encode(['status' => 'success', 'id' => $new_id]);
        
    } elseif ($action === 'delete') {
        // 1. Registrar en Auditoría ANTES de borrar (para tener el nombre o ID seguro)
        $stmtLog = $pdo->prepare("INSERT INTO system_audit_logs (user_id, action_category, action_type, module_affected, description) VALUES (?, 'CRUD', 'DELETE', 'Repositorio Central', ?)");
        $stmtLog->execute([$user_id, "Eliminó un nodo con ID: {$data['id']}"]);
        
        // 2. Eliminar el nodo
        $stmt = $pdo->prepare("DELETE FROM projects_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $user_id]);
        
        echo json_encode(['status' => 'success']);
        
    } elseif ($action === 'update') {
        // 1. Actualizar el nodo
        $stmt = $pdo->prepare("UPDATE projects_items SET title = ?, category = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['title'], $data['category'], $data['description'], $data['id'], $user_id]);
        
        // 2. Registrar en Auditoría
        $stmtLog = $pdo->prepare("INSERT INTO system_audit_logs (user_id, action_category, action_type, module_affected, description) VALUES (?, 'CRUD', 'UPDATE', 'Repositorio Central', ?)");
        $stmtLog->execute([$user_id, "Actualizó el nodo '{$data['title']}'"]);
        
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>