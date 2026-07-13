<?php
session_start();
require '../db.php'; 

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 1;

try {
    if ($action === 'create') {
        $parent_key = $data['parent_key'];
        $name = trim($data['name']);
        $max = (int)($data['max_level'] ?? 10);

        if (empty($name)) throw new Exception("El nombre no puede estar vacío.");

        $stmt = $pdo->prepare("INSERT INTO specialization_nodes (parent_skill_key, name, max_level, contribution_weight, is_default, user_id) VALUES (?, ?, ?, 0.50, false, ?)");
        $stmt->execute([$parent_key, $name, $max, $user_id]);
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete') {
        $node_id = $data['node_id'];
        
        // Protegemos la eliminación: solo se borra si pertenece al usuario y NO es default
        $stmt = $pdo->prepare("DELETE FROM specialization_nodes WHERE id = ? AND user_id = ? AND is_default = false");
        $stmt->execute([$node_id, $user_id]);
        echo json_encode(['success' => true]);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}