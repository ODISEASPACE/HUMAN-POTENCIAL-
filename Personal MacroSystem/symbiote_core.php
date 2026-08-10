<?php
session_start();
require '../db.php';

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
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>APH | Symbiote Core V4</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <!-- Iconos para la UI -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        :root { --bg: #09090b; --panel: #18181b; --text: #e4e4e7; --accent: #a855f7; --border: #27272a; --danger: #ef4444; --success: #10b981; }
        
        /* Modificación CRÍTICA: Transición añadida para expandir fluidamente */
        body { 
            font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); 
            margin: 0; padding: 0; 
            display: grid; 
            grid-template-columns: 60px 1fr 350px; 
            height: 100vh; overflow: hidden; 
            transition: grid-template-columns 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Clase activadora del Modo Expansión */
        body.ai-expanded {
            grid-template-columns: 60px 1fr 60vw;
        }
        
        /* BARRA LATERAL (Navegación Simbionte) */
        .sidebar { background: #000; border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 25px; z-index: 10; }
        .nav-btn { color: #52525b; font-size: 1.2rem; cursor: pointer; transition: 0.3s; background: none; border: none; }
        .nav-btn:hover, .nav-btn.active { color: var(--accent); }

        /* ÁREA CENTRAL MULTIVISTA */
        .main-workspace { padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; min-width: 0; }
        .view-section { display: none; animation: fadeIn 0.3s; }
        .view-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 20px; }
        h2 { font-family: 'JetBrains Mono'; font-size: 1rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-top: 0; }

        /* CALENDARIO PANORÁMICO */
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
        .btn-icon { background: none; border: none; cursor: pointer; color: #a1a1aa; font-size: 0.8rem; transition: 0.2s;}
        .btn-icon:hover { transform: scale(1.1); }
        .btn-icon.edit:hover { color: var(--success); }
        .btn-icon.delete:hover { color: var(--danger); }

        /* PANEL IA DERECHO - Modificado con min-height: 0 para evitar fallos de scroll */
        .ia-panel { border-left: 1px solid var(--border); background: var(--panel); padding: 20px; display: flex; flex-direction: column; min-height: 0; z-index: 5;}
        .ai-console { flex: 1; background: #000; border: 1px solid var(--border); border-radius: 6px; padding: 15px; font-family: 'JetBrains Mono'; font-size: 0.85rem; color: #a1a1aa; overflow-y: auto; margin-top: 15px; line-height: 1.5; min-height: 0; }
        .input-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px; flex-shrink: 0; }
        .input-field { width: 100%; background: #000; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 4px; font-family: 'Inter'; font-size: 0.85rem; margin-bottom: 10px; box-sizing: border-box; }
        .btn-action { width: 100%; background: var(--accent); color: #000; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-family: 'JetBrains Mono'; transition: 0.2s;}
        .btn-action:hover { opacity: 0.9; }

        /* MODAL CRUD SIMBIONTE */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 100; }
        .modal-overlay.active { display: flex; }
        .modal-crud { background: var(--panel); border: 1px solid var(--accent); border-radius: 8px; padding: 25px; width: 100%; max-width: 400px; position: relative; box-shadow: 0 0 20px rgba(168, 85, 247, 0.2); }
        .modal-crud h3 { margin-top: 0; color: var(--accent); font-family: 'JetBrains Mono'; border-bottom: 1px dashed var(--border); padding-bottom: 10px; }
        .btn-close { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #a1a1aa; cursor: pointer; font-size: 1.2rem; }
        .btn-close:hover { color: var(--danger); }
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
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    <h2 style="border: none; padding: 0; margin: 0;">>> Cronograma_Operativo</h2>
                    
                    <!-- Controles de Tiempo -->
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-action" style="width: auto; padding: 5px 15px; background: #27272a; color: #fff;" onclick="switchCalendarView('daily')">Diaria</button>
                        <button class="btn-action" style="width: auto; padding: 5px 15px; background: #27272a; color: #fff;" onclick="switchCalendarView('weekly')">Semanal</button>
                        <button class="btn-action" style="width: auto; padding: 5px 15px;" id="btn-month" onclick="switchCalendarView('monthly')">Mensual</button>
                    </div>
                    <div style="display: flex; gap: 10px;">
    <!-- ... tus botones de Diaria, Semanal, Mensual ... -->
    <button class="btn-action" style="width: auto; padding: 5px 15px; background: transparent; color: var(--accent); border: 1px solid var(--accent);" onclick="window.location.href='custom_routines.php'">+ Crear Rutina Propia</button>
</div>
                </div>
                
                <!-- Contenedor Dinámico -->
                <div id="calendar-container" style="margin-top: 15px;">
                    <!-- El JS inyectará aquí la cuadrícula correspondiente -->
                </div>
            </div>
        </div>

        <!-- VISTA: Repositorio y Proyectos (CRUD) -->
        <div id="view-repo" class="view-section">
            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                    <h2 style="border:none; padding:0; margin:0;">>> Repositorio_Central</h2>
                    <button class="btn-action" style="width: auto; padding: 5px 15px;" onclick="openCrudModal('create')">+ Nuevo Nodo</button>
                </div>
                
                <div class="project-grid">
                    <?php foreach($projects as $p): ?>
                    <div class="project-card" id="proj-<?= $p['id'] ?>">
                        <div class="crud-actions">
                            <button class="btn-icon edit" onclick='openCrudModal("edit", <?= json_encode($p['id']) ?>, <?= htmlspecialchars(json_encode($p['title']), ENT_QUOTES, "UTF-8") ?>, <?= htmlspecialchars(json_encode($p['category']), ENT_QUOTES, "UTF-8") ?>, <?= htmlspecialchars(json_encode($p['description']), ENT_QUOTES, "UTF-8") ?>)'><i class="fa-solid fa-pen"></i></button>
                            
                            <button class="btn-icon delete" onclick='deleteProject(<?= json_encode($p['id']) ?>)'><i class="fa-solid fa-trash"></i></button>
                        </div>
                        <h4><?= htmlspecialchars($p['title']) ?></h4>
                        <span style="font-size:0.75rem; color:#a1a1aa; display:block; margin-bottom:10px;"><?= htmlspecialchars($p['category']) ?></span>
                        <p style="font-size:0.8rem; color:#e4e4e7; margin:0;"><?= htmlspecialchars($p['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- VISTA: Matriz de Habilidades -->
        <div id="view-skills" class="view-section">
            <div class="panel">
                <h2>>> Expansión_Cognitiva (Matriz 3D)</h2>
                <div id="skill-tree-container" style="height: 600px; background: #000; border: 1px dashed #3f3f46; display:flex; justify-content:center; align-items:center;">
                    <span style="color:#52525b; font-family:'JetBrains Mono';">Lienzo de Nodos Activo. Conectando API de renderizado...</span>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. PANEL DE INGESTA E IA (Siempre visible) -->
    <div class="ia-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <h2 style="font-size: 0.9rem; margin:0; border:none;">>> Enlace_Gemini_Flash</h2>
            <button class="btn-icon" onclick="toggleAIPanel()" style="color: var(--accent); font-size: 1rem;" title="Expandir/Contraer Consola"><i class="fa-solid fa-expand" id="expand-icon"></i></button>
        </div>
        
        <div class="ai-console" id="aiResponse">
            > Sistema esperando comandos...
        </div>

        <div class="input-card">
            <h3 style="margin-top:0; font-size:0.8rem; font-family:'JetBrains Mono'; border-bottom:1px solid #27272a; padding-bottom:5px;">Ingesta Rápida</h3>
            <textarea class="input-field" id="quick-log" placeholder="Registra datos, ideas o comandos de sistema..." style="resize: vertical; min-height: 80px;"></textarea>
            <button class="btn-action" onclick="ingestData()">Procesar y Ejecutar</button>
        </div>
    </div>

    <!-- MODAL REPOSITORIO -->
    <div class="modal-overlay" id="crud-modal">
        <div class="modal-crud">
            <button class="btn-close" onclick="closeCrudModal()"><i class="fa-solid fa-xmark"></i></button>
            <h3 id="modal-title">>> Iniciar_Nodo</h3>
            
            <input type="hidden" id="crud-id" value="">
            
            <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Título</label>
            <input type="text" class="input-field" id="crud-title" placeholder="Ej. Arquitectura Backend">
            
            <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Categoría</label>
            <select class="input-field" id="crud-category">
                <option value="Proyecto">Proyecto</option>
                <option value="Logros">Logros</option>
                <option value="Fuentes de Estudio">Fuentes de Estudio</option>
                <option value="Líneas de Tiempo">Líneas de Tiempo</option>
            </select>
            
            <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Descripción</label>
            <textarea class="input-field" id="crud-desc" style="resize: vertical; min-height: 80px;" placeholder="Detalles de la integración..."></textarea>
            
            <button class="btn-action" onclick="saveProject()">Ejecutar [INSERT/UPDATE]</button>
        </div>
    </div>

    <script>
        // Configuración de Hábitos Base
        const baseRoutine = [
            { title: "Bloque Operativo", time: "01:00", type: "rutina", color: "#3b82f6" },
            { title: "Santuario Biológico (Sueño)", time: "08:00", type: "rutina", color: "#10b981" },
            { title: "Desarrollo / Proyectos", time: "18:00", type: "rutina", color: "#a855f7" }
        ];

        const restDayRoutine = [
            { title: "Recuperación Total (Descanso)", time: "Todo el día", type: "descanso", color: "#52525b" }
        ];

        // Se obtienen los eventos de la DB directamente en el motor principal
        let currentEvents = <?= json_encode($events) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            // Disparador inicial de la vista mensual exhaustiva
            document.getElementById('btn-month').click(); 
        });

        // Función principal para cambiar la vista de calendario
        function switchCalendarView(viewType) {
            // Resaltar botón activo
            document.querySelectorAll('#view-calendar .btn-action').forEach(btn => {
                btn.style.background = '#27272a';
                btn.style.color = '#fff';
            });
            event.currentTarget.style.background = 'var(--accent)';
            event.currentTarget.style.color = '#000';

            const container = document.getElementById('calendar-container');
            container.innerHTML = '';

            if (viewType === 'monthly') renderMonthly(container);
            if (viewType === 'weekly') renderWeekly(container);
            if (viewType === 'daily') renderDaily(container);
        }

        // 1. VISTA MENSUAL: Exhaustiva, sin ocultar datos
        function renderMonthly(container) {
            const grid = document.createElement('div');
            grid.className = 'month-grid';
            grid.style.display = 'grid';
            grid.style.gridTemplateColumns = 'repeat(7, 1fr)';
            grid.style.gap = '5px';

            const daysInMonth = 31; // Ajustable según el mes real
            
            for (let i = 1; i <= daysInMonth; i++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'day-cell';
                dayCell.style.background = '#000';
                dayCell.style.border = '1px solid var(--border)';
                dayCell.style.minHeight = '100px';
                dayCell.style.padding = '5px';
                dayCell.style.borderRadius = '4px';

                dayCell.innerHTML = `<span style="font-family:'JetBrains Mono'; font-size:0.7rem; color:#52525b;">${i < 10 ? '0'+i : i}</span>`;

                // Filtrar eventos del día
                const dayEvents = currentEvents.filter(evt => {
                    const evtDate = new Date(evt.start_time);
                    return evtDate.getDate() === i;
                });

                const isRestDay = (i % 7 === 0); // Asumiendo el día 7, 14, 21, 28 como descanso

                if (dayEvents.length > 0) {
                    // Renderizar eventos específicos
                    dayEvents.forEach(evt => {
                        const time = new Date(evt.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        dayCell.innerHTML += createEventBlock(evt.title, time, evt.color || 'var(--accent)');
                    });
                } else {
                    // Inyectar rutina habitual o descanso
                    const routineToApply = isRestDay ? restDayRoutine : baseRoutine;
                    routineToApply.forEach(rut => {
                        dayCell.innerHTML += createEventBlock(rut.title, rut.time, rut.color);
                    });
                }
                grid.appendChild(dayCell);
            }
            container.appendChild(grid);
        }

        // 2. VISTA SEMANAL: Solo títulos para panorama rápido
        function renderWeekly(container) {
            const grid = document.createElement('div');
            grid.style.display = 'grid';
            grid.style.gridTemplateColumns = 'repeat(7, 1fr)';
            grid.style.gap = '10px';

            const today = new Date().getDate();
            
            for (let i = 0; i < 7; i++) {
                const dayNum = today + i; // Días de la semana en curso
                const col = document.createElement('div');
                col.style.background = 'rgba(255,255,255,0.02)';
                col.style.border = '1px solid var(--border)';
                col.style.borderRadius = '6px';
                col.style.padding = '10px';

                col.innerHTML = `<div style="text-align:center; font-family:'JetBrains Mono'; font-size:0.8rem; color:var(--accent); margin-bottom:10px;">Día ${dayNum}</div>`;
                
                // Simulación rápida de llenado semanal
                const isRestDay = (dayNum % 7 === 0);
                const routine = isRestDay ? restDayRoutine : baseRoutine;
                
                routine.forEach(rut => {
                    col.innerHTML += `<div style="background: rgba(255,255,255,0.05); padding: 5px; margin-bottom: 5px; border-radius: 4px; font-size: 0.75rem; color: #e4e4e7; text-align: center;">${rut.title}</div>`;
                });

                grid.appendChild(col);
            }
            container.appendChild(grid);
        }

        // 3. VISTA DIARIA: Detallada con horas y descripciones
        function renderDaily(container) {
            const timeline = document.createElement('div');
            timeline.style.display = 'flex';
            timeline.style.flexDirection = 'column';
            timeline.style.gap = '15px';

            const today = new Date().getDate();
            const isRestDay = (today % 7 === 0);
            const routine = isRestDay ? restDayRoutine : baseRoutine;

            routine.forEach(rut => {
                timeline.innerHTML += `
                    <div style="display: grid; grid-template-columns: 80px 1fr; gap: 15px; align-items: start; background: #000; padding: 15px; border-radius: 8px; border-left: 3px solid ${rut.color};">
                        <div style="font-family: 'JetBrains Mono'; color: #a1a1aa; font-size: 0.85rem; padding-top: 2px;">${rut.time}</div>
                        <div>
                            <div style="font-weight: bold; color: #fff; margin-bottom: 5px;">${rut.title}</div>
                            <div style="font-size: 0.8rem; color: #a1a1aa;">Parámetro operativo establecido según la matriz de hábitos. Ejecución obligatoria para mantener el núcleo en óptimas condiciones.</div>
                        </div>
                    </div>
                `;
            });
            container.appendChild(timeline);
        }

        // Componente visual para los bloques del calendario
        function createEventBlock(title, time, color) {
            return `
                <div style="background: rgba(255,255,255,0.05); border-left: 2px solid ${color}; font-size: 0.65rem; padding: 4px; margin-bottom: 3px; border-radius: 2px; color: #e4e4e7;">
                    <div style="font-weight:bold; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${title}</div>
                    <div style="color: #a1a1aa; font-family: 'JetBrains Mono'; margin-top:2px;">${time}</div>
                </div>
            `;
        }

        /* FUNCIÓN: Modo Expansión IA */
        function toggleAIPanel() {
            document.body.classList.toggle('ai-expanded');
            const icon = document.getElementById('expand-icon');
            
            if (document.body.classList.contains('ai-expanded')) {
                icon.classList.replace('fa-expand', 'fa-compress');
                logToAI('> Matriz visual expandida. Lóbulo Frontal en modo lectura focalizada.', '#a855f7');
            } else {
                icon.classList.replace('fa-compress', 'fa-expand');
            }
        }

        function initSkillMatrix() {
            const container = document.getElementById('skill-tree-container');
            container.innerHTML = ''; 

            const nodes = new vis.DataSet([
                { id: 1, label: 'ORIGEN\nAPH', color: '#a855f7', font: {color: 'white', size: 16, face: 'JetBrains Mono'} },
                { id: 2, label: 'Estudio Base\n10/10', color: '#18181b', font: {color: '#e4e4e7'} },
                { id: 3, label: 'Ingeniería\nde Sistemas', color: '#18181b', font: {color: '#e4e4e7'} },
                { id: 4, label: 'Python Lvl 1', color: '#18181b', font: {color: '#e4e4e7'} },
                { id: 5, label: 'Desarrollo\nTame Q', color: '#18181b', font: {color: '#e4e4e7'} }
            ]);

            const edges = new vis.DataSet([
                { from: 1, to: 2, color: '#27272a' },
                { from: 2, to: 3, color: '#27272a' },
                { from: 2, to: 4, color: '#27272a' },
                { from: 3, to: 5, color: '#27272a' }
            ]);

            const data = { nodes: nodes, edges: edges };
            const options = {
                nodes: { shape: 'box', margin: 10, borderWidth: 1, borderColor: '#3f3f46' },
                edges: { width: 2 },
                physics: { stabilization: false, barnesHut: { springLength: 150 } }
            };

            new vis.Network(container, data, options);
        }

        function switchView(viewId) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(viewId).classList.add('active');
            event.currentTarget.classList.add('active');

            if(viewId === 'view-skills' && document.getElementById('skill-tree-container').innerHTML.includes('Conectando API')) {
                initSkillMatrix();
            }
        }

        function openCrudModal(mode, id = '', title = '', category = 'Proyecto', desc = '') {
            document.getElementById('crud-modal').classList.add('active');
            document.getElementById('crud-id').value = id;
            document.getElementById('crud-title').value = title;
            document.getElementById('crud-category').value = category;
            document.getElementById('crud-desc').value = desc;
            
            document.getElementById('modal-title').innerText = mode === 'create' ? '>> Iniciar_Nodo' : '>> Reestructurar_Nodo';
        }

        function closeCrudModal() {
            document.getElementById('crud-modal').classList.remove('active');
        }

        async function saveProject() {
            const id = document.getElementById('crud-id').value;
            const title = document.getElementById('crud-title').value;
            const category = document.getElementById('crud-category').value;
            const desc = document.getElementById('crud-desc').value;
            
            if(!title || !category) {
                logToAI(`> [ADVERTENCIA] Nodos sin título o categoría son inestables. Operación abortada.`, '#ef4444');
                return;
            }

            const action = id ? 'update' : 'create';
            const payload = { action, id, title, category, description: desc };

            try {
                const response = await fetch('repo_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    closeCrudModal();
                    logToAI(`> Operación [${action.toUpperCase()}] exitosa en la base de datos.`, '#10b981');
                    
                    if (action === 'create') {
                        const grid = document.querySelector('.project-grid');
                        const newCard = document.createElement('div');
                        newCard.className = 'project-card';
                        newCard.id = `proj-${result.id}`;
                        
                        newCard.innerHTML = `
                            <div class="crud-actions">
                                <button class="btn-icon edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="btn-icon delete"><i class="fa-solid fa-trash"></i></button>
                            </div>
                            <h4>${title}</h4>
                            <span style="font-size:0.75rem; color:#a1a1aa; display:block; margin-bottom:10px;">${category}</span>
                            <p style="font-size:0.8rem; color:#e4e4e7; margin:0;">${desc}</p>
                        `;
                        
                        newCard.querySelector('.edit').onclick = () => openCrudModal('edit', result.id, title, category, desc);
                        newCard.querySelector('.delete').onclick = () => deleteProject(result.id);
                        
                        grid.prepend(newCard);
                        logToAI(`> Nodo "${title}" materializado en la red neuronal.`);
                    } else {
                        const card = document.getElementById(`proj-${id}`);
                        card.querySelector('h4').innerText = title;
                        card.querySelector('span').innerText = category;
                        card.querySelector('p').innerText = desc;
                        
                        const editBtn = card.querySelector('.edit');
                        editBtn.onclick = () => openCrudModal('edit', id, title, category, desc);
                        
                        logToAI(`> Nodo "${title}" reestructurado.`);
                    }
                } else {
                    logToAI(`> [ERROR SQL] ${result.message}`, '#ef4444');
                }
            } catch (error) {
                logToAI(`> [FALLA DE SISTEMA] No se pudo contactar al controlador.`, '#ef4444');
            }
        }

        async function deleteProject(id) {
            if(!confirm('¿Purgar este nodo de la red de memoria? La acción es irreversible.')) return;

            logToAI(`> Iniciando protocolo de purga para el nodo [ID: ${id}]...`, '#f59e0b');

            try {
                const response = await fetch('repo_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    const card = document.getElementById(`proj-${id}`);
                    if(card) card.remove(); 
                    logToAI(`> Nodo purgado con éxito. Espacio en disco y memoria liberado.`, '#10b981');
                } else {
                    logToAI(`> [ERROR SQL] ${result.message}`, '#ef4444');
                }
            } catch (error) {
                logToAI(`> [FALLA DE SISTEMA] No se pudo completar la purga.`, '#ef4444');
            }
        }

        async function ingestData() {
            const payload = document.getElementById('quick-log').value;
            if(!payload) return;
            document.getElementById('quick-log').value = '';

            let activeView = 'Módulo Desconocido';
            let viewContext = {}; 

            if (document.getElementById('view-calendar').classList.contains('active')) {
                activeView = 'Cronograma Operativo (Calendario)';
                viewContext = currentEvents; // Vinculado a tu nuevo motor
            }
            
            if (document.getElementById('view-repo').classList.contains('active')) {
                activeView = 'Repositorio Central (Proyectos y Logros)';
                const projectCards = document.querySelectorAll('.project-card');
                viewContext = Array.from(projectCards).map(card => ({
                    id: card.id.replace('proj-', ''),
                    title: card.querySelector('h4').innerText,
                    category: card.querySelector('span').innerText
                }));
            }

            if (document.getElementById('view-skills').classList.contains('active')) {
                activeView = 'Matriz de Habilidades (Nodos)';
                viewContext = { info: "Matriz 3D visualizada por el usuario. Nodos base activos." };
            }
            
            logToAI(`> Analizando input desde el módulo: [${activeView}]...`, '#f59e0b');
            
            try {
                const response = await fetch('api_symbiote.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'analyze_context',
                        payload: payload,
                        current_view: activeView,
                        live_data: viewContext 
                    })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    try {
                        if (typeof result.data === 'object' && result.data !== null) {
                            logToAI(`> Análisis de Conciencia:`, '#10b981');
                            logToAI(`"${result.data.analysis}"`, '#d8b4fe');
                        } else {
                            let cleanJson = result.data.replace(/```json/g, '').replace(/```/g, '').trim();
                            let aiData = JSON.parse(cleanJson);
                            logToAI(`> Análisis de Conciencia:`, '#10b981');
                            logToAI(`"${aiData.analysis}"`, '#d8b4fe');
                        }
                    } catch (e) {
                        logToAI(`> [ERROR DE FORMATO]`, '#ef4444');
                        logToAI(`"${JSON.stringify(result.data)}"`, '#d8b4fe');
                        console.error("Error al procesar la data de la IA:", e);
                    }
                } else {
                    logToAI(`> [ERROR IA] ${result.error}`, '#ef4444');
                }
            } catch (error) {
                logToAI(`> [ERROR] Conexión neuronal fallida.`, '#ef4444');
            }
        }

        function logToAI(msg, color = '#a1a1aa') {
            const consoleBox = document.getElementById('aiResponse');
            consoleBox.innerHTML += `<br><span style="color:${color};">${msg}</span>`;
            // Auto-scroll forzado
            consoleBox.scrollTop = consoleBox.scrollHeight;
        }
        
    </script>
</body>
</html>