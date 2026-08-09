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
                <h2>>> Cronograma_Operativo (Mes Completo Expuesto)</h2>
                <div class="month-grid" id="calendar-grid">
                    <!-- Se renderiza por JS -->
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
                            <button class="btn-icon edit" onclick="openCrudModal('edit', '<?= $p['id'] ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['category'])) ?>', '<?= htmlspecialchars(addslashes($p['description'])) ?>')"><i class="fa-solid fa-pen"></i></button>
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

    <!-- MODAL REPOSITORIO -->
    <div class="modal-overlay" id="crud-modal">
        <div class="modal-crud">
            <button class="btn-close" onclick="closeCrudModal()"><i class="fa-solid fa-xmark"></i></button>
            <h3 id="modal-title">>> Iniciar_Nodo</h3>
            
            <input type="hidden" id="crud-id" value="">
            
            <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Título</label>
            <input type="text" class="input-field" id="crud-title" placeholder="Ej. Arquitectura Backend">
            
            <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Categoría</label>
            <input type="text" class="input-field" id="crud-category" placeholder="Ej. Fuentes de Estudio, Logros...">
            
            <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Descripción</label>
            <textarea class="input-field" id="crud-desc" style="resize: vertical; min-height: 80px;" placeholder="Detalles de la integración..."></textarea>
            
            <button class="btn-action" onclick="saveProject()">Ejecutar [INSERT/UPDATE]</button>
        </div>
    </div>

    <script>
        // Inyectar eventos de PHP al entorno asíncrono de JS
        const calendarEvents = <?= json_encode($events) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            renderCalendar(calendarEvents);
        });

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

        // Función para renderizar el mes entero sin ocultamiento
        function renderCalendar(events) {
            const grid = document.getElementById('calendar-grid');
            grid.innerHTML = ''; 
            
            const daysInMonth = 31; 
            
            for (let i = 1; i <= daysInMonth; i++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'day-cell';
                dayCell.innerHTML = `<span class="day-num">${i < 10 ? '0'+i : i}</span>`;
                
                const dayEvents = events.filter(evt => {
                    const evtDate = new Date(evt.start_time);
                    return evtDate.getDate() === i && evtDate.getMonth() === 7; // Agosto 2026
                });
                
                dayEvents.forEach(evt => {
                    const timeString = new Date(evt.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    dayCell.innerHTML += `
                        <div class="evt-block">
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${evt.title}</span>
                            <span>${timeString}</span>
                        </div>
                    `;
                });
                
                grid.appendChild(dayCell);
            }
        }

        // Navegación entre vistas
        function switchView(viewId) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(viewId).classList.add('active');
            event.currentTarget.classList.add('active');

            if(viewId === 'view-skills' && document.getElementById('skill-tree-container').innerHTML.includes('Conectando API')) {
                initSkillMatrix();
            }
        }

        // CONTROL DEL MODAL
        function openCrudModal(mode, id = '', title = '', category = '', desc = '') {
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

        // CREAR O ACTUALIZAR ASÍNCRONO
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
                        const newCard = `
                            <div class="project-card" id="proj-${result.id}">
                                <div class="crud-actions">
                                    <button class="btn-icon edit" onclick="openCrudModal('edit', '${result.id}', '${title}', '${category}', '${desc}')"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon delete" onclick="deleteProject(${result.id})"><i class="fa-solid fa-trash"></i></button>
                                </div>
                                <h4>${title}</h4>
                                <span style="font-size:0.75rem; color:#a1a1aa; display:block; margin-bottom:10px;">${category}</span>
                                <p style="font-size:0.8rem; color:#e4e4e7; margin:0;">${desc}</p>
                            </div>`;
                        grid.insertAdjacentHTML('afterbegin', newCard);
                        logToAI(`> Nodo "${title}" materializado en la red neuronal.`);
                    } else {
                        const card = document.getElementById(`proj-${id}`);
                        card.querySelector('h4').innerText = title;
                        card.querySelector('span').innerText = category;
                        card.querySelector('p').innerText = desc;
                        card.querySelector('.edit').setAttribute('onclick', `openCrudModal('edit', '${id}', '${title}', '${category}', '${desc}')`);
                        logToAI(`> Nodo "${title}" reestructurado.`);
                    }
                } else {
                    logToAI(`> [ERROR SQL] ${result.message}`, '#ef4444');
                }
            } catch (error) {
                logToAI(`> [FALLA DE SISTEMA] No se pudo contactar al controlador.`, '#ef4444');
            }
        }

        // ELIMINAR ASÍNCRONO
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
                    card.remove(); 
                    logToAI(`> Nodo purgado con éxito. Espacio en disco y memoria liberado.`, '#10b981');
                } else {
                    logToAI(`> [ERROR SQL] ${result.message}`, '#ef4444');
                }
            } catch (error) {
                logToAI(`> [FALLA DE SISTEMA] No se pudo completar la purga.`, '#ef4444');
            }
        }

        // INGESTA A GEMINI API
        async function ingestData() {
            const payload = document.getElementById('quick-log').value;
            if(!payload) return;
            document.getElementById('quick-log').value = '';
            
            logToAI(`> Analizando: "${payload}"...`, '#f59e0b');
            
            try {
                const response = await fetch('api_symbiote.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'ingest_log', payload: payload })
                });
                const result = await response.json();
                
                if(result.status === 'success') {
                    // Extraemos las partes específicas del JSON estructurado
                    const aiMsg = result.data.response_msg;
                    const category = result.data.suggested_category;
                    const impact = result.data.psique_impact;
                    
                    // Imprimimos la respuesta principal
                    logToAI(`> Sistema: ${aiMsg}`, '#a855f7');
                    
                    // Imprimimos la metadata (Categoría e Impacto) en un tono más sutil
                    let impactStr = impact > 0 ? `+${impact}` : impact;
                    logToAI(`> [Cat: ${category} | Impacto Psique: ${impactStr}]`, '#52525b');
                    
                } else {
                    logToAI(`> [ERROR] ${result.error}`, '#ef4444');
                }
            } catch (error) {
                logToAI(`> [ERROR] Conexión neuronal fallida. Verifica api_symbiote.php.`, '#ef4444');
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