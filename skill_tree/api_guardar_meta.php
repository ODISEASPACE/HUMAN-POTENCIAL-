<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 1;
$data = json_decode(file_get_contents('php://input'), true);

$skill_key = $data['skill_key'] ?? '';
$goal_text = $data['goal_text'] ?? '';

if (empty($skill_key) || empty(trim($goal_text))) {
    echo json_encode(['success' => false, 'error' => 'El texto no puede estar vacío']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO user_skill_goals (user_id, skill_key, goal_text) VALUES (?, ?, ?)");
if($stmt->execute([$user_id, $skill_key, trim($goal_text)])){
    $date = date('d M Y');
    echo json_encode(['success' => true, 'text' => htmlspecialchars(trim($goal_text)), 'date' => $date]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar en BD']);
}
?>