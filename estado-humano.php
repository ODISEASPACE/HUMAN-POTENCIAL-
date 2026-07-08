<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// 1. PROCESAR EL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $psique = (int)$_POST['psique'];
    $pneuma = (int)$_POST['pneuma'];
    $soma = (int)$_POST['soma'];
    $pathos = (int)$_POST['pathos'];
    
    $virtues = trim($_POST['virtues']);
    $capacities = trim($_POST['capacities']);
    $goals = trim($_POST['goals']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO human_state (user_id, assessment_date, psique_score, pneuma_score, soma_score, pathos_score, virtues_notes, capacities_notes, goals_notes) 
            VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (user_id, assessment_date) 
            DO UPDATE SET 
                psique_score = EXCLUDED.psique_score,
                pneuma_score = EXCLUDED.pneuma_score,
                soma_score = EXCLUDED.soma_score,
                pathos_score = EXCLUDED.pathos_score,
                virtues_notes = EXCLUDED.virtues_notes,
                capacities_notes = EXCLUDED.capacities_notes,
                goals_notes = EXCLUDED.goals_notes
        ");
        $stmt->execute([$user_id, $psique, $pneuma, $soma, $pathos, $virtues, $capacities, $goals]);
        $mensaje = "<div class='msg-success'>Estado Humano actualizado y sincronizado.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='msg-error'>Error de sincronización: " . $e->getMessage() . "</div>";
    }
}

// 2. DATOS DEL USUARIO (Sidebar)
$stmtUser = $pdo->prepare("SELECT username, profile_picture, profession FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// 3. OBTENER EL ESTADO ACTUAL (El más reciente)
$stmtState = $pdo->prepare("SELECT * FROM human_state WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1");
$stmtState->execute([$user_id]);
$currentState = $stmtState->fetch();

// Valores por defecto si es la primera vez
$v_psique = $currentState['psique_score'] ?? 50;
$v_pneuma = $currentState['pneuma_score'] ?? 50;
$v_soma = $currentState['soma_score'] ?? 50;
$v_pathos = $currentState['pathos_score'] ?? 50;

$v_virtues = $currentState['virtues_notes'] ?? '';
$v_capacities = $currentState['capacities_notes'] ?? '';
$v_goals = $currentState['goals_notes'] ?? '';

function renderAvatar($avatarData) {
    if (empty($avatarData)) return "<div class='avatar-circle' style='background: #E2E8F0; color: #4A5568;'>👤</div>";
    if (strpos($avatarData, '.') !== false) return "<div class='avatar-circle' style='background-image: url(\"{$avatarData}\"); background-size: cover;'></div>";
    return "<div class='avatar-circle' style='background: rgba(128, 90, 213, 0.1); color: #805AD5;'>{$avatarData}</div>";
}
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
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-light: rgba(128, 90, 213, 0.1); --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 10; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout { margin-top: 15px; text-align: center; font-size: 0.85rem; color: #E53E3E; text-decoration: none; font-weight: 600; padding: 8px; border-radius: 6px; }
        
        /* Main */
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 30px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        
        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: flex; justify-content: space-between; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; }
        .form-group textarea { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; resize: vertical; min-height: 70px; }
        .form-group textarea:focus { outline: none; border-color: var(--accent); }
        
        /* Sliders con los colores del MPV */
        .slider-group { margin-bottom: 15px; }
        .slider-group input[type="range"] { width: 100%; height: 6px; border-radius: 5px; outline: none; -webkit-appearance: none; background: #E2E8F0; }
        .slider-group input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border-radius: 50%; cursor: pointer; }
        
        .slider-psique::-webkit-slider-thumb { background: #ECC94B; } /* Amarillo */
        .slider-pneuma::-webkit-slider-thumb { background: #A0AEC0; } /* Gris/Blanco */
        .slider-soma::-webkit-slider-thumb { background: #4299E1; }   /* Azul */
        .slider-pathos::-webkit-slider-thumb { background: #F56565; } /* Rojo */
        
        .btn-submit { width: 100%; background: var(--accent); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: var(--accent-hover); }
        .msg-success { background: #C6F6D5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        
        .radar-container { position: relative; height: 350px; width: 100%; display: flex; justify-content: center; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="estado-humano.php" class="nav-link active">👤 Estado Humano</a>
            <a href="registro-diario.php" class="nav-link">⏱ Registro Diario</a>
            <a href="proyectos.php" class="nav-link">🚀 Proyectos</a>
        </div>
        <div class="user-mini">
            <?= renderAvatar($user['profile_picture']) ?>
            <div class="user-info-mini">
                <h4><?= htmlspecialchars($user['username'] ?? 'Usuario') ?></h4>
                <p><?= htmlspecialchars($user['profession'] ?? 'Sin asignar') ?></p>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </nav>

    <main>
        <div class="header-dash">
            <h1>Estado Humano (MPV)</h1>
            <p>Sincronización del núcleo interno y actualización de estructuras de desarrollo.</p>
        </div>

        <?= $mensaje ?>

        <div class="dashboard-grid">
            <div class="card">
                <h3 style="text-align:center; margin-bottom: 10px;">Balance del Núcleo</h3>
                <p style="text-align:center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Representación de tu equilibrio actual.</p>
                <div class="radar-container">
                    <canvas id="coreRadarChart"></canvas>
                </div>
                <div style="display: flex; justify-content: space-around; font-size: 0.8rem; font-weight: 600;">
                    <span style="color: #ECC94B;">Psique: <?= $v_psique ?>%</span>
                    <span style="color: #A0AEC0;">Pneuma: <?= $v_pneuma ?>%</span>
                </div>
                <div style="display: flex; justify-content: space-around; font-size: 0.8rem; font-weight: 600; margin-top: 10px;">
                    <span style="color: #4299E1;">Soma: <?= $v_soma ?>%</span>
                    <span style="color: #F56565;">Pathos: <?= $v_pathos ?>%</span>
                </div>
            </div>

            <div class="card">
                <h3>Actualizar Parámetros</h3>
                <form method="POST" action="">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <div class="form-group slider-group">
                                <label style="color: #D69E2E;">Psique (Mental) <span id="val_psique"><?= $v_psique ?></span></label>
                                <input type="range" name="psique" class="slider-psique" min="1" max="100" value="<?= $v_psique ?>" oninput="document.getElementById('val_psique').innerText = this.value">
                            </div>
                            <div class="form-group slider-group">
                                <label style="color: #4299E1;">Soma (Físico) <span id="val_soma"><?= $v_soma ?></span></label>
                                <input type="range" name="soma" class="slider-soma" min="1" max="100" value="<?= $v_soma ?>" oninput="document.getElementById('val_soma').innerText = this.value">
                            </div>
                        </div>
                        <div>
                            <div class="form-group slider-group">
                                <label style="color: #718096;">Pneuma (Espiritual) <span id="val_pneuma"><?= $v_pneuma ?></span></label>
                                <input type="range" name="pneuma" class="slider-pneuma" min="1" max="100" value="<?= $v_pneuma ?>" oninput="document.getElementById('val_pneuma').innerText = this.value">
                            </div>
                            <div class="form-group slider-group">
                                <label style="color: #E53E3E;">Pathos (Emocional) <span id="val_pathos"><?= $v_pathos ?></span></label>
                                <input type="range" name="pathos" class="slider-pathos" min="1" max="100" value="<?= $v_pathos ?>" oninput="document.getElementById('val_pathos').innerText = this.value">
                            </div>
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 20px;">

                    <div class="form-group">
                        <label>Virtudes</label>
                        <textarea name="virtues" placeholder="Desarrollo actual de virtudes..."><?= htmlspecialchars($v_virtues) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Capacidades</label>
                        <textarea name="capacities" placeholder="Nuevas habilidades adquiridas..."><?= htmlspecialchars($v_capacities) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Metas</label>
                        <textarea name="goals" placeholder="Objetivos a corto/mediano plazo..."><?= htmlspecialchars($v_goals) ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Sincronizar Estado</button>
                </form>
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