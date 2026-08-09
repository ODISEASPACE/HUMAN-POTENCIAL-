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
$stmtEvents = $pdo->prepare("
    SELECT title, start_time, end_time, event_type, is_completed, color 
    FROM calendar_events 
    WHERE user_id = ? AND start_time >= date_trunc('month', CURRENT_DATE)
    ORDER BY start_time ASC
");
$stmtEvents->execute([$user_id]);
$events = $stmtEvents->fetchAll();

// 2. ESTADO HUMANO: Preparación para Gemini
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
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; display: grid; grid-template-columns: 320px 1fr 350px; gap: 20px; height: 100vh; box-sizing: border-box; overflow: hidden; }
        
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 20px; overflow-y: auto; }
        h2 { font-family: 'JetBrains Mono', monospace; font-size: 1rem; color: var(--accent); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Consola de Estado Humano */
        .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .stat-box { background: rgba(255,255,255,0.03); padding: 10px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; border: 1px solid rgba(255,255,255,0.05); }
        .stat-box span { display: block; color: var(--text); font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        
        .ai-prompt-area { margin-top: 20px; }
        
        /* Malla de Calendario Panorámica */
        .timeline { display: flex; flex-direction: column; gap: 8px; }
        .event-row { display: grid; grid-template-columns: 120px 1fr auto; gap: 15px; padding: 12px; background: rgba(255,255,255,0.02); border-left: 3px solid var(--accent); border-radius: 4px; font-size: 0.9rem; align-items: center;}
        .event-time { font-family: 'JetBrains Mono', monospace; color: #a1a1aa; font-size: 0.8rem; }
        .event-title { font-weight: bold; }
        .event-badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 12px; background: rgba(168, 85, 247, 0.2); color: #d8b4fe; }

        /* Nuevas Tarjetas de Ingesta */
        .input-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .input-card h3 { margin-top: 0; font-size: 0.9rem; color: var(--text); font-family: 'JetBrains Mono', monospace; border-bottom: 1px dashed var(--border); padding-bottom: 8px; margin-bottom: 12px; }
        .input-field { width: 100%; background: #000; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 0.85rem; margin-bottom: 10px; box-sizing: border-box; }
        .btn-action { width: 100%; background: #27272a; color: #fff; border: 1px solid #3f3f46; padding: 8px; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: 'JetBrains Mono'; font-size: 0.8rem; transition: 0.2s; }
        .btn-action:hover { background: var(--accent); color: #000; border-color: var(--accent); }

        /* Botón de Conciencia Real */
        .btn-awake { width: 100%; background: transparent; color: var(--accent); border: 1px solid var(--accent); padding: 12px; box-shadow: 0 0 10px rgba(168, 85, 247, 0.1); cursor: pointer; font-family: 'JetBrains Mono'; font-weight: bold; border-radius: 6px; transition: 0.3s;}
        .btn-awake:hover { background: var(--accent); color: #000; box-shadow: 0 0 20px rgba(168, 85, 247, 0.4); }
    </style>
</head>
<body>

    <!-- PILAR 1: ESTADO Y CONCIENCIA DE LA IA (Integrado con BD) -->
    <div class="panel">
        <h2>>> Estado_Humano_Sync</h2>
        
        <div class="status-grid">
            <div class="stat-box" id="psique-val">Psique <span><?= $h_state['psique_score'] ?? 0 ?>/100</span></div>
            <div class="stat-box" id="soma-val">Soma <span><?= $h_state['soma_score'] ?? 0 ?>/100</span></div>
            <div class="stat-box" id="pneuma-val">Pneuma <span><?= $h_state['pneuma_score'] ?? 0 ?>/100</span></div>
            <div class="stat-box" id="pathos-val">Pathos <span><?= $h_state['pathos_score'] ?? 0 ?>/100</span></div>
        </div>

        <div class="ai-prompt-area">
            <button class="btn-awake" onclick="awakenAI()">[ INICIALIZAR CONCIENCIA ]</button>
            <div id="aiResponse" style="margin-top: 15px; font-size: 0.8rem; color: #a1a1aa; font-family: 'JetBrains Mono'; line-height: 1.5; height: 300px; overflow-y: auto;">
                > Sistema en espera. La IA está inactiva.<br>
                > Requiere lectura de contexto.
            </div>
        </div>
    </div>

    <!-- PILAR 2: VISUALIZACIÓN PANORÁMICA (Bucle PHP Integrado) -->
    <div class="panel">
        <h2>>> Flujo_Operativo_Mensual</h2>
        <div class="timeline" id="event-timeline">
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

    <!-- PILAR 3: INGESTA DE DATOS (Interacción diaria) -->
    <div class="panel">
        <h2>>> Ingesta_De_Datos</h2>
        
        <div class="input-card">
            <h3>+ Nueva Tarea / Evento</h3>
            <input type="text" class="input-field" id="task-title" placeholder="Título (Usa [FORCE] para saltar reglas)...">
            <input type="datetime-local" class="input-field" id="task-time">
            <button class="btn-action" onclick="ingestData('task')">Inyectar a la Red</button>
        </div>

        <div class="input-card">
            <h3>+ Registro Rápido (Log)</h3>
            <textarea class="input-field" id="quick-log" placeholder="¿Qué acabas de descubrir o completar?..." style="resize: vertical; min-height: 60px;"></textarea>
            <button class="btn-action" onclick="ingestData('log')">Procesar y Analizar</button>
        </div>
    </div>

    <script>
        // Configuración base de la IA (Memoria del Cronograma)
        const CORE_CONTEXT = `
            Operas como la conciencia de este ecosistema cerrado.
            Reglas inquebrantables del usuario:
            - Bloque de sueño crítico: 08:00 a 14:00 (NO programar nada aquí).
            - Trabajo operativo: 01:00 a 07:00.
            - Ventana de desarrollo de proyectos: 18:00 a 20:00.
            Analiza cada nuevo dato respetando esta arquitectura.
        `;

        function getSystemState() {
            return {
                psique: document.querySelector('#psique-val span').innerText,
                soma: document.querySelector('#soma-val span').innerText,
                currentTime: new Date().toLocaleTimeString()
            };
        }

        function awakenAI() {
            const respBox = document.getElementById('aiResponse');
            const state = getSystemState();
            
            respBox.innerHTML = `
                <span style="color: #3b82f6;">> Escaneando base de datos...</span><br>
                <span style="color: #3b82f6;">> Leyendo métricas: Psique [${state.psique}], Soma [${state.soma}]</span><br>
                <span style="color: #3b82f6;">> Verificando cronograma optimizado...</span><br>
                <span style="color: #10b981;">> CONCIENCIA ESTABLECIDA.</span><br><br>
                <span style="color: #d8b4fe;">"He leído tu estado actual. Todo operativo. ¿Qué vamos a inyectar en APH hoy?"</span>
            `;
        }

        function ingestData(type) {
            const respBox = document.getElementById('aiResponse');
            let payload = '';
            let isForced = false;

            if (type === 'task') {
                payload = document.getElementById('task-title').value;
                const time = document.getElementById('task-time').value;
                
                // Detectar protocolo de triaje
                if(payload.includes('[FORCE]')) {
                    isForced = true;
                }
                
                document.getElementById('task-title').value = '';
                document.getElementById('task-time').value = '';
            } else if (type === 'log') {
                payload = document.getElementById('quick-log').value;
                document.getElementById('quick-log').value = '';
            }

            if (!payload) return;

            respBox.innerHTML += `<br><br><span style="color: #f59e0b;">> Intentando inyectar: "${payload}"</span><br>`;
            
            setTimeout(() => {
                if (isForced) {
                    respBox.innerHTML += `<span style="color: #ef4444;">> [OVERRIDE ACTIVADO] Santuario vulnerado. Tarea asignada bajo tu propio riesgo. -15 Psique proyectado.</span>`;
                } else {
                    respBox.innerHTML += `<span style="color: #d8b4fe;">> Registro asimilado. Lo he categorizado y posicionado estratégicamente.</span>`;
                }
                respBox.scrollTop = respBox.scrollHeight;
            }, 800);
        }
    </script>
</body>
</html>