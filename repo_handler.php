<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO projects_items (user_id, title, category, description, status) VALUES (?, ?, ?, ?, 'Activo')");
        $stmt->execute([$user_id, $data['title'], $data['category'], $data['description']]);
        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
        
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM projects_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $user_id]);
        echo json_encode(['status' => 'success']);
        
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE projects_items SET title = ?, category = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['title'], $data['category'], $data['description'], $data['id'], $user_id]);
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>