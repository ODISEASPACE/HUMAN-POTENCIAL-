<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

// Datos de Gráfica
$stmt = $pdo->prepare("
    SELECT sn.name, COALESCE(usn.current_level, 0) as level
    FROM specialization_nodes sn
    LEFT JOIN user_specialization_nodes usn ON sn.id = usn.node_id AND usn.user_id = ?
    WHERE sn.parent_skill_key = ?
");
$stmt->execute([$user_id, $current_skill]);
$nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = []; $data = [];
foreach ($nodos as $n) {
    $labels[] = $n['name'];
    $data[] = $n['level'];
}

// Metas
$stmt = $pdo->prepare("SELECT * FROM user_skill_goals WHERE user_id = ? AND skill_key = ? ORDER BY created_at DESC");
$stmt->execute([$user_id, $current_skill]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .rp-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    @media (max-width: 768px) { .rp-container { grid-template-columns: 1fr; } }
    .rp-panel { background: var(--bg-base); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
    .rp-panel h2 { font-size: 1.2rem; margin-bottom: 20px; color: var(--text-main); }
    .goal-input { width: 100%; height: 100px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; resize: none; margin-bottom: 15px; font-family: inherit; background: var(--bg-panel); color: var(--text-main); }
    .mod-btn-save { background: var(--theme-color); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .mod-btn-save:hover { opacity: 0.8; }
    .goal-list { margin-top: 25px; display: flex; flex-direction: column; gap: 10px; max-height: 250px; overflow-y: auto; }
    .goal-item { padding: 15px; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; color: var(--text-main); }
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
        <form id="goalForm" onsubmit="guardarMeta(event)">
            <input type="hidden" id="skillKey" value="<?= htmlspecialchars($current_skill) ?>">
            <textarea id="goalText" class="goal-input" placeholder="¿Cuál es tu próximo objetivo en este núcleo? Escribe tu meta o descubrimiento..." required></textarea>
            <button type="submit" class="mod-btn-save" id="btnSaveGoal">Registrar Meta</button>
        </form>

        <div class="goal-list" id="goalList">
            <?php foreach ($metas as $meta): ?>
                <div class="goal-item">
                    ⬜ <?= htmlspecialchars($meta['goal_text']) ?>
                    <span class="goal-date">Creado: <?= date('d M Y', strtotime($meta['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Inicializar ChartJS
    <?php if(!empty($labels)): ?>
        var ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Nivel Actual',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: 'rgba(128, 90, 213, 0.2)', 
                    borderColor: 'rgba(128, 90, 213, 1)',
                    borderWidth: 2
                }]
            },
            options: { scales: { r: { min: 0, max: 10 } } }
        });
    <?php endif; ?>

    // Petición AJAX para guardar la meta real
    function guardarMeta(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveGoal');
        const text = document.getElementById('goalText').value;
        const skill = document.getElementById('skillKey').value;

        btn.innerText = 'Guardando...';
        btn.disabled = true;

        fetch('api_guardar_meta.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ skill_key: skill, goal_text: text })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const list = document.getElementById('goalList');
                const newGoal = `<div class="goal-item">⬜ ${data.text}<span class="goal-date">Creado: ${data.date}</span></div>`;
                list.insertAdjacentHTML('afterbegin', newGoal);
                document.getElementById('goalText').value = '';
            } else {
                alert('Error: ' + data.error);
            }
        })
        .finally(() => {
            btn.innerText = 'Registrar Meta';
            btn.disabled = false;
        });
    }
</script>