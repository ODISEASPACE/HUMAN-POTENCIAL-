<?php
session_start();
require 'db.php'; 

// Redirección de seguridad (Descomentar en producción)
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
*/
$user_id = $_SESSION['user_id'] ?? 1;

$user = [
    'username' => 'Daniel',
    'profession' => 'Ingeniería de Sistemas',
    'profile_picture' => ''
];

// --- 1. OBTENER EL CATÁLOGO DE HABILIDADES ---
$stmt = $pdo->query("SELECT * FROM skills_catalog");
$catalog = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. OBTENER PROGRESO DEL USUARIO ---
$stmt = $pdo->prepare("SELECT node_key, current_level, unlocked FROM user_skills WHERE user_id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_skills = [];
foreach ($userData as $row) {
    $user_skills[$row['node_key']] = $row;
}

// --- 3. CONSTRUIR EL ÁRBOL DINÁMICO ---
$nodes = [];
$spacing_multiplier = 1.8; // 🔥 MULTIPLICADOR ESPACIAL: Aumenta este número si quieres los nodos más separados

foreach ($catalog as $item) {
    $key = $item['node_key'];
    $level = isset($user_skills[$key]) ? floor($user_skills[$key]['current_level']) : 0;
    $unlocked = isset($user_skills[$key]) ? $user_skills[$key]['unlocked'] : false;

    $status = 'locked';
    if ($key === 'origen' || $level >= $item['max_level']) {
        $status = 'maxed';
    } elseif ($level > 0 || $unlocked) {
        $status = 'unlocked';
    }

    $nodes[$key] = [
        'id' => $key,
        'label' => $item['label'],
        'x' => $item['x'] * $spacing_multiplier, // Expansión dinámica en X
        'y' => $item['y'] * $spacing_multiplier, // Expansión dinámica en Y
        'level' => $level,
        'max' => $item['max_level'],
        'status' => $status,
        'parent' => $item['parent_key'],
        'route' => $item['route']
    ];
}

// --- 4. CALCULAR VISIBILIDAD ---
foreach ($nodes as $key => &$node) {
    if ($key === 'origen' || $node['status'] !== 'locked') {
        $node['visibility_class'] = 'default-visible';
    } else {
        $parentKey = $node['parent'];
        $parentStatus = isset($nodes[$parentKey]) ? $nodes[$parentKey]['status'] : 'locked';
        
        if ($parentStatus !== 'locked') {
            $node['visibility_class'] = 'default-visible';
        } else {
            $node['visibility_class'] = 'deep-locked';
        }
    }
}
unset($node);

function renderAvatar($avatarData) {
    if (empty($avatarData)) return "<div class='avatar-circle' style='background: var(--bg-panel); color: var(--text-main);'>👤</div>";
    if (strpos($avatarData, '.') !== false) return "<div class='avatar-circle' style='background-image: url(\"{$avatarData}\"); background-size: cover;'></div>";
    return "<div class='avatar-circle' style='background: var(--accent-light); color: var(--accent);'>{$avatarData}</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Árbol de Habilidades | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        /* === MOTOR DE TEMAS (CSS VARIABLES) === */
        /* 1. Tema por Defecto (Light OS) */
        :root { 
            --bg-base: #FAFAFC; 
            --bg-panel: #FFFFFF; 
            --text-main: #1A202C; 
            --text-muted: #718096; 
            --accent: #805AD5; 
            --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0; 
            --gold: #ecc94b;
            --locked-color: #E2E8F0;
            --node-radius: 12px;
            --grid-color: var(--border-color);
            --line-filter: none;
            --shadow-panel: 0 10px 25px rgba(0,0,0,0.02);
            --backdrop: none;
        }

        /* 2. Tema Cyberpunk */
        [data-theme="cyberpunk"] {
            --bg-base: #0a0a0e; 
            --bg-panel: #13131a; 
            --text-main: #00ffcc; 
            --text-muted: #4a5568; 
            --accent: #ff007f; 
            --accent-light: rgba(255, 0, 127, 0.15); 
            --border-color: #2a2a35; 
            --gold: #f5df4d;
            --locked-color: #2a2a35;
            --node-radius: 0px; /* Bordes afilados */
            --grid-color: #1a1a24;
            --line-filter: drop-shadow(0 0 4px var(--accent));
            --shadow-panel: 0 0 20px rgba(0, 255, 204, 0.05);
            --backdrop: none;
        }

        /* 3. Tema Orgánico */
        [data-theme="organic"] {
            --bg-base: #f4f1ea; 
            --bg-panel: #ffffff; 
            --text-main: #2c3e50; 
            --text-muted: #7f8c8d; 
            --accent: #27ae60; 
            --accent-light: rgba(39, 174, 96, 0.1); 
            --border-color: #dcdde1; 
            --gold: #f39c12;
            --locked-color: #dcdde1;
            --node-radius: 50%; /* Nodos circulares */
            --grid-color: #e8e6df;
            --line-filter: none;
            --shadow-panel: 0 15px 30px rgba(44, 62, 80, 0.05);
            --backdrop: none;
        }

        /* 4. Tema Void (Minimalista Oscuro) */
        [data-theme="void"] {
            --bg-base: #000000; 
            --bg-panel: #0a0a0a; 
            --text-main: #ffffff; 
            --text-muted: #666666; 
            --accent: #ffffff; 
            --accent-light: rgba(255, 255, 255, 0.1); 
            --border-color: #333333; 
            --gold: #bf953f;
            --locked-color: #222222;
            --node-radius: 30px; /* Nodos en forma de píldora */
            --grid-color: #111111;
            --line-filter: none;
            --shadow-panel: none;
            --backdrop: none;
        }

        /* 5. Tema Holográfico (Glassmorphism) */
        [data-theme="holographic"] {
            --bg-base: #0f172a; 
            --bg-panel: rgba(30, 41, 59, 0.6); 
            --text-main: #e2e8f0; 
            --text-muted: #94a3b8; 
            --accent: #38bdf8; 
            --accent-light: rgba(56, 189, 248, 0.2); 
            --border-color: rgba(255, 255, 255, 0.1); 
            --gold: #fcd34d;
            --locked-color: rgba(255, 255, 255, 0.05);
            --node-radius: 16px;
            --grid-color: rgba(56, 189, 248, 0.05);
            --line-filter: drop-shadow(0 0 3px var(--accent));
            --shadow-panel: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            --backdrop: blur(10px);
        }

        /* === ESTILOS GENERALES === */
        * { margin: 0; padding: 0; box-sizing: border-box; transition: background-color 0.3s, border-color 0.3s; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0; backdrop-filter: var(--backdrop); }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout { margin-top: 15px; text-align: center; font-size: 0.85rem; color: #E53E3E; text-decoration: none; font-weight: 600; padding: 8px; border-radius: 6px; }

        main { flex: 1; display: flex; flex-direction: column; overflow: hidden; padding: 40px; }
        .header-dash { margin-bottom: 30px; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; z-index: 10; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem; backdrop-filter: var(--backdrop); }
        .btn-return:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

        .tree-viewport { flex: 1; position: relative; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-panel); cursor: grab; backdrop-filter: var(--backdrop); }
        .tree-viewport:active { cursor: grabbing; }
        .tree-viewport::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: radial-gradient(var(--grid-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.6; z-index: 0; pointer-events: none; }
        
        .tree-canvas { position: absolute; top: 50%; left: 50%; transform-origin: 0 0; z-index: 1; transition: opacity 0.3s; }
        
        .zoom-controls { position: absolute; bottom: 20px; right: 20px; display: flex; gap: 10px; z-index: 20; }
        .zoom-btn { background: var(--bg-panel); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 10px; font-size: 1.2rem; font-weight: bold; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-panel); backdrop-filter: var(--backdrop); transition: 0.2s; }
        .zoom-btn:hover { color: var(--accent); border-color: var(--accent); transform: translateY(-2px); }
        
        /* Ocultar nodos lejanos */
        .tree-canvas.hide-deep .deep-locked { display: none !important; opacity: 0; transition: 0.3s; }

        /* SVG y Líneas Suavizadas */
        svg { position: absolute; top: 0; left: 0; width: 0; height: 0; overflow: visible; pointer-events: none; z-index: 1; filter: var(--line-filter); }
        .tree-link { transition: stroke 0.3s; }
        
        /* NODOS */
        .node { position: absolute; width: 120px; height: 100px; background: var(--bg-panel); border: 2px solid var(--border-color); border-radius: var(--node-radius); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; z-index: 5; text-decoration: none; color: var(--text-main); transform: translate(-50%, -50%); transition: 0.3s; backdrop-filter: var(--backdrop); }
        
        /* Ajuste específico para el tema orgánico (nodos redondos necesitan ser cuadrados perfectos) */
        [data-theme="organic"] .node:not(.core) { width: 110px; height: 110px; }

        .node.core { width: 150px; height: 150px; background: var(--accent); border-radius: 50%; color: white; border: 6px solid var(--bg-panel); box-shadow: 0 0 30px var(--accent-light); cursor: default; }
        .node.core span { font-size: 0.75rem; font-family: 'Inter', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .node.core strong { font-size: 1.7rem; font-family: 'Orbitron', sans-serif; }
        
        .node.locked { filter: grayscale(100%) opacity(0.5); pointer-events: none; border-color: var(--locked-color); }
        .node.unlocked { border-color: var(--gold); box-shadow: 0 5px 15px rgba(236, 201, 75, 0.1); }
        .node.maxed { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 5px 15px var(--accent-light); }
        .node:not(.core):not(.locked):hover { transform: translate(-50%, -50%) scale(1.15); z-index: 20; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        
        .node-level { font-family: 'Orbitron', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--text-muted); }
        .node.unlocked .node-level { color: var(--gold); }
        .node.maxed .node-level { color: var(--accent); }
        .node-label { font-size: 0.75rem; text-transform: uppercase; margin-top: 5px; text-align: center; font-weight: 700; letter-spacing: 0.5px; padding: 0 5px; }
    </style>
</head>
<!-- Iniciamos con el tema guardado o el default -->
<body data-theme="default">

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="estado-humano.php" class="nav-link">👤 Estado Humano</a>
            <a href="registro-diario.php" class="nav-link">⏱ Registro Diario</a>
            <a href="proyectos.php" class="nav-link">🚀 Proyectos</a>
            <a href="habilidades.php" class="nav-link active">🌳 Árbol de Habilidades</a>
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
            <div>
                <h1>Matriz de Habilidades</h1>
                <p>Visualiza y expande tu progreso cognitivo y estructural.</p>
            </div>
            <a href="arbol_de_decisiones.php" class="btn-return">
                <span>⮐ Volver a Identidad 3D</span>
            </a>
        </div>

        <div class="tree-viewport" id="viewport">
            <div class="zoom-controls">
                <!-- NUEVO BOTÓN DE TEMAS -->
                <button class="zoom-btn" onclick="cycleTheme()" title="Cambiar Diseño del Mapa">🗂️</button>
                <button class="zoom-btn" id="btn-eye" onclick="toggleVisibility()" title="Mostrar todo el árbol">🔒</button>
                <button class="zoom-btn" onclick="zoomIn()">+</button>
                <button class="zoom-btn" onclick="zoomOut()">-</button>
                <button class="zoom-btn" onclick="resetView()">⌂</button>
            </div>

            <div class="tree-canvas hide-deep" id="canvas">
                <svg>
                    <?php 
                    // Dibujar conexiones con <path> para líneas modernas y suaves
                    foreach($nodes as $id => $node) {
                        if ($node['parent'] && isset($nodes[$node['parent']])) {
                            $parent = $nodes[$node['parent']];
                            
                            // Determinación de colores según el tema usando variables CSS dinámicas integradas en el stroke
                            $statusColor = ($node['status'] == 'locked') ? 'var(--locked-color)' : (($node['status'] == 'maxed') ? 'var(--accent)' : 'var(--gold)');
                            $dash = ($node['status'] == 'locked') ? '5,5' : '0';
                            $width = ($node['status'] == 'maxed') ? '3' : '2';
                            
                            // Trazado de línea con puntas redondeadas (stroke-linecap)
                            echo "<path class='tree-link {$node['visibility_class']}' d='M {$parent['x']} {$parent['y']} L {$node['x']} {$node['y']}' stroke='{$statusColor}' stroke-width='{$width}' stroke-dasharray='{$dash}' fill='none' stroke-linecap='round'></path>";
                        }
                    }
                    ?>
                </svg>

                <?php foreach($nodes as $id => $node): ?>
                    <?php if($id === 'origen'): ?>
                        <div class="node core <?= $node['visibility_class'] ?>" style="left: <?= $node['x'] ?>px; top: <?= $node['y'] ?>px;">
                            <span>Origen</span>
                            <strong>APH</strong>
                        </div>
                    <?php else: ?>
                        <a href="skill_tree/<?= $node['route'] ?>.php" class="node <?= $node['status'] ?> <?= $node['visibility_class'] ?>" style="left: <?= $node['x'] ?>px; top: <?= $node['y'] ?>px;">
                            <div class="node-level"><?= $node['level'] ?>/<?= $node['max'] ?></div>
                            <div class="node-label"><?= $node['label'] ?></div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        // --- LÓGICA DE TEMAS (Capas) ---
        const themes = ['default', 'cyberpunk', 'organic', 'void', 'holographic'];
        let currentThemeIndex = 0;

        // Cargar tema guardado si existe
        const savedTheme = localStorage.getItem('aph_skill_theme');
        if (savedTheme && themes.includes(savedTheme)) {
            document.body.setAttribute('data-theme', savedTheme);
            currentThemeIndex = themes.indexOf(savedTheme);
        }

        function cycleTheme() {
            currentThemeIndex = (currentThemeIndex + 1) % themes.length;
            const newTheme = themes[currentThemeIndex];
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('aph_skill_theme', newTheme);
        }

        // --- Lógica de Pan y Zoom ---
        const viewport = document.getElementById('viewport');
        const canvas = document.getElementById('canvas');
        
        let scale = 1;
        let panning = false;
        let pointX = 0;
        let pointY = 0;
        let start = { x: 0, y: 0 };

        function setTransform() {
            canvas.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`;
        }

        // Centrar ligeramente el mapa al iniciar basándose en el escalado inicial
        function centerInit() {
            scale = 0.8; // Empieza un poco alejado para ver mejor el entorno
            setTransform();
        }
        window.onload = centerInit;

        viewport.onmousedown = function (e) {
            e.preventDefault();
            start = { x: e.clientX - pointX, y: e.clientY - pointY };
            panning = true;
        }
        viewport.onmouseup = function (e) { panning = false; }
        viewport.onmouseleave = function (e) { panning = false; }
        viewport.onmousemove = function (e) {
            e.preventDefault();
            if (!panning) return;
            pointX = (e.clientX - start.x);
            pointY = (e.clientY - start.y);
            setTransform();
        }

        viewport.onwheel = function (e) {
            e.preventDefault();
            let xs = (e.clientX - pointX) / scale;
            let ys = (e.clientY - pointY) / scale;
            let delta = (e.wheelDelta ? e.wheelDelta : -e.deltaY);
            
            (delta > 0) ? (scale *= 1.1) : (scale /= 1.1);
            scale = Math.max(0.2, Math.min(scale, 3.0)); 
            
            pointX = e.clientX - xs * scale;
            pointY = e.clientY - ys * scale;
            setTransform();
        }

        function zoomIn() { scale = Math.min(scale * 1.2, 3.0); setTransform(); }
        function zoomOut() { scale = Math.max(scale / 1.2, 0.2); setTransform(); }
        function resetView() { scale = 0.8; pointX = 0; pointY = 0; setTransform(); }

        // --- Lógica del Ojo ---
        function toggleVisibility() {
            const btn = document.getElementById('btn-eye');
            canvas.classList.toggle('hide-deep');
            
            if(canvas.classList.contains('hide-deep')) {
                btn.innerHTML = '🔒';
                btn.title = 'Mostrar todo el árbol';
            } else {
                btn.innerHTML = '👁️';
                btn.title = 'Ocultar ramas lejanas';
            }
        }
    </script>
</body>
</html>