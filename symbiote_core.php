<?php
session_start();
require 'db.php';

// DOBLE CERROJO DE SEGURIDAD: Solo tú entras aquí.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote']) || $_SESSION['is_symbiote'] !== true) {
    // Si alguien intenta entrar por URL, lo expulsamos al inicio
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. EXTRACCIÓN MASIVA: Eventos del Mes (Visión Panorámica)
// Extraemos TODO el mes actual sin paginación para no generar fricción visual
$stmtEvents = $pdo->prepare("
    SELECT title, start_time, end_time, event_type, is_completed, color 
    FROM calendar_events 
    WHERE user_id = ? AND start_time >= date_trunc('month', CURRENT_DATE)
    ORDER BY start_time ASC
");
$stmtEvents->execute([$user_id]);
$events = $stmtEvents->fetchAll();

// 2. ESTADO HUMANO: Preparación para Gemini
// Traemos el último estado para que Gemini lo evalúe
$stmtState = $pdo->prepare("SELECT * FROM human_state WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1");
$stmtState->execute([$user_id]);
$h_state = $stmtState->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APH | Symbiote Core</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Diseño oscuro, denso y enfocado en datos. Sin distracciones. */
        :root { --bg: #09090b; --panel: #18181b; --text: #e4e4e7; --accent: #a855f7; --border: #27272a; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; display: grid; grid-template-columns: 350px 1fr; gap: 20px; height: 100vh; box-sizing: border-box; overflow: hidden; }
        
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 20px; overflow-y: auto; }
        h2 { font-family: 'JetBrains Mono', monospace; font-size: 1rem; color: var(--accent); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Consola de Estado Humano (Para Gemini) */
        .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .stat-box { background: rgba(255,255,255,0.03); padding: 10px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; border: 1px solid rgba(255,255,255,0.05); }
        .stat-box span { display: block; color: var(--text); font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        
        .ai-prompt-area { margin-top: 20px; }
        textarea { width: 100%; background: #000; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; resize: none; height: 100px; }
        .btn-sync { width: 100%; background: var(--accent); color: #fff; border: none; padding: 10px; margin-top: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: 'JetBrains Mono'; }
        .btn-sync:hover { opacity: 0.8; }

        /* Malla de Calendario Panorámica */
        .timeline { display: flex; flex-direction: column; gap: 8px; }
        .event-row { display: grid; grid-template-columns: 120px 1fr auto; gap: 15px; padding: 12px; background: rgba(255,255,255,0.02); border-left: 3px solid var(--accent); border-radius: 4px; font-size: 0.9rem; align-items: center;}
        .event-time { font-family: 'JetBrains Mono', monospace; color: #a1a1aa; font-size: 0.8rem; }
        .event-title { font-weight: bold; }
        .event-badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 12px; background: rgba(168, 85, 247, 0.2); color: #d8b4fe; }
    </style>
</head>
<body>

    <!-- PANEL LATERAL: Estado y Sincronización con IA -->
    <div class="panel">
        <h2>>> Estado_Humano_Sync</h2>
        
        <div class="status-grid">
            <div class="stat-box">Psique <span><?= $h_state['psique_score'] ?? 0 ?>/100</span></div>
            <div class="stat-box">Soma <span><?= $h_state['soma_score'] ?? 0 ?>/100</span></div>
            <div class="stat-box">Pneuma <span><?= $h_state['pneuma_score'] ?? 0 ?>/100</span></div>
            <div class="stat-box">Pathos <span><?= $h_state['pathos_score'] ?? 0 ?>/100</span></div>
        </div>

        <div class="ai-prompt-area">
            <p style="font-size: 0.8rem; color: #a1a1aa; margin-bottom: 5px;">Módulo de Ingesta (Gemini Pro):</p>
            <textarea id="aiInput" placeholder="Introduce el contexto del día o la variable a analizar..."></textarea>
            <button class="btn-sync" onclick="triggerGeminiSync()">SINCRONIZAR IA</button>
        </div>
        
        <div id="aiResponse" style="margin-top: 15px; font-size: 0.8rem; color: #d8b4fe; font-family: 'JetBrains Mono';">
            [Esperando conexión con API Gemini...]
        </div>
    </div>

    <!-- PANEL PRINCIPAL: Visualización de Flujo -->
    <div class="panel">
        <h2>>> Flujo_Operativo_Mensual (Zero Friction)</h2>
        <div class="timeline">
            <?php if(count($events) > 0): ?>
                <?php foreach($events as $evt): 
                    $date = new DateTime($evt['start_time']);
                    $formatted_date = $date->format('d M - H:i');
                ?>
                <div class="event-row" style="border-left-color: <?= htmlspecialchars($evt['color'] ?? 'var(--accent)') ?>;">
                    <div class="event-time"><?= $formatted_date ?></div>
                    <div class="event-title"><?= htmlspecialchars($evt['title']) ?></div>
                    <div class="event-badge"><?= htmlspecialchars($evt['event_type']) ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #52525b; font-family: 'JetBrains Mono';">No hay bloques operativos definidos para este ciclo.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Aquí conectaremos Gemini vía Fetch API (PHP/cURL o JS)
        function triggerGeminiSync() {
            const btn = document.querySelector('.btn-sync');
            const resp = document.getElementById('aiResponse');
            btn.innerText = "PROCESANDO...";
            
            // Simulación de carga previa a la implementación real de la API
            setTimeout(() => {
                resp.innerHTML = "> Conexión inicializada.<br>> Preparado para ingestar logs de la BD.<br>> Esperando payload de Flash Lite.";
                btn.innerText = "SINCRONIZAR IA";
            }, 1500);
        }
    </script>
</body>
</html>