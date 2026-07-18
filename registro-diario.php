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
// Cuenta cuántos eventos de hoy están marcados como completados (is_completed = true)
$stmtRoutines = $pdo->prepare("
    SELECT COUNT(*) FROM calendar_events 
    WHERE user_id = ? 
    AND DATE(start_time) = CURRENT_DATE 
    AND is_completed = TRUE
");
$stmtRoutines->execute([$user_id]);
$auto_routines_count = (int)$stmtRoutines->fetchColumn();

// --- 2. PROCESAR EL FORMULARIO (Guardar entrada del Blog/Diario) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $routines = (int)$_POST['routines'];
    $mood = (int)$_POST['mood'];
    $health = (int)$_POST['health'];
    $finance = (int)$_POST['finance'];
    $notes = trim($_POST['notes']);
    $media_path = null;

    // Lógica básica para subir imágenes
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $upload_dir = 'uploads/journal/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('log_') . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_path = $target_file;
        }
    }

    try {
        // Asumiendo que agregaste 'title' y 'media_path' a tu tabla daily_logs
        $stmt = $pdo->prepare("
            INSERT INTO daily_logs (user_id, log_date, title, media_path, routines_completed, mood_score, health_score, finance_score, notes) 
            VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (user_id, log_date) 
            DO UPDATE SET 
                title = EXCLUDED.title,
                media_path = COALESCE(EXCLUDED.media_path, daily_logs.media_path),
                routines_completed = EXCLUDED.routines_completed,
                mood_score = EXCLUDED.mood_score,
                health_score = EXCLUDED.health_score,
                finance_score = EXCLUDED.finance_score,
                notes = EXCLUDED.notes
        ");
        $stmt->execute([$user_id, $title, $media_path, $routines, $mood, $health, $finance, $notes]);
        $mensaje = "<div class='msg-success'>Entrada guardada en tu bitácora exitosamente.</div>";
    } catch (PDOException $e) {
        // Fallback temporal si no has alterado la tabla (guarda sin titulo ni media)
        try {
             $stmt = $pdo->prepare("
                INSERT INTO daily_logs (user_id, log_date, routines_completed, mood_score, health_score, finance_score, notes) 
                VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?)
                ON CONFLICT (user_id, log_date) 
                DO UPDATE SET 
                    routines_completed = EXCLUDED.routines_completed, mood_score = EXCLUDED.mood_score, health_score = EXCLUDED.health_score, finance_score = EXCLUDED.finance_score, notes = EXCLUDED.notes
            ");
            $stmt->execute([$user_id, $routines, $mood, $health, $finance, "[$title] \n $notes"]);
            $mensaje = "<div class='msg-success'>Guardado (Modo compatibilidad). Recuerda actualizar tu tabla SQL.</div>";
        } catch (PDOException $e2) {
            $mensaje = "<div class='msg-error'>Error al guardar: " . $e2->getMessage() . "</div>";
        }
    }
}

// --- 3. OBTENER DATOS HISTÓRICOS (Para Gráficas y el Feed del Blog) ---
$stmtLogs = $pdo->prepare("
    SELECT log_date, to_char(log_date, 'DD/MM') as f_date, title, media_path, routines_completed, mood_score, health_score, finance_score, notes 
    FROM daily_logs 
    WHERE user_id = ? 
    ORDER BY log_date DESC 
    LIMIT 30
");
$stmtLogs->execute([$user_id]);
$logs_feed = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Invertimos el array solo para la gráfica (para que vaya de viejo a nuevo)
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
    <title>Bitácora Diaria | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-hover: #6B46C1; --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 30px; }
        
        /* Cabecera con botones */
        .header-dash { display: flex; justify-content: space-between; align-items: center; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        .btn-calendar { background: white; color: var(--accent); border: 2px solid var(--accent); padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-calendar:hover { background: var(--accent-light); }

        .dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .card h3 { margin-bottom: 20px; font-size: 1.2rem; }

        /* Formulario Blog-Style */
        .post-creator { display: flex; flex-direction: column; gap: 15px; }
        .post-creator input[type="text"] { font-size: 1.2rem; font-weight: 600; border: none; border-bottom: 2px solid var(--border-color); padding: 10px 0; outline: none; transition: 0.3s; }
        .post-creator input[type="text"]:focus { border-bottom-color: var(--accent); }
        .post-creator textarea { width: 100%; min-height: 120px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; resize: vertical; outline: none; }
        .post-creator textarea:focus { border-color: var(--accent); }
        
        /* Contenedor de Sliders Minimalista */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; }
        .stat-item label { display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; margin-bottom: 5px; color: var(--text-muted); }
        .stat-item input[type="range"] { width: 100%; accent-color: var(--accent); }
        
        .file-upload { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; color: var(--text-muted); cursor: pointer; }
        
        .btn-submit { background: var(--accent); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; align-self: flex-end; }
        .btn-submit:hover { background: var(--accent-hover); }
        
        .msg-success { background: #C6F6D5; color: #276749; padding: 15px; border-radius: 8px; font-weight: 600; }
        .msg-error { background: #FED7D7; color: #C53030; padding: 15px; border-radius: 8px; font-weight: 600; }
        
        /* Feed del Blog */
        .feed-container { display: flex; flex-direction: column; gap: 20px; margin-top: 30px; }
        .feed-post { border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; }
        .feed-date { font-size: 0.8rem; color: var(--accent); font-weight: 700; margin-bottom: 5px; }
        .feed-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 10px; }
        .feed-body { color: #4a5568; line-height: 1.6; font-size: 0.95rem; margin-bottom: 15px; }
        .feed-image { width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
        .feed-metrics { display: flex; gap: 15px; font-size: 0.8rem; color: var(--text-muted); font-weight: 600; }
        .feed-metrics span { background: #edf2f7; padding: 4px 8px; border-radius: 4px; }

        .chart-container { position: relative; height: 250px; width: 100%; }

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
                <h1>Bitácora y Registro</h1>
                <p>Documenta tus logros, reflexiones y métricas del día a día.</p>
            </div>
            <a href="calendar.php" class="btn-calendar">
                📅 Abrir Calendario
            </a>
        </div>

        <?= $mensaje ?>

        <div class="dashboard-grid">
            <!-- COLUMNA IZQUIERDA: Creador de Post -->
            <div class="card">
                <h3>¿Qué lograste hoy, Daniel?</h3>
                <form method="POST" action="" enctype="multipart/form-data" class="post-creator">
                    
                    <input type="text" name="title" placeholder="Ej. Lanzamiento de MVP, Día de lectura..." required>
                    
                    <textarea name="notes" placeholder="Escribe tus reflexiones, ideas, o lo que tienes en mente..."></textarea>
                    
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

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <label class="file-upload">
                            📸 Adjuntar Imagen
                            <input type="file" name="media" accept="image/*" style="display: none;" onchange="alert('Imagen seleccionada: ' + this.files[0].name)">
                        </label>
                        <button type="submit" class="btn-submit">Publicar en Bitácora</button>
                    </div>
                </form>
            </div>

            <!-- COLUMNA DERECHA: Análisis y Feed -->
            <div>
                <div class="card" style="margin-bottom: 30px;">
                    <h3>Evolución (30 días)</h3>
                    <div class="chart-container">
                        <canvas id="progressionChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h3>Historial de Bitácora</h3>
                    <div class="feed-container">
                        <?php if(empty($logs_feed)): ?>
                            <p style="color: var(--text-muted); text-align: center;">No hay entradas recientes.</p>
                        <?php else: ?>
                            <?php foreach($logs_feed as $post): ?>
                                <div class="feed-post">
                                    <div class="feed-date"><?= date('d M, Y', strtotime($post['log_date'])) ?></div>
                                    <div class="feed-title"><?= htmlspecialchars($post['title'] ?? 'Registro Diario') ?></div>
                                    
                                    <?php if(!empty($post['media_path'])): ?>
                                        <img src="<?= htmlspecialchars($post['media_path']) ?>" class="feed-image" alt="Adjunto del día">
                                    <?php endif; ?>

                                    <div class="feed-body">
                                        <?= nl2br(htmlspecialchars($post['notes'])) ?>
                                    </div>

                                    <div class="feed-metrics">
                                        <span>🧠 Psi: <?= $post['mood_score'] ?></span>
                                        <span>💪 Som: <?= $post['health_score'] ?></span>
                                        <span>💰 Eco: <?= $post['finance_score'] ?></span>
                                        <span>✅ Tareas: <?= $post['routines_completed'] ?></span>
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
                        { label: 'Ánimo', data: moodData, borderColor: '#805AD5', backgroundColor: 'rgba(128, 90, 213, 0.1)', tension: 0.4, fill: true },
                        { label: 'Salud', data: healthData, borderColor: '#38A169', backgroundColor: 'transparent', borderDash: [5, 5], tension: 0.4 },
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