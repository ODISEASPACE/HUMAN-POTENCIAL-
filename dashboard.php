<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. DATOS DEL USUARIO
$stmtUser = $pdo->prepare("SELECT username, profile_picture, profession FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// 2. DATOS: ESTADO HUMANO (Último registro)
$stmtState = $pdo->prepare("SELECT * FROM human_state WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1");
$stmtState->execute([$user_id]);
$h_state = $stmtState->fetch();

$radar_data = [
    $h_state['psique_score'] ?? 50,
    $h_state['pneuma_score'] ?? 50,
    $h_state['soma_score'] ?? 50,
    $h_state['pathos_score'] ?? 50
];

// 3. DATOS: REGISTRO DIARIO (Últimos 7 días para gráficas)
$stmtLogs = $pdo->prepare("
    SELECT to_char(log_date, 'DD/MM') as f_date, routines_completed, mood_score, health_score 
    FROM daily_logs 
    WHERE user_id = ? 
    ORDER BY log_date ASC 
    LIMIT 7
");
$stmtLogs->execute([$user_id]);
$logs = $stmtLogs->fetchAll();

// 4. DATOS: PROYECTOS (Conteo por categorías)
$stmtProj = $pdo->prepare("SELECT category, COUNT(*) as total, SUM(CASE WHEN status = 'Completado' THEN 1 ELSE 0 END) as completados FROM projects_items WHERE user_id = ? GROUP BY category");
$stmtProj->execute([$user_id]);
$projectsData = $stmtProj->fetchAll(PDO::FETCH_KEY_PAIR); // Crea un array asociativo [categoria => total]

// Preparar arrays para Chart.js
$log_labels = json_encode(array_column($logs, 'f_date'));
$log_mood = json_encode(array_column($logs, 'mood_score'));
$log_health = json_encode(array_column($logs, 'health_score'));
$log_routines = json_encode(array_column($logs, 'routines_completed'));

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
    <title>Dashboard | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-light: rgba(128, 90, 213, 0.1); --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 10; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout { margin-top: 15px; text-align: center; font-size: 0.85rem; color: #E53E3E; text-decoration: none; font-weight: 600; padding: 8px; border-radius: 6px; }
        
        /* Main Dashboard */
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header-dash { margin-bottom: 40px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); display: flex; flex-direction: column; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .card-header h3 { font-size: 1.2rem; font-weight: 700; }
        
        /* Gráficos Carrusel */
        .graph-carousel { position: relative; height: 300px; width: 100%; }
        .graph-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease; display: flex; flex-direction: column; pointer-events: none; }
        .graph-slide.active { opacity: 1; z-index: 2; pointer-events: auto; }
        .graph-title { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 15px; text-align: center; }
        .canvas-container { position: relative; height: 250px; width: 100%; }

        /* Widgets */
        .widget-controls { display: flex; gap: 10px; margin-bottom: 20px; background: var(--bg-base); padding: 5px; border-radius: 10px; border: 1px solid var(--border-color); }
        .widget-btn { flex: 1; background: transparent; border: none; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; transition: 0.3s; }
        .widget-btn:hover { color: var(--text-main); }
        .widget-btn.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .widget-panel { display: none; animation: fadeIn 0.4s ease forwards; }
        .widget-panel.active { display: block; }
        
        .metric-list { list-style: none; display: flex; flex-direction: column; gap: 15px; }
        .metric-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--border-color); border-radius: 10px; }
        .metric-info h4 { font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; color: var(--text-main); }
        .metric-info p { font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 250px; }
        .metric-status { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: var(--accent-light); color: var(--accent); white-space: nowrap; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link active">⌂ Panel Central</a>
            <a href="estado-humano.php" class="nav-link">👤 Estado Humano</a>
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
            <h1>Sistemas Activos</h1>
            <p>Resumen de rendimiento y módulos de desarrollo en tiempo real.</p>
        </div>

        <div class="dashboard-grid">
            
            <div class="card">
                <div class="card-header">
                    <h3>Métricas Generales</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Rotación 5s</span>
                </div>
                
                <div class="graph-carousel">
                    <div class="graph-slide active" id="slide-0">
                        <div class="graph-title">Proyección de Rendimiento (7 Días)</div>
                        <div class="canvas-container"><canvas id="chartProjection"></canvas></div>
                    </div>
                    
                    <div class="graph-slide" id="slide-1">
                        <div class="graph-title">Balance del Núcleo Interno (MPV)</div>
                        <div class="canvas-container"><canvas id="chartRadar"></canvas></div>
                    </div>
                    
                    <div class="graph-slide" id="slide-2">
                        <div class="graph-title">Rutinas Completadas</div>
                        <div class="canvas-container"><canvas id="chartHabits"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>SubContenido APH</h3>
                </div>
                
                <div class="widget-controls">
                    <button class="widget-btn active" onclick="showWidget(0, this)">Estado Humano</button>
                    <button class="widget-btn" onclick="showWidget(1, this)">Registro Diario</button>
                    <button class="widget-btn" onclick="showWidget(2, this)">Proyectos</button>
                </div>

                <div class="widget-content-area">
                    <div class="widget-panel active" id="widget-0">
                        <ul class="metric-list">
                            <li class="metric-item">
                                <div class="metric-info"><h4>Virtudes</h4>
                                <p><?= htmlspecialchars($h_state['virtues_notes'] ?? 'Sin registros aún.') ?></p></div>
                                <div class="metric-status">Actualizado</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Capacidades</h4>
                                <p><?= htmlspecialchars($h_state['capacities_notes'] ?? 'Sin registros aún.') ?></p></div>
                                <div class="metric-status" style="background:#C6F6D5; color:#276749;">Óptimo</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Metas</h4>
                                <p><?= htmlspecialchars($h_state['goals_notes'] ?? 'Sin registros aún.') ?></p></div>
                                <div class="metric-status" style="background:#FEEBC8; color:#C05621;">En progreso</div>
                            </li>
                        </ul>
                    </div>

                    <?php $last_log = end($logs); ?>
                    <div class="widget-panel" id="widget-1">
                        <ul class="metric-list">
                            <li class="metric-item">
                                <div class="metric-info"><h4>Último Ánimo (Psique)</h4><p>Puntuación general del día</p></div>
                                <div class="metric-status"><?= $last_log ? $last_log['mood_score'].'/10' : 'N/A' ?></div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Salud y Energía (Soma)</h4><p>Estado físico registrado</p></div>
                                <div class="metric-status" style="background:#C6F6D5; color:#276749;"><?= $last_log ? $last_log['health_score'].'/10' : 'N/A' ?></div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Rutinas</h4><p>Tareas completadas</p></div>
                                <div class="metric-status"><?= $last_log ? $last_log['routines_completed'] : '0' ?> hoy</div>
                            </li>
                        </ul>
                    </div>

                    <div class="widget-panel" id="widget-2">
                        <ul class="metric-list">
                            <li class="metric-item">
                                <div class="metric-info"><h4>Fuentes de Estudio</h4><p>Bases de conocimiento</p></div>
                                <div class="metric-status"><?= $projectsData['Fuentes de Estudio'] ?? 0 ?> Activas</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Logros</h4><p>Hitos alcanzados en el sistema</p></div>
                                <div class="metric-status" style="background:#C6F6D5; color:#276749;"><?= $projectsData['Logros'] ?? 0 ?> Totales</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Proyectos P1 y P2</h4><p>Desarrollo principal</p></div>
                                <div class="metric-status"><?= ($projectsData['Proyecto 1'] ?? 0) + ($projectsData['Proyecto 2'] ?? 0) ?> Activos</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // --- 1. ROTACIÓN DEL CARRUSEL ---
        let currentSlide = 0;
        const totalSlides = 3;
        setInterval(() => {
            document.getElementById(`slide-${currentSlide}`).classList.remove('active');
            currentSlide = (currentSlide + 1) % totalSlides;
            document.getElementById(`slide-${currentSlide}`).classList.add('active');
        }, 5000);

        // --- 2. CONTROL DE WIDGETS ---
        function showWidget(index, btnElement) {
            document.querySelectorAll('.widget-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.widget-panel').forEach(panel => panel.classList.remove('active'));
            
            btnElement.classList.add('active');
            document.getElementById(`widget-${index}`).classList.add('active');
        }

        // --- 3. CONFIGURACIÓN DE GRÁFICAS (Chart.js) ---
        const labelsDays = <?= $log_labels ?: '[]' ?>;
        
        // Gráfica 1: Proyección (Líneas)
        new Chart(document.getElementById('chartProjection'), {
            type: 'line',
            data: {
                labels: labelsDays,
                datasets: [
                    { label: 'Ánimo', data: <?= $log_mood ?: '[]' ?>, borderColor: '#805AD5', backgroundColor: 'rgba(128, 90, 213, 0.1)', fill: true, tension: 0.4 },
                    { label: 'Salud', data: <?= $log_health ?: '[]' ?>, borderColor: '#38A169', borderDash: [5, 5], tension: 0.4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 10 } }, plugins: { legend: { display: false } } }
        });

        // Gráfica 2: Estado Humano (Radar)
        new Chart(document.getElementById('chartRadar'), {
            type: 'radar',
            data: {
                labels: ['Psique', 'Pneuma', 'Soma', 'Pathos'],
                datasets: [{
                    label: 'Balance',
                    data: <?= json_encode($radar_data) ?>,
                    backgroundColor: 'rgba(128, 90, 213, 0.2)', borderColor: '#805AD5',
                    pointBackgroundColor: ['#ECC94B', '#A0AEC0', '#4299E1', '#F56565'],
                    borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { r: { ticks: { display: false }, min: 0, max: 100 } }, plugins: { legend: { display: false } } }
        });

        // Gráfica 3: Hábitos (Barras)
        new Chart(document.getElementById('chartHabits'), {
            type: 'bar',
            data: {
                labels: labelsDays,
                datasets: [{
                    label: 'Rutinas Completadas',
                    data: <?= $log_routines ?: '[]' ?>,
                    backgroundColor: '#805AD5', borderRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>