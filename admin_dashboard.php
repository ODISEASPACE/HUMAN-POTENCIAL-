<?php
session_start();
require 'db.php'; // Mantenemos esta para requerimientos base, pero haremos una conexión manual a la BD antigua

// Validación estricta de administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Si no es admin, lo devolvemos al inicio o a su dashboard
    header("Location: dashboard.php");
    exit;
}

// ==========================================
// 0. CONEXIÓN EXCLUSIVA A LA BD 'postgres' (V1)
// ==========================================
try {
    // Usando el endpoint real de AWS RDS extraído de tu configuración
    $host_v1 = 'aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com';
    $dbname_v1 = 'postgres';
    $user_v1 = 'postgres';
    $password_v1 = 'Limitless20xx';

    $pdo_old = new PDO("pgsql:host=$host_v1;port=5432;dbname=$dbname_v1;", $user_v1, $password_v1, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error conectando a la base de datos antigua: " . $e->getMessage());
}

// ==========================================
// 1. CONSULTAS A LA BASE DE DATOS 'postgres'
// ==========================================

// Métricas de Usuarios (De la BD antigua)
$stmtUsers = $pdo_old->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_verified THEN 1 ELSE 0 END) as verified FROM users");
$userStats = $stmtUsers->fetch(PDO::FETCH_ASSOC);
$totalUsers = $userStats['total'] ?? 0;
$verifiedUsers = $userStats['verified'] ?? 0;

// Arquetipos (Distribución basada en test_results)
$stmtArchetypes = $pdo_old->query("SELECT archetype, COUNT(*) as count FROM test_results GROUP BY archetype");
$archetypesData = $stmtArchetypes->fetchAll(PDO::FETCH_KEY_PAIR);

$arch_labels = json_encode(array_keys($archetypesData));
$arch_counts = json_encode(array_values($archetypesData));

// Resultados del Test (Dispersión Disciplina vs Propósito)
$stmtScores = $pdo_old->query("SELECT score_x_discipline, score_y_purpose, archetype FROM test_results");
$scoresData = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

$scatter_data = [];
foreach($scoresData as $row) {
    $scatter_data[] = [
        'x' => (int)$row['score_x_discipline'],
        'y' => (int)$row['score_y_purpose'],
        'archetype' => $row['archetype']
    ];
}
$scatter_json = json_encode($scatter_data);

// Últimos Usuarios Registrados (BD antigua)
$stmtLatestUsers = $pdo_old->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$latestUsers = $stmtLatestUsers->fetchAll(PDO::FETCH_ASSOC);

// Últimos Feedbacks (Alpha Feedback)
$stmtFeedback = $pdo_old->query("
    SELECT u.name, f.identity_validation, f.friction_detection, f.created_at 
    FROM alpha_feedback f 
    LEFT JOIN users u ON f.user_id = u.id 
    ORDER BY f.created_at DESC LIMIT 5
");
$latestFeedback = $stmtFeedback->fetchAll(PDO::FETCH_ASSOC);

// Últimas Metas (Tracktime Goals)
$stmtGoals = $pdo_old->query("
    SELECT u.name, t.primary_goal, t.created_at 
    FROM tracktime_goals t 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC LIMIT 5
");
$latestGoals = $stmtGoals->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Adaptación a un modo oscuro/Administración para diferenciar del panel de usuario */
        :root { 
            --bg-base: #111827; 
            --bg-panel: #1F2937; 
            --text-main: #F9FAFB; 
            --text-muted: #9CA3AF; 
            --accent: #3B82F6; 
            --accent-light: rgba(59, 130, 246, 0.15); 
            --border-color: #374151; 
            --success: #10B981; 
            --warning: #F59E0B; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar Admin Integrado */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 100; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } 
        .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .admin-badge { background: #EF4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: auto; }
        .user-mini-btn { display: flex; align-items: center; gap: 12px; padding: 15px; border-top: 1px solid var(--border-color); margin-top: auto; text-decoration: none; border-radius: 12px; transition: 0.3s;}
        .user-mini-btn:hover { background: rgba(255,255,255,0.05); }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; }
        .user-info-mini h4 { font-size: 0.9rem; color: var(--text-main); }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }
        
        /* Main Dashboard */
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header-dash { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        /* Stats Top */
        .stats-top { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-card h4 { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px; }
        .stat-card h2 { font-size: 1.8rem; font-weight: 800; color: var(--text-main); }
        
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .card-header h3 { font-size: 1.2rem; font-weight: 700; }
        
        /* Gráficos Carrusel */
        .graph-carousel { position: relative; height: 320px; width: 100%; }
        .graph-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease; display: flex; flex-direction: column; pointer-events: none; }
        .graph-slide.active { opacity: 1; z-index: 2; pointer-events: auto; }
        .graph-title { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 15px; text-align: center; }
        .canvas-container { position: relative; height: 260px; width: 100%; }

        /* Widgets */
        .widget-controls { display: flex; gap: 10px; margin-bottom: 20px; background: #111827; padding: 5px; border-radius: 10px; border: 1px solid var(--border-color); }
        .widget-btn { flex: 1; background: transparent; border: none; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; transition: 0.3s; }
        .widget-btn:hover { color: var(--text-main); }
        .widget-btn.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }

        .widget-panel { display: none; animation: fadeIn 0.4s ease forwards; }
        .widget-panel.active { display: block; }
        
        .metric-list { list-style: none; display: flex; flex-direction: column; gap: 15px; }
        .metric-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(255,255,255,0.02); }
        .metric-info h4 { font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; color: var(--text-main); }
        .metric-info p { font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 250px; }
        .metric-status { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: var(--border-color); color: var(--text-main); white-space: nowrap; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .dashboard-grid { grid-template-columns: 1fr; } .stats-top { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>

    <!-- Sidebar Admin -->
    <nav class="sidebar">
        <div class="brand"><h2>APH <span style="color:#EF4444">OS</span></h2></div>
        <div class="nav-links">
            <a href="#" class="nav-link active">⌂ Panel Global <span class="admin-badge">Admin</span></a>
            <a href="#" class="nav-link">👥 Gestión (users)</a>
            <a href="#" class="nav-link">📊 test_results</a>
            <a href="#" class="nav-link">💬 alpha_feedback</a>
            <a href="#" class="nav-link">🎯 tracktime_goals</a>
        </div>
        <a href="dashboard.php" class="user-mini-btn" style="border: 1px solid var(--accent); margin-bottom: 10px; justify-content: center;">
            <span style="font-weight: 600; color: var(--accent);">Volver a mi Sistema</span>
        </a>
        <a href="#" class="user-mini-btn">
            <div class="avatar-circle">🛡️</div>
            <div class="user-info-mini">
                <h4>Admin Root</h4>
                <p>Nivel de Acceso: Base Antigua</p>
            </div>
        </a>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="header-dash">
            <div>
                <h1>Centro de Comando (Postgres)</h1>
                <p>Monitoreo global de la base de datos antigua (Tablas V1).</p>
            </div>
        </div>
        
        <!-- Tarjetas Superiores -->
        <div class="stats-top">
            <div class="stat-card">
                <h4>Total Usuarios Registrados</h4>
                <h2><?= $totalUsers ?></h2>
            </div>
            <div class="stat-card">
                <h4>Usuarios Verificados</h4>
                <h2 style="color: var(--success);"><?= $verifiedUsers ?></h2>
            </div>
            <div class="stat-card">
                <h4>Feedbacks Recibidos</h4>
                <h2><?= count($latestFeedback) ?> <span style="font-size:0.8rem;color:var(--text-muted)">recientes</span></h2>
            </div>
            <div class="stat-card">
                <h4>Metas Declaradas</h4>
                <h2><?= count($latestGoals) ?> <span style="font-size:0.8rem;color:var(--text-muted)">recientes</span></h2>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <!-- TARJETA 1: GRÁFICOS GLOBALES -->
            <div class="card">
                <div class="card-header">
                    <h3>Análisis de Datos</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Rotación Automática</span>
                </div>
                
                <div class="graph-carousel">
                    <div class="graph-slide active" id="slide-0">
                        <div class="graph-title">Distribución de Arquetipos Globales (test_results)</div>
                        <div class="canvas-container"><canvas id="chartArchetypes"></canvas></div>
                    </div>
                    
                    <div class="graph-slide" id="slide-1">
                        <div class="graph-title">Dispersión: Disciplina (X) vs Propósito (Y)</div>
                        <div class="canvas-container"><canvas id="chartScatter"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- TARJETA 2: SUB-CONTENIDO Y REGISTROS RECIENTES -->
            <div class="card">
                <div class="card-header">
                    <h3>Registros Recientes</h3>
                </div>
                
                <div class="widget-controls">
                    <button class="widget-btn active" onclick="showWidget(0, this)">Nuevos Usuarios</button>
                    <button class="widget-btn" onclick="showWidget(1, this)">Feedback</button>
                    <button class="widget-btn" onclick="showWidget(2, this)">Metas V1</button>
                </div>

                <div class="widget-content-area">
                    
                    <!-- WIDGET 0: USUARIOS -->
                    <div class="widget-panel active" id="widget-0">
                        <ul class="metric-list">
                            <?php if (empty($latestUsers)): ?>
                                <p style="color:var(--text-muted)">No hay registros.</p>
                            <?php else: ?>
                                <?php foreach($latestUsers as $user): ?>
                                <li class="metric-item">
                                    <div class="metric-info">
                                        <h4><?= htmlspecialchars($user['name'] ?? 'Sin Nombre') ?></h4>
                                        <p><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                    <div class="metric-status">ID: <?= $user['id'] ?></div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- WIDGET 1: FEEDBACK -->
                    <div class="widget-panel" id="widget-1">
                        <ul class="metric-list">
                            <?php if (empty($latestFeedback)): ?>
                                <p style="color:var(--text-muted)">No hay feedbacks.</p>
                            <?php else: ?>
                                <?php foreach($latestFeedback as $fb): ?>
                                <li class="metric-item">
                                    <div class="metric-info">
                                        <h4><?= htmlspecialchars($fb['name'] ?? 'Desconocido') ?></h4>
                                        <p><strong>Fricción:</strong> <?= htmlspecialchars($fb['friction_detection'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="metric-status" style="background:rgba(245, 158, 11, 0.2); color:var(--warning);">Reciente</div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- WIDGET 2: METAS -->
                    <div class="widget-panel" id="widget-2">
                        <ul class="metric-list">
                            <?php if (empty($latestGoals)): ?>
                                <p style="color:var(--text-muted)">No hay metas registradas.</p>
                            <?php else: ?>
                                <?php foreach($latestGoals as $goal): ?>
                                <li class="metric-item">
                                    <div class="metric-info">
                                        <h4><?= htmlspecialchars($goal['name'] ?? 'Desconocido') ?></h4>
                                        <p><?= htmlspecialchars($goal['primary_goal'] ?? 'Sin meta primaria') ?></p>
                                    </div>
                                    <div class="metric-status" style="background:rgba(16, 185, 129, 0.2); color:var(--success);">Registrada</div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <script>
        // --- 1. ROTACIÓN DEL CARRUSEL ---
        let currentSlide = 0;
        const totalSlides = 2; // Arquetipos, Scatter
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
        Chart.defaults.color = '#9CA3AF';
        Chart.defaults.borderColor = '#374151';

        // Gráfica 1: Arquetipos (Bar)
        new Chart(document.getElementById('chartArchetypes'), {
            type: 'bar',
            data: {
                labels: <?= $arch_labels ?: '[]' ?>,
                datasets: [{
                    label: 'Usuarios por Arquetipo',
                    data: <?= $arch_counts ?: '[]' ?>,
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                    borderRadius: 6
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Gráfica 2: Dispersión Test Results
        const rawScatterData = <?= $scatter_json ?: '[]' ?>;
        
        const archetypeColors = {
            'Executor': '#EF4444',
            'Soldier': '#3B82F6',
            'Wanderer': '#10B981',
            'Dreamer': '#F59E0B'
        };

        new Chart(document.getElementById('chartScatter'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Disciplina vs Propósito',
                    data: rawScatterData,
                    backgroundColor: rawScatterData.map(p => archetypeColors[p.archetype] || '#8B5CF6'),
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const pt = rawScatterData[ctx.dataIndex];
                                return pt.archetype + ': (' + pt.x + ', ' + pt.y + ')';
                            }
                        }
                    }
                },
                scales: {
                    x: { title: { display: true, text: 'Disciplina (X)' }, min: 0, max: 50 },
                    y: { title: { display: true, text: 'Propósito (Y)' }, min: 0, max: 50 }
                }
            }
        });
    </script>
</body>
</html>