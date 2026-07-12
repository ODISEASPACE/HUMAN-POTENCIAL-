<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

// Consulta blindada apuntando a skills_catalog y user_skills
$stmt = $pdo->prepare("
    SELECT sc.node_key as id, sc.label as name, sc.max_level,
           (1.0 / GREATEST(COUNT(sc.node_key) OVER(), 1)) as contribution_weight, 
           COALESCE(us.current_level, 0) as current_level
    FROM skills_catalog sc
    LEFT JOIN user_skills us ON sc.node_key = us.node_key AND us.user_id = ?
    WHERE sc.parent_key = ?
    ORDER BY sc.label ASC
");
$stmt->execute([$user_id, $current_skill]);
$nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .mod-node-list { display: flex; flex-direction: column; gap: 15px; }
    .mod-node-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid var(--border-color); border-radius: 8px; }
    .mod-node-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: var(--text-main); }
    .mod-node-info p { margin: 0; color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
    .mod-btn-invest { background: var(--theme-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1rem; transition: 0.2s; }
    .mod-btn-invest:hover { opacity: 0.8; transform: scale(1.05); }
    .mod-btn-invest:disabled { background: var(--border-color); color: var(--text-muted); cursor: not-allowed; transform: none; }
</style>

<div class="mod-node-list">
    <?php if (empty($nodos)): ?>
        <p style="text-align:center; color: var(--text-muted);">No hay nodos configurados para esta rama.</p>
    <?php else: ?>
        <?php foreach ($nodos as $nodo): 
            $isMaxed = $nodo['current_level'] >= $nodo['max_level'];
            $peso = round($nodo['contribution_weight'] * 100) . '%';
        ?>
            <div class="mod-node-item">
                <div class="mod-node-info">
                    <h3><?= htmlspecialchars($nodo['name']) ?></h3>
                    <p>Nivel actual: <span style="color: var(--theme-color);"><?= $nodo['current_level'] ?>/<?= $nodo['max_level'] ?></span> | Peso: <?= $peso ?></p>
                </div>
                <button class="mod-btn-invest" onclick="invertirPuntos('<?= htmlspecialchars($nodo['id'], ENT_QUOTES) ?>', this)" <?= $isMaxed ? 'disabled' : '' ?>>
                    <?= $isMaxed ? 'Maxed' : '+ Invertir' ?>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function invertirPuntos(nodeId, btnElement) {
    btnElement.innerHTML = '...';
    btnElement.disabled = true;

    fetch('api_invertir_punto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ node_id: nodeId })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.location.reload(); 
        } else {
            alert('Error: ' + data.error);
            btnElement.innerHTML = '+ Invertir';
            btnElement.disabled = false;
        }
    })
    .catch(err => {
        alert("Error de red.");
        btnElement.innerHTML = '+ Invertir';
        btnElement.disabled = false;
    });
}
</script>