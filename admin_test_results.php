<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: dashboard.php"); exit;
}

try {
    $host_v1 = 'aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com';
    $pdo_old = new PDO("pgsql:host=$host_v1;port=5432;dbname=postgres;", 'postgres', 'Limitless20xx', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$current_page = basename($_SERVER['PHP_SELF']);

$stmt = $pdo_old->query("
    SELECT r.id, u.name as user_name, r.score_x_discipline, r.score_y_purpose, r.archetype, r.created_at 
    FROM test_results r 
    LEFT JOIN users u ON r.user_id = u.id 
    ORDER BY r.id ASC
");
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados Tests | APH OS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Mismos estilos que admin_users.php, minimizados para lectura */
        :root { --bg-base: #111827; --bg-panel: #1F2937; --text-main: #F9FAFB; --text-muted: #9CA3AF; --accent: #3B82F6; --accent-light: rgba(59, 130, 246, 0.15); --border-color: #374151; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .admin-badge { background: #EF4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: auto; }
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header-dash { margin-bottom: 30px; } .header-dash h1 { font-size: 2rem; font-weight: 800; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .arch-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand"><h2>APH <span style="color:#EF4444">OS</span></h2></div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="nav-link <?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>">⌂ Panel Global <span class="admin-badge">Admin</span></a>
            <a href="admin_users.php" class="nav-link <?= ($current_page == 'admin_users.php') ? 'active' : '' ?>">👥 Gestión (users)</a>
            <a href="admin_test_results.php" class="nav-link <?= ($current_page == 'admin_test_results.php') ? 'active' : '' ?>">📊 test_results</a>
            <a href="admin_alpha_feedback.php" class="nav-link <?= ($current_page == 'admin_alpha_feedback.php') ? 'active' : '' ?>">💬 alpha_feedback</a>
            <a href="admin_tracktime_goals.php" class="nav-link <?= ($current_page == 'admin_tracktime_goals.php') ? 'active' : '' ?>">🎯 tracktime_goals</a>
        </div>
    </nav>
    <main>
        <div class="header-dash">
            <h1>Resultados del Test</h1>
            <p style="color:var(--text-muted)">Puntuaciones X/Y y arquetipos asignados.</p>
        </div>
        <div class="card">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Usuario</th><th>Disciplina (X)</th><th>Propósito (Y)</th><th>Arquetipo</th><th>Fecha</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $r): ?>
                        <tr>
                            <td style="color:var(--text-muted)">#<?= $r['id'] ?></td>
                            <td style="font-weight:600"><?= htmlspecialchars($r['user_name'] ?? 'Usuario ID ' . $r['user_id']) ?></td>
                            <td><?= $r['score_x_discipline'] ?></td>
                            <td><?= $r['score_y_purpose'] ?></td>
                            <td><span class="arch-badge"><?= htmlspecialchars($r['archetype']) ?></span></td>
                            <td style="color:var(--text-muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>