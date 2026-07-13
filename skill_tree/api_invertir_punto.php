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

    // 1. Obtener información base del nodo
    $stmt = $pdo->prepare("SELECT parent_skill_key, max_level FROM specialization_nodes WHERE id = ?");
    $stmt->execute([$node_id]);
    $nodeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nodeInfo) throw new Exception("Nodo no encontrado");
    $parent_key = $nodeInfo['parent_skill_key'];

    // 2. Validar límites actuales del subnivel
    $stmt = $pdo->prepare("SELECT current_level FROM user_specialization_nodes WHERE user_id = ? AND node_id = ?");
    $stmt->execute([$user_id, $node_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_level = $current ? $current['current_level'] : 0;

    if ($current_level >= $nodeInfo['max_level']) throw new Exception("Nivel máximo alcanzado");
    
    $new_level = $current_level + 1;

    // Persistir el incremento del subnodo
    $stmt = $pdo->prepare("
        INSERT INTO user_specialization_nodes (user_id, node_id, current_level) 
        VALUES (?, ?, 1) 
        ON CONFLICT (user_id, node_id) 
        DO UPDATE SET current_level = user_specialization_nodes.current_level + 1
    ");
    $stmt->execute([$user_id, $node_id]);

    // 3. Algoritmo de Recálculo Dinámico Ponderado (Multiplicación por Peso Relativo)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(sn.max_level * sn.contribution_weight) as total_max_weighted, 
            SUM(COALESCE(usn.current_level, 0) * sn.contribution_weight) as total_current_weighted
        FROM specialization_nodes sn
        LEFT JOIN user_specialization_nodes usn ON sn.id = usn.node_id AND usn.user_id = ?
        WHERE sn.parent_skill_key = ? AND (sn.is_default = true OR sn.user_id = ?)
    ");
    $stmt->execute([$user_id, $parent_key, $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Proporción matemática real conseguida en la rama
    $ratio = ($stats['total_max_weighted'] > 0) ? ($stats['total_current_weighted'] / $stats['total_max_weighted']) : 0;

    // Obtener el tope máximo configurado para la habilidad núcleo en el catálogo
    $stmt = $pdo->prepare("SELECT max_level FROM skills_catalog WHERE node_key = ?");
    $stmt->execute([$parent_key]);
    $parentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $parent_max = $parentInfo ? $parentInfo['max_level'] : 10;

    // Cálculo del nivel maestro continuo (Guardado flotante para barras de progreso fluidas)
    $new_parent_level = $ratio * $parent_max;

    // Actualizar la tabla de habilidades núcleo con precisión decimal
    $stmt = $pdo->prepare("UPDATE user_skills SET current_level = ? WHERE user_id = ? AND node_key = ?");
    $stmt->execute([$new_parent_level, $user_id, $parent_key]);

    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'new_level' => $new_level,
        'is_maxed' => ($new_level >= $nodeInfo['max_level'])
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}