<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote']) || $_SESSION['is_symbiote'] !== true) {
    header("Location: index.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Extracción del Estado
$stmtState = $pdo->prepare("SELECT * FROM human_state WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1");
$stmtState->execute([$user_id]);
$h_state = $stmtState->fetch();

// Extracción de Proyectos (Para el Repositorio Central)
$stmtProjects = $pdo->prepare("SELECT * FROM projects_items WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmtProjects->execute([$user_id]);
$projects = $stmtProjects->fetchAll();

// Extracción de Eventos (Mes Completo - Sin paginación ni dropdowns)
$stmtEvents = $pdo->prepare("SELECT id, title, start_time, event_type FROM calendar_events WHERE user_id = ? AND start_time >= date_trunc('month', CURRENT_DATE) ORDER BY start_time ASC");
$stmtEvents->execute([$user_id]);
$events = $stmtEvents->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>APH | Symbiote Core V4</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <!-- Iconos para la UI -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --bg: #09090b; --panel: #18181b; --text: #e4e4e7; --accent: #a855f7; --border: #27272a; --danger: #ef4444; --success: #10b981; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; display: grid; grid-template-columns: 60px 1fr 350px; height: 100vh; overflow: hidden; }
        
        /* BARRA LATERAL (Navegación Simbionte) */
        .sidebar { background: #000; border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 25px; }
        .nav-btn { color: #52525b; font-size: 1.2rem; cursor: pointer; transition: 0.3s; background: none; border: none; }
        .nav-btn:hover, .nav-btn.active { color: var(--accent); }

        /* ÁREA CENTRAL MULTIVISTA */
        .main-workspace { padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        .view-section { display: none; animation: fadeIn 0.3s; }
        .view-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 20px; }
        h2 { font-family: 'JetBrains Mono'; font-size: 1rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-top: 0; }

        /* CALENDARIO PANORÁMICO (Mes entero, cero dropdowns) */
        .month-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-top: 15px; }
        .day-cell { background: #000; border: 1px solid var(--border); min-height: 100px; padding: 5px; border-radius: 4px; }
        .day-num { font-family: 'JetBrains Mono'; font-size: 0.7rem; color: #52525b; margin-bottom: 5px; display: block; }
        .evt-block { background: rgba(168, 85, 247, 0.15); border-left: 2px solid var(--accent); font-size: 0.7rem; padding: 3px 5px; margin-bottom: 3px; border-radius: 2px; display: flex; justify-content: space-between; cursor: pointer; }
        .evt-block:hover { background: rgba(168, 85, 247, 0.3); }

        /* REPOSITORIO CRUD */
        .project-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .project-card { background: #000; border: 1px solid var(--border); padding: 15px; border-radius: 6px; position: relative; }
        .project-card h4 { margin: 0 0 10px 0; color: #fff; }
        .crud-actions { position: absolute; top: 10px; right: 10px; display: flex; gap: 5px; opacity: 0.2; transition: 0.3s; }
        .project-card:hover .crud-actions { opacity: 1; }
        .btn-icon { background: none; border: none; cursor: pointer; color: #a1a1aa; font-size: 0.8rem; }
        .btn-icon.edit:hover { color: var(--success); }
        .btn-icon.delete:hover { color: var(--danger); }

        /* PANEL IA DERECHO */
        .ia-panel { border-left: 1px solid var(--border); background: var(--panel); padding: 20px; display: flex; flex-direction: column; }
        .ai-console { flex: 1; background: #000; border: 1px solid var(--border); border-radius: 6px; padding: 15px; font-family: 'JetBrains Mono'; font-size: 0.8rem; color: #a1a1aa; overflow-y: auto; margin-top: 15px; }
        .input-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px; }
        .input-field { width: 100%; background: #000; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 4px; font-family: 'Inter'; font-size: 0.85rem; margin-bottom: 10px; box-sizing: border-box; }
        .btn-action { width: 100%; background: var(--accent); color: #000; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-family: 'JetBrains Mono'; }
    </style>
</head>
<body>

    <!-- 1. BARRA DE NAVEGACIÓN -->
    <div class="sidebar">
        <button class="nav-btn active" onclick="switchView('view-calendar')" title="Visión Mensual"><i class="fa-regular fa-calendar-days"></i></button>
        <button class="nav-btn" onclick="switchView('view-repo')" title="Repositorio Central"><i class="fa-solid fa-database"></i></button>
        <button class="nav-btn" onclick="switchView('view-skills')" title="Matriz de Habilidades"><i class="fa-solid fa-network-wired"></i></button>
        <button class="nav-btn" onclick="switchView('view-state')" title="Estado Humano"><i class="fa-solid fa-heart-pulse"></i></button>
    </div>

    <!-- 2. ESPACIO DE TRABAJO PRINCIPAL -->
    <div class="main-workspace">
        
        <!-- VISTA: Calendario Integral -->
        <div id="view-calendar" class="view-section active">
            <div class="panel">
                <h2>>> Cronograma_Operativo (Mes Completo Expuesto)</h2>
                <div class="month-grid" id="calendar-grid">
                    <!-- El grid de 30/31 días se renderiza aquí por JS/PHP. Sin menús ocultos. -->
                    <div class="day-cell">
                        <span class="day-num">01</span>
                        <!-- Ejemplo de bloque inyectado -->
                        <div class="evt-block" onclick="editEvent(1)">
                            <span>Desarrollo Tame Q</span>
                            <span>18:00</span>
                        </div>
                    </div>
                    <!-- Generar los demás días... -->
                </div>
            </div>
        </div>

        <!-- VISTA: Repositorio y Proyectos (CRUD) -->
        <div id="view-repo" class="view-section">
            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                    <h2 style="border:none; padding:0; margin:0;">>> Repositorio_Central</h2>
                    <button class="btn-action" style="width: auto; padding: 5px 15px;" onclick="openCreateModal('project')">+ Nuevo Nodo</button>
                </div>
                
                <div class="project-grid">
                    <?php foreach($projects as $p): ?>
                    <div class="project-card" id="proj-<?= $p['id'] ?>">
                        <div class="crud-actions">
                            <button class="btn-icon edit" onclick="editProject(<?= $p['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-icon delete" onclick="deleteProject(<?= $p['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                        <h4><?= htmlspecialchars($p['title']) ?></h4>
                        <span style="font-size:0.75rem; color:#a1a1aa; display:block; margin-bottom:10px;"><?= htmlspecialchars($p['category']) ?></span>
                        <p style="font-size:0.8rem; color:#e4e4e7; margin:0;"><?= htmlspecialchars($p['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- VISTA: Matriz de Habilidades (Placeholder para tu JS de Nodos) -->
        <div id="view-skills" class="view-section">
            <div class="panel">
                <h2>>> Expansión_Cognitiva (Matriz 3D)</h2>
                <div id="skill-tree-container" style="height: 600px; background: #000; border: 1px dashed #3f3f46; display:flex; justify-content:center; align-items:center;">
                    <!-- Aquí se montará el canvas de tu árbol de nodos (Python, Bases de Datos, etc.) -->
                    <span style="color:#52525b; font-family:'JetBrains Mono';">Lienzo de Nodos Activo. Conectando API de renderizado...</span>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. PANEL DE INGESTA E IA (Siempre visible) -->
    <div class="ia-panel">
        <h2 style="font-size: 0.9rem;">>> Enlace_Gemini_Flash</h2>
        
        <div class="ai-console" id="aiResponse">
            > Sistema esperando comandos...
        </div>

        <div class="input-card">
            <h3 style="margin-top:0; font-size:0.8rem; font-family:'JetBrains Mono'; border-bottom:1px solid #27272a; padding-bottom:5px;">Ingesta Rápida</h3>
            <textarea class="input-field" id="quick-log" placeholder="Registra datos, ideas o comandos de sistema..." style="resize: vertical; min-height: 80px;"></textarea>
            <button class="btn-action" onclick="ingestData()">Procesar y Ejecutar</button>
        </div>
    </div>

    <script>
        // Navegación entre vistas
        function switchView(viewId) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(viewId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Lógica CRUD - Interceptada por la IA
        function deleteProject(id) {
            if(confirm('¿Purgar este nodo de la red de memoria?')) {
                // Aquí harías el fetch a un endpoint PHP para hacer el DELETE real en PostgreSQL
                document.getElementById('proj-' + id).style.display = 'none';
                logToAI(`> Elemento [ID: ${id}] purgado del Repositorio Central. Impacto en memoria liberado.`);
            }
        }

        function editProject(id) {
            // Lógica para abrir un modal de edición y hacer el UPDATE
            logToAI(`> Iniciando reestructuración del nodo [ID: ${id}]...`);
        }

        // Ingesta conectada a api_symbiote.php
        async function ingestData() {
            const payload = document.getElementById('quick-log').value;
            if(!payload) return;
            document.getElementById('quick-log').value = '';
            
            logToAI(`> Analizando: "${payload}"...`, '#f59e0b');
            
            // Llamada real al endpoint que creamos en el paso anterior
            try {
                const response = await fetch('api_symbiote.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'ingest_log', payload: payload })
                });
                const result = await response.json();
                if(result.status === 'success') {
                    // Si el usuario da una orden CRUD directa (ej. "Crea un proyecto llamado X")
                    // la IA puede devolver la instrucción para que el JS renderice la nueva tarjeta.
                    logToAI(`> Sistema: ${result.data}`, '#a855f7');
                }
            } catch (error) {
                logToAI(`> [ERROR] Conexión neuronal fallida.`, '#ef4444');
            }
        }

        function logToAI(msg, color = '#a1a1aa') {
            const consoleBox = document.getElementById('aiResponse');
            consoleBox.innerHTML += `<br><span style="color:${color};">${msg}</span>`;
            consoleBox.scrollTop = consoleBox.scrollHeight;
        }
    </script>
</body>
</html>