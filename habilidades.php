<?php
session_start();
require 'db.php';
// Simulación de niveles del usuario en cada rama
$skills = [
    'Estudio' => 10,
    'Laboral' => 4,
    'Finanzas' => 2,
    'Salud' => 0,
    'Espíritu' => 0
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Skill Tree | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: radial-gradient(circle, #1a202c 0%, #050505 100%); color: white; margin: 0; overflow: hidden; font-family: 'Inter', sans-serif; }
        .skill-container { width: 100vw; height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; }
        
        /* Central Core */
        .core { width: 120px; height: 120px; background: #805AD5; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 10; box-shadow: 0 0 50px #805AD5; font-family: 'Orbitron'; font-size: 0.8rem; border: 4px solid white; }
        
        /* SVG de conexiones */
        svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
        
        /* Nodos de Habilidad */
        .node { position: absolute; width: 80px; height: 80px; background: #2d3748; border: 2px solid #4a5568; border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: 0.4s; z-index: 5; }
        .node.unlocked { border-color: #ecc94b; box-shadow: 0 0 20px rgba(236, 201, 75, 0.3); }
        .node.maxed { border-color: #805ad5; background: #2b1d42; box-shadow: 0 0 20px #805ad5; }
        .node:hover { transform: scale(1.1); z-index: 20; }
        
        .node-label { font-size: 0.6rem; text-transform: uppercase; margin-top: 5px; text-align: center; font-weight: bold; }
        .node-level { font-family: 'Orbitron'; font-size: 0.9rem; color: #ecc94b; }

        /* Ramas */
        .branch-title { position: absolute; font-family: 'Orbitron'; color: rgba(255,255,255,0.2); font-size: 3rem; pointer-events: none; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="skill-container">
        <svg id="skill-lines">
            </svg>

        <div class="core">ORIGEN<br>APH</div>

        <?php 
        $branches = ['Estudio', 'Laboral', 'Finanzas', 'Salud', 'Espíritu'];
        $angleStep = 360 / count($branches);
        
        foreach($branches as $i => $name): 
            $angle = deg2rad($i * $angleStep);
            $dist = 250; // Distancia del centro
            $x = cos($angle) * $dist;
            $y = sin($angle) * $dist;
            $level = $skills[$name];
            $status = ($level >= 10) ? 'maxed' : (($level > 0) ? 'unlocked' : 'locked');
        ?>
            <div class="node <?= $status ?>" 
                 style="transform: translate(<?= $x ?>px, <?= $y ?>px);"
                 onclick="window.location.href='skill_tree/<?= strtolower($name) ?>.php'">
                <div class="node-level"><?= $level ?>/10</div>
                <div class="node-label"><?= $name ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Dibujar líneas desde el centro a los nodos
        const svg = document.getElementById('skill-lines');
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;

        document.querySelectorAll('.node').forEach(node => {
            const rect = node.getBoundingClientRect();
            const nodeX = rect.left + rect.width / 2;
            const nodeY = rect.top + rect.height / 2;

            const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
            line.setAttribute("x1", centerX);
            line.setAttribute("y1", centerY);
            line.setAttribute("x2", nodeX);
            line.setAttribute("y2", nodeY);
            line.setAttribute("stroke", node.classList.contains('maxed') ? "#805ad5" : "#4a5568");
            line.setAttribute("stroke-width", "2");
            line.setAttribute("stroke-dasharray", "5,5");
            svg.appendChild(line);
        });
    </script>
</body>
</html>