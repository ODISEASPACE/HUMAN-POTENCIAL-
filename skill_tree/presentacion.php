<?php $current_skill = $_GET['skill'] ?? 'desarrollo'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presentación | <?= ucfirst($current_skill) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #1A202C; color: #FFF; padding: 40px; display: flex; justify-content: center; }
        .presentation-card { background: #2D3748; padding: 50px; border-radius: 20px; width: 100%; max-width: 700px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); text-align: center; }
        .title { font-family: 'Orbitron', sans-serif; font-size: 2.5rem; color: #F6AD55; margin-bottom: 10px; }
        .subtitle { color: #A0AEC0; margin-bottom: 40px; font-size: 1.2rem; }
        .stat-circle { width: 150px; height: 150px; border-radius: 50%; border: 8px solid #F6AD55; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; font-size: 3rem; font-family: 'Orbitron', sans-serif; }
        .progress-row { text-align: left; margin-bottom: 20px; }
        .bar-bg { width: 100%; background: #4A5568; height: 10px; border-radius: 5px; margin-top: 8px; }
        .bar-fill { background: #F6AD55; height: 100%; border-radius: 5px; }
        .btn-export { background: transparent; border: 2px solid #F6AD55; color: #F6AD55; padding: 10px 20px; border-radius: 8px; margin-top: 30px; cursor: pointer; text-decoration: none; display: inline-block;}
    </style>
</head>
<body>
    <div class="presentation-card">
        <h1 class="title"><?= strtoupper($current_skill) ?></h1>
        <p class="subtitle">Análisis de Dominio Actual</p>
        
        <div class="stat-circle">42%</div>

        <div class="progress-row">
            <label>Dominio Práctico (Frontend / Backend)</label>
            <div class="bar-bg"><div class="bar-fill" style="width: 60%;"></div></div>
        </div>
        
        <div class="progress-row">
            <label>Dominio Teórico (Arquitectura)</label>
            <div class="bar-bg"><div class="bar-fill" style="width: 25%;"></div></div>
        </div>

        <a href="rama_template.php?skill=<?= $current_skill ?>" class="btn-export">Finalizar Presentación</a>
    </div>
</body>
</html>