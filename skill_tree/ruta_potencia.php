<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

// 1. OBTENER COLOR DINÁMICO DE LA RAMA (Para que la gráfica combine con Finanzas, Estudio, etc.)
$branch_themes = [
    'estudio'  => '128, 90, 213',  // #805AD5 en RGB
    'laboral'  => '49, 130, 206',  // #3182CE en RGB
    'finanzas' => '214, 158, 46',  // #D69E2E en RGB
    'salud'    => '56, 161, 105',  // #38A169 en RGB
    'espiritu' => '213, 63, 140',  // #D53F8C en RGB
    'base'     => '74, 85, 104'    // #4A5568 en RGB
];

// Obtener la rama actual para sacar su color
$stmt = $pdo->prepare("SELECT branch FROM skills_catalog WHERE node_key = ?");
$stmt->execute([$current_skill]);
$branch_info = $stmt->fetch(PDO::FETCH_ASSOC);
$branch_name = $branch_info ? $branch_info['branch'] : 'base';
$rgb_color = $branch_themes[$branch_name] ?? $branch_themes['base'];

// 2. DATOS DE GRÁFICA
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

// 3. METAS
$stmt = $pdo->prepare("SELECT * FROM user_skill_goals WHERE user_id = ? AND skill_key = ? ORDER BY created_at DESC");
$stmt->execute([$user_id, $current_skill]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .rp-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    @media (max-width: 768px) { .rp-container { grid-template-columns: 1fr; } }
    .rp-panel { background: var(--bg-base); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
    .rp-panel h2 { font-size: 1.2rem; margin-bottom: 20px; color: var(--text-main); }
    
    /* Contenedor relativo para evitar que Chart.js se desborde en el modal */
    .chart-container { position: relative; height: 300px; width: 100%; display: flex; justify-content: center; align-items: center;}
    
    .goal-input { width: 100%; height: 100px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; resize: none; margin-bottom: 15px; font-family: inherit; background: var(--bg-panel); color: var(--text-main); }
    .mod-btn-save { background: var(--theme-color); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .mod-btn-save:hover { opacity: 0.8; }
    .goal-list { margin-top: 25px; display: flex; flex-direction: column; gap: 10px; max-height: 250px; overflow-y: auto; }
    .goal-item { padding: 15px; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; color: var(--text-main); border-left: 4px solid var(--theme-color);}
    .goal-date { font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 5px; }
</style>

<div class="rp-container">
    <div class="rp-panel">
        <h2>Evolución Multidimensional</h2>
        <div class="chart-container">
            <?php if(empty($labels)): ?>
                <p style="color:var(--text-muted); text-align:center;">Agrega nodos de especialización para generar la matriz de tu potencial.</p>
            <?php else: ?>
                <canvas id="radarChart"></canvas>
            <?php endif; ?>
        </div>
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
                    <?= htmlspecialchars($meta['goal_text']) ?>
                    <span class="goal-date">Registrado el: <?= date('d M Y', strtotime($meta['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Inicialización de la Gráfica usando los colores dinámicos de PHP
    <?php if(!empty($labels)): ?>
        // Pequeño timeout para asegurar que el DOM del modal se renderizó completamente antes de pintar el canvas
        setTimeout(() => {
            var ctx = document.getElementById('radarChart').getContext('2d');
            var rgbColor = '<?= $rgb_color ?>'; // Toma el RGB dinámico

            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                        label: 'Nivel Actual',
                        data: <?= json_encode($data) ?>,
                        backgroundColor: `rgba(${rgbColor}, 0.3)`, 
                        borderColor: `rgba(${rgbColor}, 1)`,
                        pointBackgroundColor: `rgba(${rgbColor}, 1)`,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: `rgba(${rgbColor}, 1)`,
                        borderWidth: 2
                    }]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        r: { 
                            min: 0, 
                            max: 10,
                            ticks: { stepSize: 2, backdropColor: 'transparent' }
                        } 
                    },
                    plugins: {
                        legend: { display: false } // Oculta la leyenda superior para ahorrar espacio en el modal
                    }
                }
            });
        }, 150); // 150ms es suficiente para que el modal termine su animación de entrada
    <?php endif; ?>

    // Petición AJAX para guardar la meta
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
                const newGoal = `<div class="goal-item">${data.text}<span class="goal-date">Registrado el: ${data.date}</span></div>`;
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