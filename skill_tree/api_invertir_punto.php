<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 1;
$data = json_decode(file_get_contents('php://input'), true);
$node_id = $data['node_id'] ?? null;

if (!$node_id) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener info del nodo
    $stmt = $pdo->prepare("SELECT parent_skill_key, max_level FROM specialization_nodes WHERE id = ?");
    $stmt->execute([$node_id]);
    $nodeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $parent_key = $nodeInfo['parent_skill_key'];

    // 2. Subir nivel del usuario en este nodo
    $stmt = $pdo->prepare("
        INSERT INTO user_specialization_nodes (user_id, node_id, current_level) 
        VALUES (?, ?, 1) 
        ON CONFLICT (user_id, node_id) 
        DO UPDATE SET current_level = LEAST(user_specialization_nodes.current_level + 1, EXCLUDED.current_level + 99)
    ");
    $stmt->execute([$user_id, $node_id]);

    // 3. RECALCULAR EL NIVEL DEL PADRE (Núcleo)
    $stmt = $pdo->prepare("
        SELECT sn.max_level, sn.contribution_weight, COALESCE(usn.current_level, 0) as current_level
        FROM specialization_nodes sn
        LEFT JOIN user_specialization_nodes usn ON sn.id = usn.node_id AND usn.user_id = ?
        WHERE sn.parent_skill_key = ?
    ");
    $stmt->execute([$user_id, $parent_key]);
    $all_nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_parent_progress = 0;
    foreach ($all_nodes as $n) {
        if ($n['max_level'] > 0) {
            // Formula: (Nivel Actual / Nivel Maximo) * Peso * 10 (Nivel maximo del padre)
            $aportado = ($n['current_level'] / $n['max_level']) * $n['contribution_weight'] * 10;
            $total_parent_progress += $aportado;
        }
    }
    
    // Redondear hacia abajo para el nivel del núcleo
    $new_parent_level = floor($total_parent_progress);

    // 4. Actualizar la tabla user_skills
    $stmt = $pdo->prepare("
        UPDATE user_skills SET current_level = ? WHERE user_id = ? AND node_key = ?
    ");
    $stmt->execute([$new_parent_level, $user_id, $parent_key]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>