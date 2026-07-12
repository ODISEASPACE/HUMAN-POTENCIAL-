<?php
$current_skill = $_GET['skill'] ?? 'desarrollo';
// Aquí harías tu consulta a la BD: SELECT * FROM specialization_nodes WHERE parent_skill_key = $current_skill
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aumentar | <?= ucfirst($current_skill) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FAFAFC; padding: 40px; color: #1A202C; }
        .container { max-width: 800px; margin: 0 auto; background: #FFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { padding: 10px 15px; border: 1px solid #E2E8F0; border-radius: 8px; text-decoration: none; color: #718096; font-weight: 600; }
        .node-list { display: flex; flex-direction: column; gap: 15px; }
        .node-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid #E2E8F0; border-radius: 8px; }
        .node-info h3 { margin: 0 0 5px 0; }
        .node-info p { margin: 0; color: #718096; font-size: 0.9rem; }
        .btn-invest { background: #805AD5; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1.1rem; }
        .btn-invest:hover { background: #6B46C1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invertir Puntos: <?= ucfirst($current_skill) ?></h1>
            <a href="rama_template.php?skill=<?= $current_skill ?>" class="btn-back">Volver al Mando</a>
        </div>
        
        <div class="node-list">
            <div class="node-item">
                <div class="node-info">
                    <h3>Frontend (React/Vue)</h3>
                    <p>Nivel actual: 3/10 | Peso: 40%</p>
                </div>
                <button class="btn-invest">+ Invertir</button>
            </div>
            
            <div class="node-item">
                <div class="node-info">
                    <h3>Backend (Node/Python)</h3>
                    <p>Nivel actual: 5/10 | Peso: 40%</p>
                </div>
                <button class="btn-invest">+ Invertir</button>
            </div>
        </div>
    </div>
</body>
</html>