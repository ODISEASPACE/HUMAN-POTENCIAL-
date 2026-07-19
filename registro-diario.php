<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// --- 1. AUTO-CALCULAR RUTINAS DEL CALENDARIO DE HOY ---
$stmtRoutines = $pdo->prepare("
    SELECT COUNT(*) FROM calendar_events 
    WHERE user_id = ? 
    AND DATE(start_time) = CURRENT_DATE 
    AND is_completed = TRUE
");
$stmtRoutines->execute([$user_id]);
$auto_routines_count = (int)$stmtRoutines->fetchColumn();

// --- 2. OBTENER ELEMENTOS DEL REPOSITORIO DE PROYECTOS ---
// Cargamos las fuentes de estudio, logros y proyectos activos
$stmtProjects = $pdo->prepare("
    SELECT id, title, category 
    FROM projects_items 
    WHERE user_id = ? 
    ORDER BY category ASC, title ASC
");
$stmtProjects->execute([$user_id]);
$projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);

// --- 3. PROCESAR EL FORMULARIO (Guardar nota vinculada al proyecto) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_item_id = !empty($_POST['project_item_id']) ? $_POST['project_item_id'] : null;
    $routines = (int)$_POST['routines'];
    $mood = (int)$_POST['mood'];
    $health = (int)$_POST['health'];
    $finance = (int)$_POST['finance'];
    $notes = trim($_POST['notes']);

    try {
        // Asumiendo que agregaste 'project_item_id' a la tabla daily_logs
        // Se ha removido el ON CONFLICT para permitir múltiples notas al día para distintos proyectos
        $stmt = $pdo->prepare("
            INSERT INTO daily_logs (user_id, log_date, project_item_id, routines_completed, mood_score, health_score, finance_score, notes) 
            VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $project_item_id, $routines, $mood, $health, $finance, $notes]);
        $mensaje = "<div class='msg-success'>Nota registrada y vinculada a tu repositorio exitosamente.</div>";
    } catch (PDOException $e) {
        // Fallback temporal si no has alterado la tabla sql para incluir project_item_id
        try {
             $stmt = $pdo->prepare("
                INSERT INTO daily_logs (user_id, log_date, routines_completed, mood_score, health_score, finance_score, notes) 
                VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?)
            ");
            // Guardamos el ID del proyecto dentro de las notas como texto provisorio
            $notas_fallback = $project_item_id ? "[Ref. Proyecto ID: $project_item_id]\n$notes" : $notes;
            $stmt->execute([$user_id, $routines, $mood, $health, $finance, $notas_fallback]);
            $mensaje = "<div class='msg-success'>Guardado (Modo compatibilidad). Recuerda añadir 'project_item_id' (uuid) a tu tabla daily_logs.</div>";
        } catch (PDOException $e2) {
            $mensaje = "<div class='msg-error'>Error al guardar: " . $e2->getMessage() . "</div>";
        }
    }
}

// --- 4. OBTENER DATOS HISTÓRICOS (Solo para la Gráfica de Evolución) ---
$stmtLogs = $pdo->prepare("
    SELECT log_date, to_char(log_date, 'DD/MM') as f_date, mood_score, health_score, finance_score 
    FROM daily_logs 
    WHERE user_id = ? 
    ORDER BY log_date DESC 
    LIMIT 30
");
$stmtLogs->execute([$user_id]);
$logs_feed = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Invertimos el array para que la gráfica vaya de viejo a nuevo
$logs_chart = array_reverse($logs_feed);

$labels = json_encode(array_column($logs_chart, 'f_date'));
$data_mood = json_encode(array_column($logs_chart, 'mood_score'));
$data_health = json_encode(array_column($logs_chart, 'health_score'));
$data_finance = json_encode(array_column($logs_chart, 'finance_score'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora de Repositorio | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-hover: #6B46C1; --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 30px; }
        
        /* Cabecera */
        .header-dash { display: flex; justify-content: space-between; align-items: center; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        .btn-calendar { background: white; color: var(--accent); border: 2px solid var(--accent); padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-calendar:hover { background: var(--accent-light); }

        .dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .card h3 { margin-bottom: 20px; font-size: 1.2rem; }

        /* Formulario Modificado */
        .post-creator { display: flex; flex-direction: column; gap: 15px; }
        
        .project-select {
            font-family: inherit;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-main);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            outline: none;
            transition: 0.3s;
            background-color: var(--bg-base);
            cursor: pointer;
        }
        .project-select:focus { border-color: var(--accent); }
        
        .post-creator textarea { width: 100%; min-height: 120px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; resize: vertical; outline: none; }
        .post-creator textarea:focus { border-color: var(--accent); }
        
        /* Sliders */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; }
        .stat-item label { display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; margin-bottom: 5px; color: var(--text-muted); }
        .stat-item input[type="range"] { width: 100%; accent-color: var(--accent); }
        
        .btn-submit { background: var(--accent); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; width: 100%; }
        .btn-submit:hover { background: var(--accent-hover); }
        
        .msg-success { background: #C6F6D5; color: #276749; padding: 15px; border-radius: 8px; font-weight: 600; }
        .msg-error { background: #FED7D7; color: #C53030; padding: 15px; border-radius: 8px; font-weight: 600; }
        
        .chart-container { position: relative; height: 350px; width: 100%; }

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
            <div>
                <h1>Registro de Progreso</h1>
                <p>Vincula notas diarias, logros y métricas a tu repositorio de proyectos.</p>
            </div>
            <a href="calendar.php" class="btn-calendar">
                📅 Abrir Calendario
            </a>
        </div>

        <?= $mensaje ?>

        <div class="dashboard-grid">
            <!-- COLUMNA IZQUIERDA: Formulario de Conexión a Repositorio -->
            <div class="card">
                <h3>Añadir apunte al Repositorio</h3>
                <form method="POST" action="" class="post-creator">
                    
                    <!-- Selector de Proyectos/Fuentes -->
                    <select name="project_item_id" class="project-select" required>
                        <option value="">Selecciona un elemento de estudio o proyecto...</option>
                        <?php 
                        $current_category = '';
                        foreach($projects as $project): 
                            if ($current_category !== $project['category']) {
                                if ($current_category !== '') echo '</optgroup>';
                                $current_category = $project['category'];
                                echo '<optgroup label="' . htmlspecialchars(strtoupper($current_category)) . '">';
                            }
                        ?>
                            <option value="<?= htmlspecialchars($project['id']) ?>">
                                <?= htmlspecialchars($project['title']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_category !== '') echo '</optgroup>'; ?>
                    </select>
                    
                    <textarea name="notes" placeholder="Registra lo aprendido, obstáculos superados, o el avance del día respecto a este elemento..." required></textarea>
                    
                    <!-- Integración con el Calendario -->
                    <div style="background: var(--accent-light); padding: 10px 15px; border-radius: 8px; border-left: 4px solid var(--accent); font-size: 0.9rem;">
                        <strong>Sincronización de Agenda:</strong> Tienes <input type="number" name="routines" value="<?= $auto_routines_count ?>" style="width: 50px; text-align:center; border:none; border-bottom:1px solid #ccc; background: transparent; font-weight: bold;"> tareas completadas hoy en tu calendario.
                    </div>

                    <div class="stats-grid">
                        <div class="stat-item">
                            <label>Psique <span id="moodVal">5</span></label>
                            <input type="range" name="mood" min="1" max="10" value="5" oninput="document.getElementById('moodVal').innerText = this.value">
                        </div>
                        <div class="stat-item">
                            <label>Soma <span id="healthVal">5</span></label>
                            <input type="range" name="health" min="1" max="10" value="5" oninput="document.getElementById('healthVal').innerText = this.value">
                        </div>
                        <div class="stat-item">
                            <label>Economía <span id="financeVal">5</span></label>
                            <input type="range" name="finance" min="1" max="10" value="5" oninput="document.getElementById('financeVal').innerText = this.value">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Guardar Registro Diario</button>
                </form>
            </div>

            <!-- COLUMNA DERECHA: Análisis (El Feed fue removido) -->
            <div>
                <div class="card" style="height: 100%;">
                    <h3>Evolución de Métricas (30 días)</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                        Visión general del estado humano registrado durante las sesiones de trabajo.
                    </p>
                    <div class="chart-container">
                        <canvas id="progressionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('progressionChart');
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
                        { label: 'Ánimo (Psique)', data: moodData, borderColor: '#805AD5', backgroundColor: 'rgba(128, 90, 213, 0.1)', tension: 0.4, fill: true },
                        { label: 'Salud (Soma)', data: healthData, borderColor: '#38A169', backgroundColor: 'transparent', borderDash: [5, 5], tension: 0.4 },
                        { label: 'Economía', data: financeData, borderColor: '#D69E2E', backgroundColor: 'transparent', tension: 0.4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { min: 0, max: 10, grid: { color: '#E2E8F0' } }, x: { grid: { display: false } } },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } }
                }
            });
        }
    </script>
</body>
</html>