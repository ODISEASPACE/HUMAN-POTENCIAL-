<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: dashboard.php");
    exit;
}

try {
    $host_v1 = 'aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com';
    $pdo_old = new PDO("pgsql:host=$host_v1;port=5432;dbname=postgres;", 'postgres', 'Limitless20xx', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { die("Error conectando a la BD antigua: " . $e->getMessage()); }

$current_page = basename($_SERVER['PHP_SELF']);

// Consulta de usuarios
$stmt = $pdo_old->query("SELECT id, name, email, is_verified, created_at FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios | APH OS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-base: #111827; --bg-panel: #1F2937; --text-main: #F9FAFB; --text-muted: #9CA3AF; --accent: #3B82F6; --accent-light: rgba(59, 130, 246, 0.15); --border-color: #374151; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar (Reutilizado) */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .admin-badge { background: #EF4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: auto; }
        
        /* Contenido Principal y Tablas */
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header-dash { margin-bottom: 30px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; }
        
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .status-true { background: rgba(16, 185, 129, 0.2); color: #10B981; }
        .status-false { background: rgba(245, 158, 11, 0.2); color: #F59E0B; }
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
            <h1>Gestión de Usuarios</h1>
            <p style="color:var(--text-muted)">Visualización de la tabla `users`</p>
        </div>
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Nombre</th><th>Email</th><th>Verificado</th><th>Fecha de Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td style="color:var(--text-muted)">#<?= $u['id'] ?></td>
                            <td style="font-weight:600"><?= htmlspecialchars($u['name'] ?? 'Sin nombre') ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="status-badge <?= $u['is_verified'] ? 'status-true' : 'status-false' ?>">
                                    <?= $u['is_verified'] ? 'Sí' : 'No' ?>
                                </span>
                            </td>
                            <td style="color:var(--text-muted)"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>