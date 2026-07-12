<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

// 1. Datos para la gráfica (Los mismos nodos, pero preparamos arrays para Chart.js)
$stmt_nodes = $pdo->prepare("
    SELECT sn.name, COALESCE(usn.current_level, 0) as level
    FROM specialization_nodes sn
    LEFT JOIN user_specialization_nodes usn ON sn.id = usn.node_id AND usn.user_id = ?
    WHERE sn.parent_skill_key = ?
");
$stmt_nodes->execute([$user_id, $current_skill]);
$nodos_grafica = $stmt_nodes->fetchAll(PDO::FETCH_ASSOC);

$labels = []; $data = [];
foreach ($nodos_grafica as $n) {
    $labels[] = $n['name'];
    $data[] = $n['level'];
}

// 2. Traer las metas del usuario desde la BD
$stmt_metas = $pdo->prepare("SELECT * FROM user_skill_goals WHERE user_id = ? AND skill_key = ? ORDER BY created_at DESC");
$stmt_metas->execute([$user_id, $current_skill]);
$metas = $stmt_metas->fetchAll(PDO::FETCH_ASSOC);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .rp-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    @media (max-width: 768px) { .rp-container { grid-template-columns: 1fr; } }
    .rp-panel { background: var(--bg-base); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
    .rp-panel h2 { font-size: 1.2rem; margin-bottom: 20px; color: var(--text-main); }
    .goal-input { width: 100%; height: 100px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; resize: none; margin-bottom: 15px; font-family: inherit; background: var(--bg-panel); color: var(--text-main); }
    .btn-save { background: var(--theme-color); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .btn-save:hover { opacity: 0.8; }
    .goal-list { margin-top: 25px; display: flex; flex-direction: column; gap: 10px; }
    .goal-item { padding: 15px; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; }
    .goal-date { font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 5px; }
</style>

<div class="rp-container">
    <div class="rp-panel">
        <h2>Evolución Multidimensional</h2>
        <?php if(empty($labels)): ?>
            <p style="color:var(--text-muted); text-align:center;">Agrega nodos para ver la gráfica.</p>
        <?php else: ?>
            <canvas id="radarChart"></canvas>
        <?php endif; ?>
    </div>

    <div class="rp-panel">
        <h2>Registro Estratégico</h2>
        <form onsubmit="event.preventDefault(); alert('Aquí programarías un fetch() en JS para guardar la meta en user_skill_goals');">
            <textarea class="goal-input" placeholder="¿Cuál es tu próximo objetivo en este núcleo? Escribe tu meta o descubrimiento..."></textarea>
            <button type="submit" class="btn-save">Registrar Meta</button>
        </form>

        <div class="goal-list">
            <?php if (empty($metas)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">No hay metas activas.</p>
            <?php else: ?>
                <?php foreach ($metas as $meta): ?>
                    <div class="goal-item">
                        ⬜ <?= htmlspecialchars($meta['goal_text']) ?>
                        <span class="goal-date">Creado: <?= date('d M Y', strtotime($meta['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Inicializar gráfica dinámica con datos de PHP
    <?php if(!empty($labels)): ?>
    setTimeout(() => {
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Nivel Actual',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: 'rgba(128, 90, 213, 0.2)', // Ideal usar variable CSS pero Chartjs requiere RGBA
                    borderColor: 'rgba(128, 90, 213, 1)',
                    borderWidth: 2
                }]
            },
            options: { scales: { r: { min: 0, max: 10 } } }
        });
    }, 100); // Pequeño retraso para asegurar que el DOM del modal cargó
    <?php endif; ?>
</script>