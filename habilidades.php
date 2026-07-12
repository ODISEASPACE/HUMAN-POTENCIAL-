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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
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

        /* =========================================
           SISTEMA DE TEMAS AISLADOS DEL MAPA 
           ========================================= */

        /* 1. Tema por Defecto (Light OS) */
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

        /* 2. Tema Blueprint (Técnico / Ingeniería) */
        .tree-viewport[data-theme="blueprint"] {
            --map-bg: #0B192C;
            --map-grid: rgba(255, 255, 255, 0.1);
            --node-bg: #0B192C;
            --node-border: #3A86FF;
            --node-text: #F8F9FA;
            --node-text-muted: #A0C4FF;
            --locked-line: rgba(255, 255, 255, 0.15);
            --node-radius: 0px; 
        }
        .tree-viewport[data-theme="blueprint"] .node:not(.core) {
            border: 2px dashed var(--node-border);
            font-family: 'JetBrains Mono', monospace;
            background-image: repeating-linear-gradient(45deg, rgba(58, 134, 255, 0.05) 25%, transparent 25%, transparent 75%, rgba(58, 134, 255, 0.05) 75%, rgba(58, 134, 255, 0.05)), repeating-linear-gradient(45deg, rgba(58, 134, 255, 0.05) 25%, transparent 25%, transparent 75%, rgba(58, 134, 255, 0.05) 75%, rgba(58, 134, 255, 0.05));
            background-position: 0 0, 10px 10px; background-size: 20px 20px;
        }
        .tree-viewport[data-theme="blueprint"] .node-level { font-family: 'JetBrains Mono', monospace; font-size: 1rem; }
        .tree-viewport[data-theme="blueprint"] .node-label { font-family: 'JetBrains Mono', monospace; font-weight: 400; }
        .tree-viewport[data-theme="blueprint"] .node.unlocked { border-style: solid; background-color: rgba(58, 134, 255, 0.1); }
        .tree-viewport[data-theme="blueprint"] .node.maxed { border-style: solid; background-color: rgba(58, 134, 255, 0.3); border-width: 3px; }
        .tree-viewport[data-theme="blueprint"] .svg-layer path { stroke-linecap: square; }
        .tree-viewport[data-theme="blueprint"]::before { background-size: 50px 50px; }

        /* 3. Tema Executive (Corporativo / Management) */
        .tree-viewport[data-theme="executive"] {
            --map-bg: #F1F5F9;
            --map-grid: transparent;
            --node-bg: #FFFFFF;
            --node-border: #CBD5E1;
            --node-text: #334155;
            --node-text-muted: #94A3B8;
            --locked-line: #CBD5E1;
            --node-radius: 6px;
            --map-shadow: inset 0 0 50px rgba(0,0,0,0.03);
        }
        .tree-viewport[data-theme="executive"] .node:not(.core) {
            width: 150px; height: 60px;
            flex-direction: row; justify-content: flex-start;
            padding: 0 12px; gap: 10px;
            border-left-width: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .tree-viewport[data-theme="executive"] .node.unlocked { border-left-color: var(--gold); }
        .tree-viewport[data-theme="executive"] .node.maxed { border-left-color: var(--accent); }
        .tree-viewport[data-theme="executive"] .node-level { font-size: 1rem; margin: 0; }
        .tree-viewport[data-theme="executive"] .node-label { font-size: 0.65rem; text-align: left; margin: 0; }

        /* 4. Tema Void (Minimalista) */
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

        /* 5. Tema Nexus (Creativo / Sci-Fi Brutalista) */
        .tree-viewport[data-theme="nexus"] {
            --map-bg: #121212;
            --map-grid: rgba(255, 255, 255, 0.03);
            --node-bg: #1E1E24;
            --node-border: #444444;
            --node-text: #F5F5F5;
            --node-text-muted: #888888;
            --locked-line: #333333;
            --node-radius: 0px;
        }
        .tree-viewport[data-theme="nexus"] .node:not(.core) {
            border: 1px solid var(--node-border);
            box-shadow: 5px 5px 0px rgba(0,0,0,0.8);
            background: linear-gradient(135deg, #1E1E24 0%, #111115 100%);
        }
        .tree-viewport[data-theme="nexus"] .node.unlocked { box-shadow: 5px 5px 0px var(--gold); border-color: var(--gold); }
        .tree-viewport[data-theme="nexus"] .node.maxed { box-shadow: 5px 5px 0px var(--accent); border-color: var(--accent); }
        .tree-viewport[data-theme="nexus"] .node.core { border-radius: 50%; box-shadow: 0 0 0 6px rgba(128, 90, 213, 0.3), 8px 8px 0px rgba(128, 90, 213, 0.8); }


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
            background: var(--map-bg); 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: var(--map-shadow); 
            cursor: grab; 
            transition: background-color 0.4s, box-shadow 0.4s;
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
            min-width: 170px;
            z-index: 100;
        }
        .theme-dropdown.show { display: flex; }
        .theme-option {
            padding: 12px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            transition: 0.2s;
            display: flex; align-items: center; justify-content: space-between;
        }
        .theme-option:hover, .theme-option.active { background: var(--accent-light); color: var(--accent); }

        /* Ocultar nodos lejanos */
        .tree-canvas.hide-deep .deep-locked { display: none !important; opacity: 0; transition: 0.3s; }

        /* === SOLUCIÓN DEFINITIVA A LAS LÍNEAS SVG === */
        .svg-layer { 
            position: absolute; top: -2500px; left: -2500px; width: 5000px; height: 5000px; 
            pointer-events: none; z-index: 1; filter: var(--line-filter); 
        }
        .tree-link { transition: stroke 0.4s; }
        
        /* === ESTILOS ESTRUCTURALES BASE DE LOS NODOS === */
        .node { 
            position: absolute; width: 120px; height: 100px; 
            background: var(--node-bg); 
            border: 2px solid var(--node-border); 
            border-radius: var(--node-radius); 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            cursor: pointer; z-index: 5; text-decoration: none; color: var(--node-text); 
            transform: translate(-50%, -50%); transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        .node.core { width: 150px; height: 150px; background: var(--accent); border-radius: 50%; color: white; border: 6px solid var(--node-bg); box-shadow: 0 0 30px var(--accent-light); cursor: default; }
        .node.core span { font-size: 0.75rem; font-family: 'Inter', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .node.core strong { font-size: 1.7rem; font-family: 'Orbitron', sans-serif; }
        
        .node.locked { filter: grayscale(100%) opacity(0.5); border-color: var(--locked-line); cursor: pointer;}
        .node.unlocked { border-color: var(--gold); box-shadow: 0 5px 15px rgba(236, 201, 75, 0.15); }
        .node.maxed { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 5px 15px var(--accent-light); }
        .node:not(.core):not(.locked):hover { transform: translate(-50%, -50%) scale(1.1); z-index: 20; }
        
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
                        <div class="theme-option" data-theme-val="blueprint" onclick="setTheme('blueprint')">Blueprint</div>
                        <div class="theme-option" data-theme-val="executive" onclick="setTheme('executive')">Executive</div>
                        <div class="theme-option" data-theme-val="nexus" onclick="setTheme('nexus')">Nexus</div>
                        <div class="theme-option" data-theme-val="void" onclick="setTheme('void')">Void</div>
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
                            
                            echo "<path class='tree-link {$node['visibility_class']}' d='M {$parent['x']} {$parent['y']} L {$node['x']} {$node['y']}' stroke='{$statusColor}' stroke-width='{$width}' stroke-dasharray='{$dash}' fill='none' stroke-linejoin='round'></path>";
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
                        <a href="skill_tree/rama.php?skill=<?= $id ?>" class="node <?= $node['status'] ?> <?= $node['visibility_class'] ?>" style="left: <?= $node['x'] ?>px; top: <?= $node['y'] ?>px;">
                            <div class="node-level"><?= $node['level'] ?>/<?= $node['max'] ?></div>
                            <div class="node-label"><?= $node['label'] ?></div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleThemeMenu() {
            document.getElementById('themeDropdown').classList.toggle('show');
        }

        function setTheme(theme) {
            document.getElementById('viewport').setAttribute('data-theme', theme);
            localStorage.setItem('aph_skill_theme', theme);
            
            document.getElementById('themeDropdown').classList.remove('show');
            document.querySelectorAll('.theme-option').forEach(el => el.classList.remove('active'));
            document.querySelector(`.theme-option[data-theme-val="${theme}"]`).classList.add('active');
        }

        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('aph_skill_theme');
            if (savedTheme) {
                setTheme(savedTheme);
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.theme-menu-container')) {
                const dropdown = document.getElementById('themeDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });

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

        window.onload = function() {
            scale = 0.85; 
            setTransform();
        };

        viewport.onmousedown = function (e) {
            e.preventDefault();
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