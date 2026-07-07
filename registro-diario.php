<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// 1. PROCESAR EL FORMULARIO (Guardar datos de hoy)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $routines = (int)$_POST['routines'];
    $mood = (int)$_POST['mood'];
    $health = (int)$_POST['health'];
    $finance = (int)$_POST['finance'];
    $notes = trim($_POST['notes']);

    try {
        // Usamos ON CONFLICT para actualizar si el usuario ya llenó el registro hoy
        $stmt = $pdo->prepare("
            INSERT INTO daily_logs (user_id, log_date, routines_completed, mood_score, health_score, finance_score, notes) 
            VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?)
            ON CONFLICT (user_id, log_date) 
            DO UPDATE SET 
                routines_completed = EXCLUDED.routines_completed,
                mood_score = EXCLUDED.mood_score,
                health_score = EXCLUDED.health_score,
                finance_score = EXCLUDED.finance_score,
                notes = EXCLUDED.notes
        ");
        $stmt->execute([$user_id, $routines, $mood, $health, $finance, $notes]);
        $mensaje = "<div class='msg-success'>Registro diario guardado exitosamente.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='msg-error'>Error al guardar: " . $e->getMessage() . "</div>";
    }
}

// 2. OBTENER DATOS DEL USUARIO PARA LA BARRA LATERAL
$stmtUser = $pdo->prepare("SELECT username, profile_picture, profession FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// 3. OBTENER DATOS HISTÓRICOS PARA LAS GRÁFICAS (Últimos 30 días)
$stmtLogs = $pdo->prepare("
    SELECT to_char(log_date, 'DD/MM') as f_date, routines_completed, mood_score, health_score, finance_score 
    FROM daily_logs 
    WHERE user_id = ? 
    ORDER BY log_date ASC 
    LIMIT 30
");
$stmtLogs->execute([$user_id]);
$logs = $stmtLogs->fetchAll();

// Preparar los datos en formato JSON para inyectarlos en Chart.js
$labels = json_encode(array_column($logs, 'f_date'));
$data_mood = json_encode(array_column($logs, 'mood_score'));
$data_health = json_encode(array_column($logs, 'health_score'));
$data_finance = json_encode(array_column($logs, 'finance_score'));

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
    <title>Registro Diario | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-light: rgba(128, 90, 213, 0.1); --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar (Mismos estilos) */
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
        
        /* Main Content */
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 30px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .card h3 { margin-bottom: 20px; font-size: 1.2rem; }

        /* Formulario de Input */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: flex; justify-content: space-between; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; }
        .form-group input[type="range"] { width: 100%; accent-color: var(--accent); }
        .form-group input[type="number"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .btn-submit { width: 100%; background: var(--accent); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: var(--accent-hover); }
        
        .msg-success { background: #C6F6D5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        .msg-error { background: #FED7D7; color: #C53030; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        
        /* Contenedor Gráfica */
        .chart-container { position: relative; height: 400px; width: 100%; }

        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="estado-humano.php" class="nav-link">👤 Estado Humano</a>
            <a href="registro-diario.php" class="nav-link active">⏱ Registro Diario</a>
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
            <h1>Registro Diario</h1>
            <p>Monitoreo constante de tu estado de ánimo, salud física y estabilidad financiera.</p>
        </div>

        <?= $mensaje ?>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Registro de Hoy</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Rutinas/Eventos Completados <span id="routinesVal">0</span></label>
                        <input type="number" name="routines" min="0" value="0" required oninput="document.getElementById('routinesVal').innerText = this.value">
                    </div>
                    
                    <div class="form-group">
                        <label>Estado de Ánimo (Psique) <span id="moodVal">5</span></label>
                        <input type="range" name="mood" min="1" max="10" value="5" oninput="document.getElementById('moodVal').innerText = this.value">
                    </div>

                    <div class="form-group">
                        <label>Estado de Salud (Soma) <span id="healthVal">5</span></label>
                        <input type="range" name="health" min="1" max="10" value="5" oninput="document.getElementById('healthVal').innerText = this.value">
                    </div>

                    <div class="form-group">
                        <label>Estado Económico <span id="financeVal">5</span></label>
                        <input type="range" name="finance" min="1" max="10" value="5" oninput="document.getElementById('financeVal').innerText = this.value">
                    </div>

                    <div class="form-group">
                        <label>Notas del día</label>
                        <textarea name="notes" placeholder="Reflexiones, imprevistos..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Guardar Registro</button>
                </form>
            </div>

            <div class="card">
                <h3>Progresión Histórica</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Evolución de tus métricas (1-10) en los últimos 30 días registrados.</p>
                
                <div class="chart-container">
                    <canvas id="progressionChart"></canvas>
                </div>
                
                <?php if(empty($logs)): ?>
                    <div style="text-align: center; color: var(--text-muted); margin-top: -200px;">
                        Aún no hay datos para graficar. Llena tu primer registro hoy.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Configuración de Chart.js
        const ctx = document.getElementById('progressionChart');
        
        // Datos inyectados desde PHP
        const chartLabels = <?= $labels ?: '[]' ?>;
        const moodData = <?= $data_mood ?: '[]' ?>;
        const healthData = <?= $data_health ?: '[]' ?>;
        const financeData = <?= $data_finance ?: '[]' ?>;

        if (chartLabels.length > 0) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Ánimo',
                            data: moodData,
                            borderColor: '#805AD5', // APH Accent
                            backgroundColor: 'rgba(128, 90, 213, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Salud',
                            data: healthData,
                            borderColor: '#38A169', // Verde éxito
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            tension: 0.4
                        },
                        {
                            label: 'Economía',
                            data: financeData,
                            borderColor: '#D69E2E', // Amarillo/Oro
                            backgroundColor: 'transparent',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 10,
                            grid: { color: '#E2E8F0' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        }
    </script>
</body>
</html>