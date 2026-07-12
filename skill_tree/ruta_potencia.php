<?php $current_skill = $_GET['skill'] ?? 'desarrollo'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ruta de Potencia | <?= ucfirst($current_skill) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FAFAFC; padding: 40px; color: #1A202C; }
        .container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .panel { background: #FFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        textarea { width: 100%; height: 120px; padding: 15px; border: 1px solid #E2E8F0; border-radius: 8px; resize: none; margin-bottom: 15px; font-family: inherit; }
        .btn-save { background: #3182CE; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .goal-item { padding: 15px; border-bottom: 1px solid #E2E8F0; }
        .goal-item:last-child { border: none; }
    </style>
</head>
<body>
    <div style="max-width: 1000px; margin: 0 auto 20px;">
        <a href="rama_template.php?skill=<?= $current_skill ?>" style="text-decoration:none; color:#718096; font-weight:bold;">← Volver</a>
    </div>

    <div class="container">
        <div class="panel">
            <h2>Evolución de <?= ucfirst($current_skill) ?></h2>
            <canvas id="radarChart"></canvas>
        </div>

        <div class="panel">
            <h2>Registro Estratégico</h2>
            <form method="POST" action="guardar_meta.php">
                <textarea placeholder="¿Cuál es tu próximo objetivo en este núcleo? Escribe tu meta o descubrimiento..."></textarea>
                <button type="submit" class="btn-save">Registrar Meta</button>
            </form>

            <div style="margin-top: 30px;">
                <h3>Metas Activas</h3>
                <div class="goal-item">⬜ Terminar certificación de AWS Database (Creado: Hoy)</div>
                <div class="goal-item">⬜ Optimizar consultas del proyecto X (Creado: Ayer)</div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Frontend', 'Backend', 'Bases de Datos', 'DevOps', 'Arquitectura'],
                datasets: [{
                    label: 'Nivel Actual',
                    data: [3, 5, 2, 0, 1],
                    backgroundColor: 'rgba(128, 90, 213, 0.2)',
                    borderColor: 'rgba(128, 90, 213, 1)',
                    borderWidth: 2
                }]
            },
            options: { scales: { r: { min: 0, max: 10 } } }
        });
    </script>
</body>
</html>