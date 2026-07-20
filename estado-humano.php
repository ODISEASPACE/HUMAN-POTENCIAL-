<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// 1. PROCESAR EL FORMULARIO (Guardar Estado y Entrada de Blog)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $psique = (int)$_POST['psique'];
    $pneuma = (int)$_POST['pneuma'];
    $soma = (int)$_POST['soma'];
    $pathos = (int)$_POST['pathos'];
    
    $virtues = trim($_POST['virtues']);
    $capacities = trim($_POST['capacities']);
    $goals = trim($_POST['goals']);
    
    $media_path = null;

    // Lógica para subir imágenes
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $upload_dir = 'uploads/human_state/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('hs_') . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_path = $target_file;
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO human_state (user_id, assessment_date, title, media_path, psique_score, pneuma_score, soma_score, pathos_score, virtues_notes, capacities_notes, goals_notes) 
            VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (user_id, assessment_date) 
            DO UPDATE SET 
                title = EXCLUDED.title,
                media_path = COALESCE(EXCLUDED.media_path, human_state.media_path),
                psique_score = EXCLUDED.psique_score,
                pneuma_score = EXCLUDED.pneuma_score,
                soma_score = EXCLUDED.soma_score,
                pathos_score = EXCLUDED.pathos_score,
                virtues_notes = EXCLUDED.virtues_notes,
                capacities_notes = EXCLUDED.capacities_notes,
                goals_notes = EXCLUDED.goals_notes
        ");
        $stmt->execute([$user_id, $title, $media_path, $psique, $pneuma, $soma, $pathos, $virtues, $capacities, $goals]);
        $mensaje = "<div class='msg-success'>Estado Humano actualizado y bitácora guardada.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='msg-error'>Error de sincronización: " . $e->getMessage() . "</div>";
    }
}

// 2. OBTENER EL HISTORIAL (Para el Feed y los valores por defecto)
$stmtHistory = $pdo->prepare("
    SELECT * FROM human_state 
    WHERE user_id = ? 
    ORDER BY assessment_date DESC 
    LIMIT 20
");
$stmtHistory->execute([$user_id]);
$historyFeed = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

// Extraer el estado más reciente para llenar los inputs y el radar
$currentState = $historyFeed[0] ?? [];

$v_title = $currentState['title'] ?? '';
$v_psique = $currentState['psique_score'] ?? 50;
$v_pneuma = $currentState['pneuma_score'] ?? 50;
$v_soma = $currentState['soma_score'] ?? 50;
$v_pathos = $currentState['pathos_score'] ?? 50;
$v_virtues = $currentState['virtues_notes'] ?? '';
$v_capacities = $currentState['capacities_notes'] ?? '';
$v_goals = $currentState['goals_notes'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado Humano | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-hover: #6B46C1; --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 30px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        .dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        
        /* Creador de Post y Sliders */
        .post-creator input[type="text"] { width: 100%; font-size: 1.2rem; font-weight: 600; border: none; border-bottom: 2px solid var(--border-color); padding: 10px 0; margin-bottom: 20px; outline: none; transition: 0.3s; }
        .post-creator input[type="text"]:focus { border-bottom-color: var(--accent); }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .slider-group label { display: flex; justify-content: space-between; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; }
        .slider-group input[type="range"] { width: 100%; height: 6px; border-radius: 5px; outline: none; -webkit-appearance: none; background: #E2E8F0; }
        .slider-group input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; border-radius: 50%; cursor: pointer; }
        
        .slider-psique::-webkit-slider-thumb { background: #ECC94B; }
        .slider-pneuma::-webkit-slider-thumb { background: #A0AEC0; }
        .slider-soma::-webkit-slider-thumb { background: #4299E1; }
        .slider-pathos::-webkit-slider-thumb { background: #F56565; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-main); }
        .form-group textarea { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; resize: vertical; min-height: 80px; outline: none; }
        .form-group textarea:focus { border-color: var(--accent); }
        
        .file-upload { display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--text-muted); cursor: pointer; font-weight: 600; padding: 10px 15px; border: 1px dashed var(--border-color); border-radius: 8px; transition: 0.3s; }
        .file-upload:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
        
        .btn-submit { background: var(--accent); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: var(--accent-hover); }
        
        /* Feed del Blog */
        .radar-container { position: relative; height: 250px; width: 100%; display: flex; justify-content: center; align-items: center; margin-bottom: 20px; }
        .feed-container { display: flex; flex-direction: column; gap: 20px; margin-top: 20px; }
        .feed-post { border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; }
        .feed-date { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; margin-bottom: 5px; }
        .feed-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 15px; }
        .feed-image { width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
        .feed-section h4 { font-size: 0.85rem; color: var(--accent); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .feed-section p { font-size: 0.95rem; line-height: 1.5; color: #4a5568; margin-bottom: 15px; }
        
        .feed-metrics { display: flex; flex-wrap: wrap; gap: 10px; font-size: 0.75rem; font-weight: 700; border-top: 1px solid var(--border-color); padding-top: 15px; }
        .badge { padding: 4px 10px; border-radius: 20px; }
        .badge-psique { background: rgba(236, 201, 75, 0.2); color: #B7791F; }
        .badge-pneuma { background: rgba(160, 174, 192, 0.2); color: #4A5568; }
        .badge-soma { background: rgba(66, 153, 225, 0.2); color: #2B6CB0; }
        .badge-pathos { background: rgba(245, 101, 101, 0.2); color: #C53030; }

        .msg-success, .msg-error { padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; }
        .msg-success { background: #C6F6D5; color: #276749; }
        .msg-error { background: #FED7D7; color: #C53030; }

        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-dash">
            <h1>Estado Humano</h1>
            <p>Tu bitácora profunda: Sincroniza métricas y documenta tu desarrollo personal.</p>
        </div>

        <?= $mensaje ?>

        <div class="dashboard-grid">
            <!-- COLUMNA IZQUIERDA: Creador de Post -->
            <div class="card">
                <form method="POST" action="" enctype="multipart/form-data" class="post-creator">
                    
                    <input type="text" name="title" placeholder="Título de tu actualización (Ej. Día de claridad mental...)" value="<?= htmlspecialchars($v_title) ?>" required>
                    
                    <div class="stats-grid">
                        <div class="slider-group">
                            <label style="color: #D69E2E;">Psique <span id="val_psique"><?= $v_psique ?></span></label>
                            <input type="range" name="psique" class="slider-psique" min="1" max="100" value="<?= $v_psique ?>" oninput="document.getElementById('val_psique').innerText = this.value">
                        </div>
                        <div class="slider-group">
                            <label style="color: #4299E1;">Soma <span id="val_soma"><?= $v_soma ?></span></label>
                            <input type="range" name="soma" class="slider-soma" min="1" max="100" value="<?= $v_soma ?>" oninput="document.getElementById('val_soma').innerText = this.value">
                        </div>
                        <div class="slider-group">
                            <label style="color: #718096;">Pneuma <span id="val_pneuma"><?= $v_pneuma ?></span></label>
                            <input type="range" name="pneuma" class="slider-pneuma" min="1" max="100" value="<?= $v_pneuma ?>" oninput="document.getElementById('val_pneuma').innerText = this.value">
                        </div>
                        <div class="slider-group">
                            <label style="color: #E53E3E;">Pathos <span id="val_pathos"><?= $v_pathos ?></span></label>
                            <input type="range" name="pathos" class="slider-pathos" min="1" max="100" value="<?= $v_pathos ?>" oninput="document.getElementById('val_pathos').innerText = this.value">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Virtudes</label>
                        <textarea name="virtues" placeholder="¿Qué virtudes ejercitaste o reflexionaste hoy?"><?= htmlspecialchars($v_virtues) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Capacidades</label>
                        <textarea name="capacities" placeholder="Nuevas habilidades, lecturas o conocimientos..."><?= htmlspecialchars($v_capacities) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Metas</label>
                        <textarea name="goals" placeholder="Progreso hacia tus objetivos, bloqueos o nuevos caminos..."><?= htmlspecialchars($v_goals) ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <label class="file-upload">
                            📸 Adjuntar Evidencia (Opcional)
                            <input type="file" name="media" accept="image/*" style="display: none;" onchange="alert('Archivo listo: ' + this.files[0].name)">
                        </label>
                        <button type="submit" class="btn-submit">Sincronizar Bitácora</button>
                    </div>
                </form>
            </div>

            <!-- COLUMNA DERECHA: Radar y Feed -->
            <div>
                <div class="card" style="margin-bottom: 30px;">
                    <h3 style="text-align:center; margin-bottom: 5px;">Balance del Núcleo</h3>
                    <div class="radar-container">
                        <canvas id="coreRadarChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h3>Registros Anteriores</h3>
                    <div class="feed-container">
                        <?php if(empty($historyFeed)): ?>
                            <p style="color: var(--text-muted); text-align: center;">No hay registros previos. Comienza tu bitácora hoy.</p>
                        <?php else: ?>
                            <?php foreach($historyFeed as $post): ?>
                                <div class="feed-post">
                                    <div class="feed-date"><?= date('d M, Y', strtotime($post['assessment_date'])) ?></div>
                                    <div class="feed-title"><?= htmlspecialchars($post['title'] ?? 'Actualización de Estado') ?></div>
                                    
                                    <?php if(!empty($post['media_path'])): ?>
                                        <img src="<?= htmlspecialchars($post['media_path']) ?>" class="feed-image" alt="Evidencia">
                                    <?php endif; ?>

                                    <?php if(!empty(trim($post['virtues_notes']))): ?>
                                        <div class="feed-section">
                                            <h4>Virtudes</h4>
                                            <p><?= nl2br(htmlspecialchars($post['virtues_notes'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty(trim($post['capacities_notes']))): ?>
                                        <div class="feed-section">
                                            <h4>Capacidades</h4>
                                            <p><?= nl2br(htmlspecialchars($post['capacities_notes'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty(trim($post['goals_notes']))): ?>
                                        <div class="feed-section">
                                            <h4>Metas</h4>
                                            <p><?= nl2br(htmlspecialchars($post['goals_notes'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="feed-metrics">
                                        <span class="badge badge-psique">Psi: <?= $post['psique_score'] ?>%</span>
                                        <span class="badge badge-pneuma">Pne: <?= $post['pneuma_score'] ?>%</span>
                                        <span class="badge badge-soma">Som: <?= $post['soma_score'] ?>%</span>
                                        <span class="badge badge-pathos">Pat: <?= $post['pathos_score'] ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('coreRadarChart');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Psique', 'Pneuma', 'Soma', 'Pathos'],
                datasets: [{
                    label: 'Balance Actual',
                    data: [<?= $v_psique ?>, <?= $v_pneuma ?>, <?= $v_soma ?>, <?= $v_pathos ?>],
                    backgroundColor: 'rgba(128, 90, 213, 0.2)',
                    borderColor: '#805AD5',
                    pointBackgroundColor: ['#ECC94B', '#A0AEC0', '#4299E1', '#F56565'],
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#805AD5',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: '#E2E8F0' },
                        grid: { color: '#E2E8F0' },
                        pointLabels: { font: { family: 'Inter', size: 12, weight: '600' }, color: '#4A5568' },
                        ticks: { display: false, min: 0, max: 100 }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>