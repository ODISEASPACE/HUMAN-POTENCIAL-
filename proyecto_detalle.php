<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: proyectos.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = $_GET['id'];

// 1. OBTENER DATOS DEL PROYECTO
$stmtProject = $pdo->prepare("SELECT * FROM projects_items WHERE id = ? AND user_id = ?");
$stmtProject->execute([$project_id, $user_id]);
$project = $stmtProject->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die("Registro no encontrado o no tienes permisos para verlo.");
}

// 2. OBTENER INFORMACIÓN EXTRA DEL USUARIO
// Extraemos datos adicionales como la biografía y el arquetipo para dar contexto a la vista
$stmtUser = $pdo->prepare("SELECT username, profession, bio, archetype FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// 3. OBTENER LOS REGISTROS DIARIOS VINCULADOS A ESTE PROYECTO
$stmtLogs = $pdo->prepare("
    SELECT log_date, to_char(log_date, 'DD/MM/YYYY') as f_date, 
           routines_completed, mood_score, health_score, finance_score, notes 
    FROM daily_logs 
    WHERE project_item_id = ? AND user_id = ? 
    ORDER BY log_date DESC
");
$stmtLogs->execute([$project_id, $user_id]);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Helper para el color del estado
$statusClass = 'status-' . strtolower($project['status']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); padding: 40px; line-height: 1.6; }
        
        .container { max-width: 900px; margin: 0 auto; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        
        .btn-back { display: inline-block; margin-bottom: 20px; color: var(--text-muted); text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-back:hover { color: var(--accent); }

        .header-project { border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px; }
        .category-label { font-size: 0.85rem; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; }
        .title { font-size: 2.5rem; font-weight: 800; margin: 10px 0; }
        
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-bottom: 15px; }
        .status-activo { background: #EBF4FF; color: #3182CE; }
        .status-completado { background: #C6F6D5; color: #276749; }
        .status-pausado { background: #FEFCBF; color: #B7791F; }

        .user-context { background: #F7FAFC; padding: 20px; border-radius: 12px; margin-bottom: 30px; display: flex; gap: 20px; align-items: center; border: 1px solid var(--border-color); }
        .user-context-info h4 { margin-bottom: 5px; color: var(--accent); }
        .user-context-info p { font-size: 0.9rem; color: var(--text-muted); }

        .description { font-size: 1.1rem; color: #4A5568; margin-bottom: 40px; white-space: pre-line; }

        .logs-section h3 { font-size: 1.5rem; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; }
        
        /* Línea de tiempo para los registros */
        .timeline { display: flex; flex-direction: column; gap: 20px; }
        .log-entry { border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; background: #fff; transition: 0.3s; }
        .log-entry:hover { border-color: var(--accent); box-shadow: 0 4px 12px rgba(128,90,213,0.05); }
        .log-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .log-date { font-weight: 700; color: var(--text-main); }
        .log-metrics { display: flex; gap: 10px; font-size: 0.85rem; font-weight: 600; }
        .log-metrics span { background: #EDF2F7; padding: 4px 10px; border-radius: 6px; color: #4A5568; }
        .log-notes { color: #4A5568; font-size: 0.95rem; }
        
        .empty-logs { text-align: center; color: var(--text-muted); padding: 30px; font-style: italic; background: #F7FAFC; border-radius: 8px; }
    </style>
</head>
<body>

    <div class="container">
        <a href="proyectos.php" class="btn-back">← Volver al Repositorio</a>
        
        <div class="header-project">
            <div class="category-label"><?= htmlspecialchars($project['category']) ?></div>
            <h1 class="title"><?= htmlspecialchars($project['title']) ?></h1>
            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($project['status']) ?></span>
            <div style="color: var(--text-muted); font-size: 0.9rem;">
                Creado el: <?= date("d de M, Y", strtotime($project['created_at'])) ?>
            </div>
        </div>

        <!-- Información Extra del Usuario -->
        <div class="user-context">
            <div style="font-size: 2rem;">👤</div>
            <div class="user-context-info">
                <h4>Contexto del Creador: <?= htmlspecialchars($user['username']) ?></h4>
                <p><strong>Profesión:</strong> <?= htmlspecialchars($user['profession'] ?? 'No especificada') ?> | <strong>Arquetipo:</strong> Tipo <?= htmlspecialchars($user['archetype'] ?? 'N/A') ?></p>
                <p><em>"<?= htmlspecialchars($user['bio'] ?? 'Sin biografía disponible.') ?>"</em></p>
            </div>
        </div>

        <div class="description">
            <?= nl2br(htmlspecialchars($project['description'])) ?>
        </div>

        <div class="logs-section">
            <h3>📖 Historial de Registros Diarios</h3>
            
            <div class="timeline">
                <?php if(empty($logs)): ?>
                    <div class="empty-logs">Aún no has registrado avances diarios para este elemento. Usa la Bitácora para empezar a documentar.</div>
                <?php else: ?>
                    <?php foreach($logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-header">
                                <div class="log-date">📅 <?= $log['f_date'] ?></div>
                                <div class="log-metrics">
                                    <span title="Psique">🧠 <?= $log['mood_score'] ?></span>
                                    <span title="Soma">💪 <?= $log['health_score'] ?></span>
                                    <span title="Economía">💰 <?= $log['finance_score'] ?></span>
                                    <span title="Tareas">✅ <?= $log['routines_completed'] ?></span>
                                </div>
                            </div>
                            <div class="log-notes">
                                <?= nl2br(htmlspecialchars($log['notes'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>