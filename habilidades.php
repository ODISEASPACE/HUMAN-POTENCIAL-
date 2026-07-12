<?php
session_start();
require 'db.php';

// Redirección de seguridad (descomentar en producción)
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$user_id = $_SESSION['user_id'];
*/

// --- SIMULACIÓN DE DATOS PARA LA INTERFAZ ---
$user = [
    'username' => 'Daniel',
    'profession' => 'Ingeniería de Sistemas',
    'profile_picture' => '' // Asume avatar genérico si está vacío
];

$skills = [
    'Estudio' => 10,
    'Laboral' => 4,
    'Finanzas' => 2,
    'Salud' => 0,
    'Espíritu' => 0
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
        
        /* Sidebar (Mismo diseño de la plataforma) */
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

        /* Contenedor Principal */
        main { flex: 1; display: flex; flex-direction: column; overflow: hidden; padding: 40px; }
        
        .header-dash { margin-bottom: 30px; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; z-index: 10; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        /* Botón de Retorno */
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem; }
        .btn-return:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

        /* Espacio del Árbol (Canvas) */
        .tree-workspace { flex: 1; position: relative; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        
        /* SVG de conexiones */
        svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; }
        
        /* Núcleo Central */
        .core { width: 130px; height: 130px; background: var(--accent); border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10; box-shadow: 0 0 40px rgba(128, 90, 213, 0.4); border: 6px solid white; color: white; text-align: center; font-family: 'Orbitron', sans-serif; }
        .core span { font-size: 0.7rem; font-family: 'Inter', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .core strong { font-size: 1.4rem; font-weight: 700; }

        /* Nodos de Habilidad */
        .node { position: absolute; width: 100px; height: 90px; background: var(--bg-panel); border: 2px solid var(--border-color); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 5; box-shadow: 0 4px 10px rgba(0,0,0,0.03); text-decoration: none; color: var(--text-main); }
        
        .node.locked { filter: grayscale(100%); opacity: 0.7; }
        .node.unlocked { border-color: var(--gold); box-shadow: 0 5px 15px rgba(236, 201, 75, 0.2); }
        .node.maxed { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 5px 15px rgba(128, 90, 213, 0.2); }
        
        .node:hover { transform: scale(1.1) !important; z-index: 20; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .node-level { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--text-muted); }
        .node.unlocked .node-level { color: #D69E2E; }
        .node.maxed .node-level { color: var(--accent); }
        
        .node-label { font-size: 0.7rem; text-transform: uppercase; margin-top: 5px; text-align: center; font-weight: 700; letter-spacing: 0.5px; }
        
        /* Fondo con patrón sutil para simular el espacio de red */
        .tree-workspace::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: radial-gradient(var(--border-color) 1px, transparent 1px); background-size: 30px 30px; opacity: 0.5; z-index: 0; pointer-events: none; }
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

        <div class="tree-workspace" id="tree-container">
            <svg id="skill-lines"></svg>

            <div class="core" id="core-node">
                <span>Origen</span>
                <strong>APH</strong>
            </div>

            <?php 
            $branches = ['Estudio', 'Laboral', 'Finanzas', 'Salud', 'Espíritu'];
            $angleStep = 360 / count($branches);
            $dist = 280; // Distancia desde el centro (ajustada para el nuevo contenedor)
            
            foreach($branches as $i => $name): 
                $angle = deg2rad($i * $angleStep - 90); // -90 para empezar desde arriba
                $x = cos($angle) * $dist;
                $y = sin($angle) * $dist;
                
                $level = $skills[$name] ?? 0;
                $status = ($level >= 10) ? 'maxed' : (($level > 0) ? 'unlocked' : 'locked');
            ?>
                <a href="skill_tree/<?= strtolower($name) ?>.php" class="node <?= $status ?> skill-node" data-x="<?= $x ?>" data-y="<?= $y ?>">
                    <div class="node-level"><?= $level ?>/10</div>
                    <div class="node-label"><?= $name ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function renderTree() {
            const container = document.getElementById('tree-container');
            const svg = document.getElementById('skill-lines');
            const nodes = document.querySelectorAll('.skill-node');
            
            // Limpiar SVG
            svg.innerHTML = '';
            
            // Posicionamiento dinámico desde el centro real del contenedor
            const centerX = container.clientWidth / 2;
            const centerY = container.clientHeight / 2;

            nodes.forEach(node => {
                // Obtener coordenadas de PHP pasadas por data-attributes
                const offsetX = parseFloat(node.getAttribute('data-x'));
                const offsetY = parseFloat(node.getAttribute('data-y'));
                
                // Aplicar posicionamiento CSS relativo al centro del div
                node.style.left = `calc(50% + ${offsetX}px - 50px)`; // 50px es la mitad del ancho del nodo
                node.style.top = `calc(50% + ${offsetY}px - 45px)`;  // 45px es la mitad del alto del nodo
                
                // Configurar propiedades de la línea
                let strokeColor = "#E2E8F0"; // Default Locked
                let strokeWidth = "2";
                let strokeDash = "6,6";
                
                if(node.classList.contains('maxed')) {
                    strokeColor = "#805AD5";
                    strokeWidth = "3";
                    strokeDash = "0";
                } else if(node.classList.contains('unlocked')) {
                    strokeColor = "#ecc94b";
                    strokeWidth = "2";
                    strokeDash = "0";
                }

                // Dibujar Línea SVG
                const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
                line.setAttribute("x1", centerX);
                line.setAttribute("y1", centerY);
                line.setAttribute("x2", centerX + offsetX);
                line.setAttribute("y2", centerY + offsetY);
                line.setAttribute("stroke", strokeColor);
                line.setAttribute("stroke-width", strokeWidth);
                line.setAttribute("stroke-dasharray", strokeDash);
                svg.appendChild(line);
            });
        }

        // Renderizar al cargar y recalcular al redimensionar la ventana
        window.addEventListener('load', renderTree);
        window.addEventListener('resize', renderTree);
    </script>
</body>
</html>