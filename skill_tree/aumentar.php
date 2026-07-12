<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

// Consulta real: Traer nodos de la rama actual y el progreso del usuario
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.max_level, sn.contribution_weight, 
           COALESCE(usn.current_level, 0) as current_level
    FROM specialization_nodes sn
    LEFT JOIN user_specialization_nodes usn ON sn.id = usn.node_id AND usn.user_id = ?
    WHERE sn.parent_skill_key = ?
");
$stmt->execute([$user_id, $current_skill]);
$nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .node-list { display: flex; flex-direction: column; gap: 15px; }
    .node-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid var(--border-color); border-radius: 8px; transition: 0.2s; }
    .node-item:hover { border-color: var(--theme-color); box-shadow: 0 5px 15px var(--theme-light); }
    .node-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: var(--text-main); }
    .node-info p { margin: 0; color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
    .node-info span { color: var(--theme-color); }
    .btn-invest { background: var(--theme-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1rem; transition: 0.2s; }
    .btn-invest:hover { opacity: 0.8; transform: scale(1.05); }
    .btn-invest:disabled { background: var(--border-color); color: var(--text-muted); cursor: not-allowed; transform: none; }
</style>

<div class="node-list">
    <?php if (empty($nodos)): ?>
        <p style="text-align:center; color: var(--text-muted); padding: 40px;">No hay nodos de especialización registrados en este nivel.</p>
    <?php else: ?>
        <?php foreach ($nodos as $nodo): 
            $isMaxed = $nodo['current_level'] >= $nodo['max_level'];
            $peso_porcentaje = ($nodo['contribution_weight'] * 100) . '%';
        ?>
            <div class="node-item">
                <div class="node-info">
                    <h3><?= htmlspecialchars($nodo['name']) ?></h3>
                    <p>Nivel actual: <span><?= $nodo['current_level'] ?>/<?= $nodo['max_level'] ?></span> | Peso en el núcleo: <?= $peso_porcentaje ?></p>
                </div>
                <button class="btn-invest" <?= $isMaxed ? 'disabled' : '' ?>>
                    <?= $isMaxed ? 'Al Máximo' : '+ Invertir' ?>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>