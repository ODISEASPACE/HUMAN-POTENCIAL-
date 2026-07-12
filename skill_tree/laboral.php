<?php
session_start();
// require '../db.php'; // Ajusta la ruta a tu conexión

// Simulación de datos para visualización
$user = ['username' => 'Daniel', 'profession' => 'Ingeniería de Sistemas', 'profile_picture' => ''];
$core_level = 10;
$core_max = 10;
$progress_percent = ($core_level / $core_max) * 100;

// Sub-habilidades (Esto vendría del campo JSON en la BD)
$sub_skills = [
    ['name' => 'Lógica de Programación', 'level' => 5, 'max' => 10],
    ['name' => 'Python', 'level' => 3, 'max' => 10],
    ['name' => 'JavaScript & Web', 'level' => 4, 'max' => 10],
    ['name' => 'Java / C++', 'level' => 2, 'max' => 10],
    ['name' => 'Dart & Flutter', 'level' => 1, 'max' => 10]
];

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
    <title>Estudio | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --accent: #805AD5; --accent-light: rgba(128, 90, 213, 0.1); --border-color: #E2E8F0; 
            --theme-color: #805AD5; /* Color específico de esta rama */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }

        /* Main Content */
        main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 40px; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .title-area h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; display: flex; align-items: center; gap: 15px; }
        .title-area h1 span { color: var(--theme-color); font-family: 'Orbitron', sans-serif; }
        .title-area p { color: var(--text-muted); font-size: 1.1rem; }
        
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 0.9rem; }
        .btn-return:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

        /* Progress Banner */
        .progress-banner { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .progress-header h3 { font-size: 1.2rem; font-weight: 700; }
        .progress-header .level-badge { background: rgba(128, 90, 213, 0.1); color: var(--theme-color); padding: 5px 15px; border-radius: 20px; font-weight: 800; font-family: 'Orbitron', sans-serif; font-size: 1.1rem; }
        
        .progress-bar-bg { width: 100%; height: 12px; background: var(--border-color); border-radius: 10px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: var(--theme-color); border-radius: 10px; transition: width 0.5s ease; }

        /* Sub-skills Grid */
        .skills-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .skill-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; transition: 0.3s; display: flex; flex-direction: column; gap: 15px; }
        .skill-card:hover { border-color: var(--theme-color); box-shadow: 0 10px 20px rgba(128, 90, 213, 0.05); transform: translateY(-3px); }
        
        .skill-card-header { display: flex; justify-content: space-between; align-items: center; }
        .skill-card-header h4 { font-size: 1.1rem; font-weight: 700; color: var(--text-main); }
        .skill-fraction { font-family: 'Orbitron', sans-serif; font-size: 0.9rem; font-weight: 700; color: var(--text-muted); }
        
        .skill-actions { display: flex; gap: 10px; margin-top: auto; }
        .btn-upgrade { flex: 1; background: var(--bg-base); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; color: var(--text-main); }
        .btn-upgrade:hover { background: var(--theme-color); color: white; border-color: var(--theme-color); }
        .btn-upgrade:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="../dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="../estado-humano.php" class="nav-link">👤 Estado Humano</a>
            <a href="../registro-diario.php" class="nav-link">⏱ Registro Diario</a>
            <a href="../proyectos.php" class="nav-link">🚀 Proyectos</a>
            <a href="../habilidades.php" class="nav-link active">🌳 Árbol de Habilidades</a>
        </div>
        <div class="user-mini">
            <?= renderAvatar($user['profile_picture']) ?>
            <div class="user-info-mini">
                <h4><?= htmlspecialchars($user['username']) ?></h4>
                <p><?= htmlspecialchars($user['profession']) ?></p>
            </div>
        </div>
    </nav>

    <main>
        <div class="header-dash">
            <div class="title-area">
                <h1>📚 <span>ESTUDIO</span></h1>
                <p>Gestión de aprendizaje, lógica y desarrollo técnico.</p>
            </div>
            <a href="../habilidades.php" class="btn-return">⮐ Volver a la Matriz</a>
        </div>

        <div class="progress-banner">
            <div class="progress-header">
                <h3>Nivel del Núcleo</h3>
                <div class="level-badge">NIVEL <?= $core_level ?> / <?= $core_max ?></div>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div>
            </div>
        </div>

        <h3 style="margin-bottom: 20px; color: var(--text-muted); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Nodos de Especialización</h3>
        
        <div class="skills-grid">
            <?php foreach($sub_skills as $skill): 
                $percent = ($skill['level'] / $skill['max']) * 100;
            ?>
                <div class="skill-card">
                    <div class="skill-card-header">
                        <h4><?= $skill['name'] ?></h4>
                        <div class="skill-fraction"><?= $skill['level'] ?>/<?= $skill['max'] ?></div>
                    </div>
                    <div class="progress-bar-bg" style="height: 6px;">
                        <div class="progress-bar-fill" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <div class="skill-actions">
                        <button class="btn-upgrade" <?= ($skill['level'] >= $skill['max']) ? 'disabled' : '' ?>>
                            Invertir Puntos
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

</body>
</html>