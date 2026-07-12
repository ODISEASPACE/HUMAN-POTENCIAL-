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
$spacing_multiplier = 1.8; 

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
        'x' => $item['x'] * $spacing_multiplier, 
        'y' => $item['y'] * $spacing_multiplier, 
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
        /* === VARIABLES GLOBALES DE LA PLATAFORMA (No cambian) === */
        :root { 
            --bg-base: #FAFAFC; 
            --bg-panel: #FFFFFF; 
            --text-main: #1A202C; 
            --text-muted: #718096; 
            --accent: #805AD5; 
            --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0; 
            --gold: #ecc94b;
        }

        /* === VARIABLES AISLADAS DEL MAPA (Light OS por defecto) === */
        .tree-viewport {
            --map-bg: #FFFFFF;
            --map-grid: #E2E8F0;
            --node-bg: #FFFFFF;
            --node-border: #E2E8F0;
            --node-text: #1A202C;
            --node-text-muted: #718096;
            --locked-line: #E2E8F0;
            --node-radius: 12px;
            --line-filter: none;
            --map-shadow: 0 10px 25px rgba(0,0,0,0.02);
            --map-backdrop: none;
        }

        /* Tema 2: Cyberpunk (Aplicado SOLO al viewport) */
        .tree-viewport[data-theme="cyberpunk"] {
            --map-bg: #0a0a0e;
            --map-grid: #1a1a24;
            --node-bg: #13131a;
            --node-border: #2a2a35;
            --node-text: #00ffcc;
            --node-text-muted: #4a5568;
            --locked-line: #2a2a35;
            --node-radius: 0px; 
            --line-filter: drop-shadow(0 0 4px var(--accent));
        }

        /* Tema 3: Orgánico */
        .tree-viewport[data-theme="organic"] {
            --map-bg: #f4f1ea;
            --map-grid: #e8e6df;
            --node-bg: #ffffff;
            --node-border: #dcdde1;
            --node-text: #2c3e50;
            --node-text-muted: #7f8c8d;
            --locked-line: #dcdde1;
            --node-radius: 50%; 
        }

        /* Tema 4: Void (Minimalista) */
        .tree-viewport[data-theme="void"] {
            --map-bg: #000000;
            --map-grid: #111111;
            --node-bg: #0a0a0a;
            --node-border: #333333;
            --node-text: #ffffff;
            --node-text-muted: #666666;
            --locked-line: #222222;
            --node-radius: 30px;
        }

        /* Tema 5: Holográfico */
        .tree-viewport[data-theme="holographic"] {
            --map-bg: #0f172a;
            --map-grid: rgba(56, 189, 248, 0.05);
            --node-bg: rgba(30, 41, 59, 0.6);
            --node-border: rgba(255, 255, 255, 0.1);
            --node-text: #e2e8f0;
            --node-text-muted: #94a3b8;
            --locked-line: rgba(255, 255, 255, 0.05);
            --node-radius: 16px;
            --line-filter: drop-shadow(0 0 3px var(--accent));
            --map-backdrop: blur(10px);
        }

        /* === ESTILOS GENERALES (Plataforma) === */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s;}
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
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .btn-return:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

        /* === ESTILOS DEL MAPA DE HABILIDADES === */
        .tree-viewport { 
            flex: 1; 
            position: relative; 
            background: var(--map-bg); /* Usa la variable aislada */
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: var(--map-shadow); 
            cursor: grab; 
            transition: background-color 0.4s;
        }
        .tree-viewport:active { cursor: grabbing; }
        .tree-viewport::before { 
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: radial-gradient(var(--map-grid) 1px, transparent 1px); 
            background-size: 40px 40px; opacity: 0.6; z-index: 0; pointer-events: none; 
            transition: background-image 0.4s;
        }
        
        .tree-canvas { position: absolute; top: 50%; left: 50%; transform-origin: 0 0; z-index: 1; }
        
        .zoom-controls { position: absolute; bottom: 20px; right: 20px; display: flex; gap: 10px; z-index: 20; }
        .zoom-btn { background: var(--bg-panel); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 10px; font-size: 1.2rem; font-weight: bold; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.2s; }
        .zoom-btn:hover { color: var(--accent); border-color: var(--accent); transform: translateY(-2px); }
        
        /* === MENÚ DESPLEGABLE DE TEMAS === */
        .theme-menu-container { position: relative; display: flex; }
        .theme-dropdown {
            position: absolute;
            bottom: 55px;
            right: 0;
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: none;
            flex-direction: column;
            min-width: 150px;
            z-index: 100;
        }
        .theme-dropdown.show { display: flex; }
        .theme-option {
            padding: 10px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            transition: 0.2s;
        }
        .theme-option:hover, .theme-option.active { background: var(--accent-light); color: var(--accent); }

        /* Ocultar nodos lejanos */
        .tree-canvas.hide-deep .deep-locked { display: none !important; opacity: 0; transition: 0.3s; }

        /* === SOLUCIÓN DEFINITIVA A LAS LÍNEAS SVG === */
        /* Al darle un tamaño gigante y centrarlo con viewBox, garantizamos que ningún navegador recorte las líneas */
        .svg-layer { 
            position: absolute; 
            top: -2500px; 
            left: -2500px; 
            width: 5000px; 
            height: 5000px; 
            pointer-events: none; 
            z-index: 1; 
            filter: var(--line-filter); 
        }
        .tree-link { transition: stroke 0.4s; }
        
        /* === ESTILOS DE LOS NODOS (Usando variables del mapa) === */
        .node { 
            position: absolute; width: 120px; height: 100px; 
            background: var(--node-bg); 
            border: 2px solid var(--node-border); 
            border-radius: var(--node-radius); 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            cursor: pointer; z-index: 5; text-decoration: none; color: var(--node-text); 
            transform: translate(-50%, -50%); transition: 0.3s; backdrop-filter: var(--map-backdrop); 
        }
        
        /* Ajuste orgánico */
        .tree-viewport[data-theme="organic"] .node:not(.core) { width: 110px; height: 110px; }

        .node.core { width: 150px; height: 150px; background: var(--accent); border-radius: 50%; color: white; border: 6px solid var(--node-bg); box-shadow: 0 0 30px var(--accent-light); cursor: default; }
        .node.core span { font-size: 0.75rem; font-family: 'Inter', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .node.core strong { font-size: 1.7rem; font-family: 'Orbitron', sans-serif; }
        
        .node.locked { filter: grayscale(100%) opacity(0.5); pointer-events: none; border-color: var(--locked-line); }
        .node.unlocked { border-color: var(--gold); box-shadow: 0 5px 15px rgba(236, 201, 75, 0.15); }
        .node.maxed { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 5px 15px var(--accent-light); }
        .node:not(.core):not(.locked):hover { transform: translate(-50%, -50%) scale(1.15); z-index: 20; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        
        .node-level { font-family: 'Orbitron', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--node-text-muted); }
        .node.unlocked .node-level { color: var(--gold); }
        .node.maxed .node-level { color: var(--accent); }
        .node-label { font-size: 0.75rem; text-transform: uppercase; margin-top: 5px; text-align: center; font-weight: 700; letter-spacing: 0.5px; padding: 0 5px; }
    </style>
</head>
<body>

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

        <div class="tree-viewport" id="viewport" data-theme="default">
            
            <div class="zoom-controls">
                <div class="theme-menu-container">
                    <button class="zoom-btn" onclick="toggleThemeMenu()" title="Capas de Diseño">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 12l10 5 10-5M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="theme-dropdown" id="themeDropdown">
                        <div class="theme-option active" data-theme-val="default" onclick="setTheme('default')">Light OS</div>
                        <div class="theme-option" data-theme-val="cyberpunk" onclick="setTheme('cyberpunk')">Cyberpunk</div>
                        <div class="theme-option" data-theme-val="organic" onclick="setTheme('organic')">Orgánico</div>
                        <div class="theme-option" data-theme-val="void" onclick="setTheme('void')">Void</div>
                        <div class="theme-option" data-theme-val="holographic" onclick="setTheme('holographic')">Holográfico</div>
                    </div>
                </div>

                <button class="zoom-btn" id="btn-eye" onclick="toggleVisibility()" title="Mostrar todo el árbol">🔒</button>
                <button class="zoom-btn" onclick="zoomIn()">+</button>
                <button class="zoom-btn" onclick="zoomOut()">-</button>
                <button class="zoom-btn" onclick="resetView()">⌂</button>
            </div>

            <div class="tree-canvas hide-deep" id="canvas">
                <svg class="svg-layer" viewBox="-2500 -2500 5000 5000">
                    <?php 
                    foreach($nodes as $id => $node) {
                        if ($node['parent'] && isset($nodes[$node['parent']])) {
                            $parent = $nodes[$node['parent']];
                            
                            $statusColor = ($node['status'] == 'locked') ? 'var(--locked-line)' : (($node['status'] == 'maxed') ? 'var(--accent)' : 'var(--gold)');
                            $dash = ($node['status'] == 'locked') ? '5,5' : '0';
                            $width = ($node['status'] == 'maxed') ? '3' : '2';
                            
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
        // --- LÓGICA DE TEMAS (Menú de Capas) ---
        function toggleThemeMenu() {
            document.getElementById('themeDropdown').classList.toggle('show');
        }

        function setTheme(theme) {
            // Aplicar tema SOLO al viewport
            document.getElementById('viewport').setAttribute('data-theme', theme);
            localStorage.setItem('aph_skill_theme', theme);
            
            // Cerrar menú
            document.getElementById('themeDropdown').classList.remove('show');
            
            // Actualizar estilo visual "activo" en el menú
            document.querySelectorAll('.theme-option').forEach(el => el.classList.remove('active'));
            document.querySelector(`.theme-option[data-theme-val="${theme}"]`).classList.add('active');
        }

        // Cargar tema al iniciar la página
        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('aph_skill_theme');
            if (savedTheme) {
                setTheme(savedTheme);
            }
        });

        // Cerrar menú de capas si se hace clic afuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.theme-menu-container')) {
                const dropdown = document.getElementById('themeDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });

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

        // Alejar ligeramente al iniciar para apreciar el mapa
        window.onload = function() {
            scale = 0.85; 
            setTransform();
        };

        viewport.onmousedown = function (e) {
            e.preventDefault();
            // Evitar que el drag ocurra si se clickea un botón o el menú
            if(e.target.closest('.zoom-controls')) return; 
            
            start = { x: e.clientX - pointX, y: e.clientY - pointY };
            panning = true;
        }
        viewport.onmouseup = function () { panning = false; }
        viewport.onmouseleave = function () { panning = false; }
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
        function resetView() { scale = 0.85; pointX = 0; pointY = 0; setTransform(); }

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