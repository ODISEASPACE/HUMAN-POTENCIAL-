<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("
    SELECT sc.label as name, sc.max_level, 
           (1.0 / GREATEST(COUNT(sc.node_key) OVER(), 1)) as contribution_weight, 
           COALESCE(us.current_level, 0) as current_level
    FROM skills_catalog sc
    LEFT JOIN user_skills us ON sc.node_key = us.node_key AND us.user_id = ?
    WHERE sc.parent_key = ?
");
$stmt->execute([$user_id, $current_skill]);
$nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_percent = 0;
foreach ($nodos as $nodo) {
    if ($nodo['max_level'] > 0) {
        $aportado = ($nodo['current_level'] / $nodo['max_level']) * ($nodo['contribution_weight'] * 100);
        $total_percent += $aportado;
    }
}
$total_percent = round($total_percent, 1);
?>
<style>
    .presentation-card { padding: 20px; text-align: center; }
    .pres-title { font-family: 'Orbitron', sans-serif; font-size: 2.5rem; color: var(--theme-color); margin-bottom: 5px; text-transform: uppercase; }
    .pres-subtitle { color: var(--text-muted); margin-bottom: 30px; font-size: 1.1rem; }
    .stat-circle { width: 140px; height: 140px; border-radius: 50%; border: 6px solid var(--theme-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; font-size: 2.5rem; font-family: 'Orbitron', sans-serif; color: var(--text-main); font-weight: bold; background: var(--theme-light); }
    .progress-list { display: flex; flex-direction: column; gap: 20px; max-width: 500px; margin: 0 auto; text-align: left;}
    .pres-bar-bg { width: 100%; background: var(--border-color); height: 12px; border-radius: 6px; margin-top: 8px; overflow: hidden; }
    .pres-bar-fill { background: var(--theme-color); height: 100%; border-radius: 6px; }
    .pres-label { color: var(--text-main); font-weight: 600; display: flex; justify-content: space-between; }
</style>

<div class="presentation-card">
    <h1 class="pres-title"><?= htmlspecialchars($current_skill) ?></h1>
    <p class="pres-subtitle">Análisis de Dominio Actual</p>
    
    <div class="stat-circle">
        <?= $total_percent ?>%
    </div>

    <div class="progress-list">
        <?php if(empty($nodos)): ?>
            <p style="text-align:center; color: var(--text-muted);">Sin datos para proyectar.</p>
        <?php else: ?>
            <?php foreach($nodos as $nodo): 
                $porcentaje_individual = ($nodo['current_level'] / $nodo['max_level']) * 100;
            ?>
            <div>
                <div class="pres-label">
                    <span><?= htmlspecialchars($nodo['name']) ?></span>
                    <span><?= round($porcentaje_individual) ?>%</span>
                </div>
                <div class="pres-bar-bg">
                    <div class="pres-bar-fill" style="width: <?= $porcentaje_individual ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>