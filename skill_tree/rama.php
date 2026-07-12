<?php
session_start();
// require '../db.php';

// 1. OBTENER LA RAMA ACTUAL DESDE LA URL
// Si la URL es rama.php?skill=finanzas, $current_skill será 'finanzas'
$current_skill = $_GET['skill'] ?? 'origen';

// 2. DICCIONARIO DE CONFIGURACIÓN DINÁMICA
// Aquí defines los colores e íconos de cada rama central. 
// (Más adelante esto también podría venir de tu tabla skills_catalog)
$branches_config = [
    'estudio' =>  ['name' => 'Estudio',  'icon' => '📚', 'color' => '#805AD5', 'desc' => 'Gestión de aprendizaje y desarrollo técnico.'],
    'laboral' =>  ['name' => 'Laboral',  'icon' => '💼', 'color' => '#3182CE', 'desc' => 'Ejecución profesional, liderazgo y organización.'],
    'finanzas' => ['name' => 'Finanzas', 'icon' => '💰', 'color' => '#D69E2E', 'desc' => 'Sustento material y multiplicación del capital.'],
    'salud' =>    ['name' => 'Salud',    'icon' => '🍎', 'color' => '#38A169', 'desc' => 'Base biológica, energía y estado físico.'],
    'espiritu' => ['name' => 'Espíritu', 'icon' => '🧘', 'color' => '#D53F8C', 'desc' => 'Claridad mental, resiliencia y control emocional.'],
    'origen' =>   ['name' => 'Origen APH','icon'=>'💠', 'color' => '#4A5568', 'desc' => 'El núcleo de tu identidad 3D.']
];

// Si el usuario pone una ruta que no existe, cargamos una por defecto
$branch = $branches_config[$current_skill] ?? $branches_config['estudio'];

// Simulación de progreso
$core_level = 7; 
$core_max = 10;
$progress_percent = ($core_level / $core_max) * 100;

$user = ['username' => 'Daniel', 'profession' => 'Ingeniería de Sistemas', 'profile_picture' => ''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $branch['name'] ?> | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        /* INYECCIÓN DINÁMICA DEL COLOR DE LA RAMA */
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --border-color: #E2E8F0; 
            --theme-color: <?= $branch['color'] ?>; 
            --theme-light: <?= $branch['color'] ?>1A; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; }
        .brand h2 { text-align: center; font-weight: 800; letter-spacing: 2px; color: var(--theme-color); margin-bottom: 40px;}
        .user-mini { margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 20px; display: flex; align-items: center; gap: 10px; }

        main { flex: 1; display: flex; flex-direction: column; padding: 40px; overflow-y: auto; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .title-area h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; font-family: 'Orbitron', sans-serif; text-transform: uppercase; color: var(--theme-color); }
        .title-area p { color: var(--text-muted); font-size: 1.05rem; }
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: 0.3s; }
        .btn-return:hover { border-color: var(--theme-color); color: var(--theme-color); }

        .progress-master-panel { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); text-align: center; margin-bottom: 30px; }
        .progress-title { font-size: 1.2rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; }
        .level-display { font-size: 4rem; font-family: 'Orbitron', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 20px; }
        .level-display span { color: var(--theme-color); font-size: 2rem; }
        .progress-bar-bg { width: 100%; height: 16px; background: var(--border-color); border-radius: 20px; overflow: hidden; margin-bottom: 40px; }
        .progress-bar-fill { height: 100%; background: var(--theme-color); border-radius: 20px; transition: width 1s ease-in-out; }

        .action-buttons-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .action-btn { background: var(--bg-base); border: 2px solid var(--border-color); border-radius: 12px; padding: 20px; text-decoration: none; color: var(--text-main); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; transition: 0.3s; cursor: pointer; }
        .action-btn:hover { border-color: var(--theme-color); background: var(--theme-light); transform: translateY(-3px); }
        .action-btn .icon { font-size: 2rem; }
        .action-btn .title { font-weight: 700; font-size: 1.1rem; }
        .action-btn .desc { font-size: 0.8rem; color: var(--text-muted); text-align: center; }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="user-mini">
            <div style="width: 40px; height: 40px; background: #E2E8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">👤</div>
            <div><h4><?= $user['username'] ?></h4><p><?= $user['profession'] ?></p></div>
        </div>
    </nav>

    <main>
        <div class="header-dash">
            <div class="title-area">
                <h1><span style="font-size: 2.5rem;"><?= $branch['icon'] ?></span> <?= $branch['name'] ?></h1>
                <p><?= $branch['desc'] ?></p>
            </div>
            <a href="../habilidades.php" class="btn-return">⮐ Volver a la Matriz</a>
        </div>

        <div class="progress-master-panel">
            <div class="progress-title">Progreso del Núcleo Actual</div>
            <div class="level-display"><?= $core_level ?> <span>/ <?= $core_max ?></span></div>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div></div>

            <div class="action-buttons-grid">
                <a href="aumentar.php?skill=<?= $current_skill ?>" class="action-btn">
                    <span class="icon">📈</span><span class="title">Aumentar</span>
                    <span class="desc">Gestionar nodos de especialización.</span>
                </a>
                <a href="ruta_potencia.php?skill=<?= $current_skill ?>" class="action-btn">
                    <span class="icon">⚡</span><span class="title">Ruta de Potencia</span>
                    <span class="desc">Agregar metas y notas estratégicas.</span>
                </a>
                <a href="presentacion.php?skill=<?= $current_skill ?>" class="action-btn">
                    <span class="icon">📊</span><span class="title">Presentación</span>
                    <span class="desc">Comparativa del nivel actual.</span>
                </a>
            </div>
        </div>
    </main>
</body>
</html>