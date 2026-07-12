<?php
session_start();
require 'db.php'; // Asegúrate de que aquí instancias tu conexión a la BD como $pdo

// Redirección de seguridad (Descomentar en producción)
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
*/
$user_id = $_SESSION['user_id'] ?? 1; // 1 para pruebas

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

// Procesar todos los nodos
foreach ($catalog as $item) {
    $key = $item['node_key'];
    $level = isset($user_skills[$key]) ? floor($user_skills[$key]['current_level']) : 0;
    $unlocked = isset($user_skills[$key]) ? $user_skills[$key]['unlocked'] : false;

    // Calcular Estado
    $status = 'locked';
    if ($key === 'origen' || $level >= $item['max_level']) {
        $status = 'maxed';
    } elseif ($level > 0 || $unlocked) {
        $status = 'unlocked';
    }

    $nodes[$key] = [
        'id' => $key,
        'label' => $item['label'],
        'x' => $item['x'],
        'y' => $item['y'],
        'level' => $level,
        'max' => $item['max_level'],
        'status' => $status,
        'parent' => $item['parent_key'],
        'route' => $item['route']
    ];
}

// --- 4. CALCULAR VISIBILIDAD (Para el botón del Ojo) ---
// Un nodo es "deep-locked" si él está bloqueado y su PADRE también está bloqueado.
foreach ($nodes as $key => &$node) {
    if ($key === 'origen' || $node['status'] !== 'locked') {
        $node['visibility_class'] = 'default-visible';
    } else {
        $parentKey = $node['parent'];
        $parentStatus = isset($nodes[$parentKey]) ? $nodes[$parentKey]['status'] : 'locked';
        
        // Si el padre está desbloqueado o maximizado, este nodo es el "Siguiente Nivel 1"
        if ($parentStatus !== 'locked') {
            $node['visibility_class'] = 'default-visible';
        } else {
            // Está profundo en el árbol
            $node['visibility_class'] = 'deep-locked';
        }
    }
}
unset($node); // Romper referencia

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
    <title>Árbol de Habilidades | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar y Layout ... (Igual que tu versión) */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
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
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem; }
        .btn-return:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

        .tree-viewport { flex: 1; position: relative; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.02); cursor: grab; }
        .tree-viewport:active { cursor: grabbing; }
        .tree-viewport::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: radial-gradient(var(--border-color) 1px, transparent 1px); background-size: 30px 30px; opacity: 0.5; z-index: 0; pointer-events: none; }
        
        .tree-canvas { position: absolute; top: 50%; left: 50%; transform-origin: 0 0; z-index: 1; transition: opacity 0.3s; }
        
        .zoom-controls { position: absolute; bottom: 20px; right: 20px; display: flex; gap: 10px; z-index: 20; }
        .zoom-btn { background: white; border: 1px solid var(--border-color); width: 40px; height: 40px; border-radius: 8px; font-size: 1.2rem; font-weight: bold; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.2s; }
        .zoom-btn:hover { color: var(--accent); border-color: var(--accent); }
        
        /* ESTILOS DEL MODO OCULTO (EYE TOGGLE) */
        .tree-canvas.hide-deep .deep-locked {
            display: none !important;
            opacity: 0;
            transition: 0.3s;
        }

        svg { position: absolute; top: 0; left: 0; width: 0; height: 0; overflow: visible; pointer-events: none; z-index: 1; }
        
        .node { position: absolute; width: 110px; height: 90px; background: var(--bg-panel); border: 2px solid var(--border-color); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; z-index: 5; text-decoration: none; color: var(--text-main); transform: translate(-50%, -50%); }
        .node.core { width: 140px; height: 140px; background: var(--accent); border-radius: 50%; color: white; border: 6px solid white; box-shadow: 0 0 40px rgba(128, 90, 213, 0.4); cursor: default; }
        .node.core span { font-size: 0.7rem; font-family: 'Inter', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .node.core strong { font-size: 1.5rem; font-family: 'Orbitron', sans-serif; }
        
        .node.locked { filter: grayscale(100%); opacity: 0.6; pointer-events: none; }
        .node.unlocked { border-color: var(--gold); box-shadow: 0 5px 15px rgba(236, 201, 75, 0.2); }
        .node.maxed { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 5px 15px rgba(128, 90, 213, 0.2); }
        .node:not(.core):not(.locked):hover { transform: translate(-50%, -50%) scale(1.1); z-index: 20; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .node-level { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--text-muted); }
        .node.unlocked .node-level { color: #D69E2E; }
        .node.maxed .node-level { color: var(--accent); }
        .node-label { font-size: 0.75rem; text-transform: uppercase; margin-top: 5px; text-align: center; font-weight: 700; letter-spacing: 0.5px; }
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

        <div class="tree-viewport" id="viewport">
            <div class="zoom-controls">
                <button class="zoom-btn" id="btn-eye" onclick="toggleVisibility()" title="Mostrar todo el árbol">🔒</button>
                <button class="zoom-btn" onclick="zoomIn()">+</button>
                <button class="zoom-btn" onclick="zoomOut()">-</button>
                <button class="zoom-btn" onclick="resetView()">⌂</button>
            </div>

            <div class="tree-canvas hide-deep" id="canvas">
                <svg>
                    <?php 
                    // Dibujar las conexiones dinámicamente
                    foreach($nodes as $id => $node) {
                        if ($node['parent'] && isset($nodes[$node['parent']])) {
                            $parent = $nodes[$node['parent']];
                            $color = ($node['status'] == 'locked') ? '#E2E8F0' : (($node['status'] == 'maxed') ? '#805AD5' : '#ecc94b');
                            $dash = ($node['status'] == 'locked') ? '5,5' : '0';
                            $width = ($node['status'] == 'maxed') ? '3' : '2';
                            
                            // Se aplica la clase de visibilidad también a la línea
                            echo "<line class='{$node['visibility_class']}' x1='{$parent['x']}' y1='{$parent['y']}' x2='{$node['x']}' y2='{$node['y']}' stroke='{$color}' stroke-width='{$width}' stroke-dasharray='{$dash}'></line>";
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
        // Lógica de Pan y Zoom
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
            scale = Math.max(0.3, Math.min(scale, 2.5)); 
            
            pointX = e.clientX - xs * scale;
            pointY = e.clientY - ys * scale;
            setTransform();
        }

        function zoomIn() { scale = Math.min(scale * 1.2, 2.5); setTransform(); }
        function zoomOut() { scale = Math.max(scale / 1.2, 0.3); setTransform(); }
        function resetView() { scale = 1; pointX = 0; pointY = 0; setTransform(); }

        // --- LÓGICA DEL BOTÓN DEL OJO ---
        function toggleVisibility() {
            const btn = document.getElementById('btn-eye');
            canvas.classList.toggle('hide-deep');
            
            if(canvas.classList.contains('hide-deep')) {
                btn.innerHTML = '🔒'; // Modo Oculto (Solo muestra nivel actual + 1)
                btn.title = 'Mostrar todo el árbol';
            } else {
                btn.innerHTML = '👁️'; // Modo Visible (Muestra todo)
                btn.title = 'Ocultar ramas lejanas';
            }
        }
    </script>
</body>
</html>