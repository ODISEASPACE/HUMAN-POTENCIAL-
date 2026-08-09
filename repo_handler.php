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

try {
    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO projects_items (user_id, title, category, description, status) 
            VALUES (?, ?, ?, ?, 'Activo') 
            RETURNING id
        ");
        $stmt->execute([$user_id, $data['title'], $data['category'], $data['description']]);
        
        $new_id = $stmt->fetchColumn(); 
        
        echo json_encode(['status' => 'success', 'id' => $new_id]);
        
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM projects_items WHERE id = ? AND user_id = ?");
        // 2. Forzamos (int) para evitar errores de sintaxis en PostgreSQL
        $stmt->execute([(int)$data['id'], $user_id]);
        echo json_encode(['status' => 'success']);
        
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE projects_items SET title = ?, category = ?, description = ? WHERE id = ? AND user_id = ?");
        // 2. Forzamos (int) aquí también
        $stmt->execute([$data['title'], $data['category'], $data['description'], (int)$data['id'], $user_id]);
        echo json_encode(['status' => 'success']);
    }
} catch (PDOException $e) {
    // 3. Capturamos excepciones exclusivas de Base de Datos para debug
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
