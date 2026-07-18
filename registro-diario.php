<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// 1. AUTO-CALCULAR RUTINAS DEL CALENDARIO PARA HOY
// Cuenta cuántos eventos hay hoy y cuántos están marcados como "is_completed = TRUE"
$stmtRoutines = $pdo->prepare("
    SELECT 
        COUNT(*) as total_events, 
        COALESCE(SUM(CASE WHEN is_completed = TRUE THEN 1 ELSE 0 END), 0) as completed_events 
    FROM calendar_events 
    WHERE user_id = ? AND DATE(start_time) = CURRENT_DATE
");
$stmtRoutines->execute([$user_id]);
$todayEvents = $stmtRoutines->fetch(PDO::FETCH_ASSOC);
$autoRoutines = $todayEvents['completed_events'];
$totalEvents = $todayEvents['total_events'];


// 2. PROCESAR EL FORMULARIO (Guardar datos de hoy)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $routines = (int)$_POST['routines'];
    $mood = (int)$_POST['mood'];
    $health = (int)$_POST['health'];
    $finance = (int)$_POST['finance'];
    $notes = trim($_POST['notes']);

    try {
        // Guardar el registro diario
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

        // (Opcional) Actualizar la tabla human_state basada en el registro
        // Multiplicamos por 10 para simular una escala de 0 a 100 (RPG Stats)
        $stmtHuman = $pdo->prepare("
            INSERT INTO human_state (user_id, assessment_date, psique_score, soma_score, pathos_score)
            VALUES (?, CURRENT_DATE, ?, ?, ?)
            ON CONFLICT (user_id, assessment_date)
            DO UPDATE SET 
                psique_score = EXCLUDED.psique_score,
                soma_score = EXCLUDED.soma_score,
                pathos_score = EXCLUDED.pathos_score
        ");
        $stmtHuman->execute([$user_id, ($mood * 10), ($health * 10), ($finance * 10)]);

        $mensaje = "<div class='msg-success'>Registro diario guardado exitosamente. Se ha actualizado tu Estado Humano.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='msg-error'>Error al guardar: " . $e->getMessage() . "</div>";
    }
}

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

$labels = json_encode(array_column($logs, 'f_date'));
$data_mood = json_encode(array_column($logs, 'mood_score'));
$data_health = json_encode(array_column($logs, 'health_score'));
$data_finance = json_encode(array_column($logs, 'finance_score'));

// 4. OBTENER ESTADO HUMANO (RPG Stats)
$stmtState = $pdo->prepare("
    SELECT psique_score, pneuma_score, soma_score, pathos_score 
    FROM human_state 
    WHERE user_id = ? 
    ORDER BY assessment_date DESC LIMIT 1
");
$stmtState->execute([$user_id]);
$hState = $stmtState->fetch(PDO::FETCH_ASSOC);

// Valores por defecto si aún no hay estado humano registrado
if (!$hState) {
    $hState = ['psique_score' => 50, 'pneuma_score' => 50, 'soma_score' => 50, 'pathos_score' => 50];
}
$radarData = json_encode([$hState['psique_score'], $hState['soma_score'], $hState['pathos_score'], $hState['pneuma_score']]);

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
        
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 30px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        /* Ajuste del Grid para 3 columnas en pantallas grandes */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); display: flex; flex-direction: column;}
        .card h3 { margin-bottom: 20px; font-size: 1.2rem; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: flex; justify-content: space-between; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; }
        .form-group input[type="range"] { width: 100%; accent-color: var(--accent); }
        .form-group input[type="number"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .btn-submit { width: 100%; background: var(--accent); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: auto;}
        .btn-submit:hover { background: #6B46C1; }
        
        .msg-success { background: #C6F6D5; color: #276749; padding: 10px; border-radius: 8px; font-size: 0.9rem; text-align: center; }
        .msg-error { background: #FED7D7; color: #C53030; padding: 10px; border-radius: 8px; font-size: 0.9rem; text-align: center; }
        
        .chart-container { position: relative; flex: 1; min-height: 300px; width: 100%; }
        .sync-badge { font-size: 0.75rem; background: var(--accent-light); color: var(--accent); padding: 4px 8px; border-radius: 4px; font-weight: 600; margin-left: 10px;}

        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr 1fr; }
            .card:last-child { grid-column: span 2; }
        }
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .card:last-child { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-dash">
            <h1>Registro Diario</h1>
            <p>Monitoreo constante de tu estado de ánimo, salud física y estabilidad financiera.</p>
        </div>

        <?php if($mensaje) echo $mensaje; ?>

        <div class="dashboard-grid">
            
            <!-- TARJETA 1: FORMULARIO -->
            <div class="card">
                <h3>Registro de Hoy</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>
                            <span>Rutinas Completadas 
                                <?php if($totalEvents > 0): ?>
                                    <span class="sync-badge">Sync: Calendario</span>
                                <?php endif; ?>
                            </span> 
                            <span id="routinesVal"><?= $autoRoutines ?></span>
                        </label>
                        <input type="number" name="routines" min="0" value="<?= $autoRoutines ?>" required oninput="document.getElementById('routinesVal').innerText = this.value">
                        <?php if($totalEvents > 0): ?>
                            <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 5px;">
                                Tienes <b><?= $totalEvents ?></b> eventos programados hoy.
                            </small>
                        <?php endif; ?>
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

                    <button type="submit" class="btn-submit">Guardar y Sincronizar</button>
                </form>
            </div>

            <!-- TARJETA 2: PROGRESIÓN HISTÓRICA (LINEAL) -->
            <div class="card">
                <h3>Progresión Histórica</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Evolución en los últimos 30 días registrados.</p>
                
                <div class="chart-container">
                    <canvas id="progressionChart"></canvas>
                </div>
            </div>

            <!-- TARJETA 3: ESTADO HUMANO (RADAR RPG) -->
            <div class="card">
                <h3>Estado Humano (Arquetipo)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Tu balance actual de atributos base (0-100).</p>
                
                <div class="chart-container" style="display: flex; justify-content: center; align-items: center;">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>

        </div>
    </main>

    <script>
        // 1. Gráfico Lineal (Histórico)
        const ctxLine = document.getElementById('progressionChart');
        const chartLabels = <?= $labels ?: '[]' ?>;
        const moodData = <?= $data_mood ?: '[]' ?>;
        const healthData = <?= $data_health ?: '[]' ?>;
        const financeData = <?= $data_finance ?: '[]' ?>;

        if (chartLabels.length > 0) {
            new Chart(ctxLine, {
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
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { min: 0, max: 10, grid: { color: '#E2E8F0' } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { position: 'top' } }
                }
            });
        }

        // 2. Gráfico Radial (RPG Stats)
        const ctxRadar = document.getElementById('radarChart');
        const radarStats = <?= $radarData ?>; // [Psique, Soma, Pathos, Pneuma]

        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: ['Mente (Psique)', 'Cuerpo (Soma)', 'Emoción (Pathos)', 'Espíritu (Pneuma)'],
                datasets: [{
                    label: 'Nivel Actual',
                    data: radarStats,
                    backgroundColor: 'rgba(128, 90, 213, 0.2)', // APH Accent transparent
                    borderColor: '#805AD5',
                    pointBackgroundColor: '#805AD5',
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
                        pointLabels: {
                            font: { family: 'Inter', size: 12, weight: '600' },
                            color: '#1A202C'
                        },
                        ticks: { display: false, min: 0, max: 100 }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>