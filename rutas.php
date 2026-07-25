<?php
session_start();
// Simulación de usuario
$user = [
    'username' => 'Daniel',
    'profession' => 'Ingeniería de Sistemas',
    'profile_picture' => ''
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Planificador de Rutas | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --accent: #805AD5; --accent-hover: #6B46C1; --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0;
            
            /* Colores por Foco (Ningún esquema verde, mantienendo consistencia temática) */
            --c-acad: #3182CE; 
            --c-lab: #DD6B20; 
            --c-fin: #D69E2E; /* Dorado en lugar de verde */
            --c-salud: #E53E3E; 
            --c-auto: #805AD5; 
            --c-crea: #D53F8C;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* ---------------------------------------------------
           BLOQUEO MÓVIL LANDSCAPE
           --------------------------------------------------- */
        #landscape-lock {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-panel); z-index: 9999; flex-direction: column; justify-content: center; align-items: center;
            text-align: center; padding: 30px;
        }
        #landscape-lock h2 { color: var(--accent); margin-bottom: 15px; font-weight: 800; }
        #landscape-lock p { color: var(--text-muted); font-size: 1rem; line-height: 1.5; }
        @media screen and (orientation: portrait) and (max-width: 768px) {
            #landscape-lock { display: flex !important; }
            .app-container { display: none !important; }
        }

        /* ---------------------------------------------------
           ESTRUCTURA PRINCIPAL Y SIDEBAR
           --------------------------------------------------- */
        .app-container { display: flex; width: 100%; height: 100%; }
        
        nav.sidebar { width: 220px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 25px 15px; flex-shrink: 0; z-index: 20; }
        .brand { text-align: center; margin-bottom: 30px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.2rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 10px 12px; font-size: 0.85rem; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s;}
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        
        main { flex: 1; display: flex; flex-direction: column; padding: 20px 30px; overflow-y: auto; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; flex-shrink: 0; }
        .header-dash h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); font-size: 0.85rem; }

        /* ---------------------------------------------------
           MATRIZ DE RUTAS COMPACTA (Solución a saturación de Zoom)
           --------------------------------------------------- */
        .matrix-container { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .matrix-grid { display: grid; grid-template-columns: 1.2fr repeat(4, 1fr); gap: 6px; align-items: center; }
        .matrix-header { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; padding-bottom: 5px; border-bottom: 2px solid var(--border-color); margin-bottom: 5px; }
        
        .foco-title { font-weight: 700; color: var(--text-main); font-size: 0.8rem; padding-right: 10px; }
        
        .route-node {
            display: flex; align-items: center; justify-content: center; width: 100%; padding: 6px 8px; 
            background: var(--bg-base); border: 1px solid var(--border-color); border-radius: 6px;
            cursor: pointer; transition: 0.2s; font-weight: 600; font-size: 0.75rem; color: var(--text-muted);
        }
        .route-node:hover { transform: translateY(-1px); border-color: var(--accent); color: var(--accent); }
        
        /* Estados Activos */
        .route-node.active[data-obj="academico"] { border-color: var(--c-acad); background: var(--c-acad); color: white; }
        .route-node.active[data-obj="laboral"] { border-color: var(--c-lab); background: var(--c-lab); color: white; }
        .route-node.active[data-obj="financiero"] { border-color: var(--c-fin); background: var(--c-fin); color: white; }
        .route-node.active[data-obj="salud"] { border-color: var(--c-salud); background: var(--c-salud); color: white; }
        .route-node.active[data-obj="autonomo"] { border-color: var(--c-auto); background: var(--c-auto); color: white; }
        .route-node.active[data-obj="creativo"] { border-color: var(--c-crea); background: var(--c-crea); color: white; }

        /* ---------------------------------------------------
           CALENDARIO Y CONTROLES DE VISTA
           --------------------------------------------------- */
        .month-planner { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1; display: flex; flex-direction: column; }
        
        .planner-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .month-title { font-size: 1.1rem; font-weight: 800; color: var(--text-main); }
        
        .view-controls { display: flex; gap: 5px; background: var(--bg-base); padding: 4px; border-radius: 8px; border: 1px solid var(--border-color); }
        .btn-view { border: none; background: transparent; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.2s; }
        .btn-view.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        .dynamic-grid { display: grid; gap: 8px; flex: 1; align-content: start; }
        /* Clases inyectadas por JS según la vista */
        .grid-month { grid-template-columns: repeat(7, 1fr); }
        .grid-week { grid-template-columns: repeat(7, 1fr); }
        .grid-day { grid-template-columns: 1fr; max-width: 600px; margin: 0 auto; width: 100%; }

        .day-card { background: var(--bg-base); border: 1px solid var(--border-color); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; transition: 0.2s; }
        
        .day-header { padding: 6px 10px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-align: right; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s; }
        .day-header:hover { background: var(--border-color); color: var(--accent); }
        
        .day-body { padding: 6px; display: flex; flex-direction: column; gap: 4px; min-height: 80px; }
        .grid-day .day-body { min-height: auto; padding: 15px; gap: 10px; }

        .habit-block {
            padding: 5px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; color: white; 
            line-height: 1.2; cursor: pointer; transition: transform 0.1s, box-shadow 0.1s;
            display: flex; align-items: flex-start; gap: 5px;
        }
        .habit-block:hover { transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .grid-day .habit-block { font-size: 0.85rem; padding: 10px; border-radius: 6px; }

        /* Mínimo en vista Mes */
        .month-minimal .habit-block { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.65rem; padding: 4px; }

        /* ---------------------------------------------------
           MODAL DE DESCRIPCIÓN
           --------------------------------------------------- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; opacity: 0; transition: opacity 0.2s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background: #fff; width: 100%; max-width: 400px; border-radius: 12px; padding: 25px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.15); transform: scale(0.95); transition: transform 0.2s; }
        .modal-overlay.active .modal-content { transform: scale(1); }
        .close-btn { position: absolute; top: 15px; right: 15px; font-size: 1.5rem; cursor: pointer; border: none; background: none; color: var(--text-muted); line-height: 1; }
        .close-btn:hover { color: var(--text-main); }
        
        .modal-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; color: white; margin-bottom: 15px; }
        .modal-title { font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 10px; }
        .modal-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; }
    </style>
</head>
<body>
    <div id="landscape-lock"><h2>Rotar Dispositivo</h2><p>Modo horizontal requerido.</p></div>

    <div class="app-container">
        <nav class="sidebar">
            <div class="brand"><h2>A P H</h2></div>
            <div class="nav-links">
                <a href="#" class="nav-link">⌂ Panel Central</a>
                <a href="#" class="nav-link active">🗓 Matriz de Progresión</a>
            </div>
        </nav>

        <main>
            <div class="header-dash">
                <div>
                    <h1>Planificador de 24 Rutas</h1>
                    <p>Mapa de evolución de hábitos agnóstico a la fecha real.</p>
                </div>
            </div>

            <!-- MATRIZ VISUAL COMPACTA -->
            <div class="matrix-container">
                <div class="matrix-grid">
                    <div class="matrix-header">Foco / Objetivo</div>
                    <div class="matrix-header">Mes 1 (Vagabundo)</div>
                    <div class="matrix-header">Mes 2 (Soñador)</div>
                    <div class="matrix-header">Mes 3 (Soldado)</div>
                    <div class="matrix-header">Mes 4 (Ejecutor)</div>
                </div>
                <div class="matrix-grid" id="matrixBody">
                    <!-- Generado por JS -->
                </div>
            </div>

            <!-- VISTAS DEL CALENDARIO -->
            <div class="month-planner">
                <div class="planner-header">
                    <div class="month-title" id="calendarTitle">Selecciona una ruta</div>
                    <div class="view-controls">
                        <button class="btn-view active" onclick="setViewMode('month')" id="btn-month">Mes</button>
                        <button class="btn-view" onclick="setViewMode('week')" id="btn-week">Semana</button>
                        <button class="btn-view" onclick="setViewMode('day')" id="btn-day">Día</button>
                    </div>
                </div>
                
                <!-- Pagidador Semana/Día (Oculto en vista mes) -->
                <div id="subnavControls" style="display:none; justify-content:space-between; margin-bottom: 15px;">
                    <button class="btn-view" onclick="navigate(-1)">⬅ Anterior</button>
                    <span id="subnavLabel" style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; align-self: center;"></span>
                    <button class="btn-view" onclick="navigate(1)">Siguiente ➡</button>
                </div>

                <div class="dynamic-grid grid-month month-minimal" id="calendarGrid">
                    <!-- Cuadrícula generada por JS -->
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL DE DESCRIPCIÓN -->
    <div id="habitModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <div class="modal-badge" id="modalTime">00:00</div>
            <h2 class="modal-title" id="modalTitle">Título</h2>
            <p class="modal-desc" id="modalDesc">Descripción de la rutina estructurada.</p>
        </div>
    </div>

    <script>
        const objetivos = [
            { id: 'academico', name: 'Académico Universitario', color: 'var(--c-acad)' },
            { id: 'laboral', name: 'Competencia Laboral', color: 'var(--c-lab)' },
            { id: 'financiero', name: 'Desarrollo Financiero', color: 'var(--c-fin)' },
            { id: 'salud', name: 'Salud y Fisiología', color: 'var(--c-salud)' },
            { id: 'autonomo', name: 'Aprendizaje Autónomo', color: 'var(--c-auto)' },
            { id: 'creativo', name: 'Desarrollo Creativo / Social', color: 'var(--c-crea)' }
        ];

        let currentRoute = { objId: 'laboral', level: 1, name: 'Competencia Laboral' };
        let currentView = 'month'; // 'month', 'week', 'day'
        let currentPointer = 1; // Día actual seleccionado o inicio de semana

        // 1. Render Matriz Compacta
        function renderMatrix() {
            const container = document.getElementById('matrixBody');
            let html = '';
            
            objetivos.forEach(obj => {
                html += `
                    <div class="foco-title">${obj.name}</div>
                    <div class="route-node" data-obj="${obj.id}" data-level="1" onclick="selectRoute('${obj.id}', 1, '${obj.name}')">Vagabundo</div>
                    <div class="route-node" data-obj="${obj.id}" data-level="2" onclick="selectRoute('${obj.id}', 2, '${obj.name}')">Soñador</div>
                    <div class="route-node" data-obj="${obj.id}" data-level="3" onclick="selectRoute('${obj.id}', 3, '${obj.name}')">Soldado</div>
                    <div class="route-node" data-obj="${obj.id}" data-level="4" onclick="selectRoute('${obj.id}', 4, '${obj.name}')">Ejecutor</div>
                `;
            });
            container.innerHTML = html;
        }

        // 2. Base de Datos Mockeada (Con descripciones)
        function getRoutineData(objId, level, dayNum) {
            let blocks = [];
            const isWeekend = (dayNum % 7 === 6 || dayNum % 7 === 0);
            const color = objetivos.find(o => o.id === objId).color;

            if (objId === 'laboral') {
                if (level === 1) {
                    blocks.push({ time: '07:50', title: 'Limpieza de Espacio', desc: '5 minutos para organizar el escritorio y cerrar pestañas irrelevantes. Reduce la fricción visual antes de iniciar.', color: color });
                } 
                else if (level === 2 && !isWeekend) {
                    blocks.push({ time: '08:00', title: 'Práctica de Código', desc: '30 minutos enfocados en PHP/Python. Límite de tiempo estricto para forzar la ejecución de ideas.', color: color });
                }
                else if (level === 3 && !isWeekend) {
                    blocks.push({ time: '07:30', title: 'Planificación de Turno', desc: 'Categorización de requerimientos urgentes. Eliminación total de la multitarea.', color: '#4A5568' });
                    blocks.push({ time: '08:00', title: 'Deep Work (Sin distracciones)', desc: 'Bloque de inmersión profunda. Operación enfocada apuntando al propósito central.', color: color });
                }
                else if (level === 4) {
                    if (!isWeekend) {
                        blocks.push({ time: '08:00', title: 'Desarrollo de Arquitectura Core', desc: 'Sistematización pura. Integración en el estilo de vida soportando fricción alta.', color: color });
                    }
                }
            } else {
                // Datos genéricos para los demás
                blocks.push({ time: 'AM', title: `Hábito de ${currentRoute.name}`, desc: `Esta rutina está diseñada para el nivel ${level}. El objetivo es generar constancia progresiva.`, color: color });
                if(level > 2) blocks.push({ time: 'PM', title: 'Auditoría / Bloque Profundo', desc: 'Revisión del sistema y trabajo ininterrumpido.', color: color });
            }
            return blocks;
        }

        // 3. Manejo de Vistas
        function setViewMode(mode, targetDay = null) {
            currentView = mode;
            document.querySelectorAll('.btn-view').forEach(b => b.classList.remove('active'));
            document.getElementById('btn-' + mode).classList.add('active');
            
            const grid = document.getElementById('calendarGrid');
            grid.className = `dynamic-grid grid-${mode} ${mode === 'month' ? 'month-minimal' : ''}`;
            
            const subnav = document.getElementById('subnavControls');
            subnav.style.display = (mode === 'month') ? 'none' : 'flex';

            if(targetDay !== null) currentPointer = targetDay;
            else if(mode === 'week') currentPointer = Math.floor((currentPointer - 1) / 7) * 7 + 1; // Ir al inicio de la semana actual

            renderCalendar();
        }

        function navigate(dir) {
            if (currentView === 'week') {
                currentPointer += (dir * 7);
                if (currentPointer < 1) currentPointer = 1;
                if (currentPointer > 30) currentPointer = 22; // Última semana
            } else if (currentView === 'day') {
                currentPointer += dir;
                if (currentPointer < 1) currentPointer = 1;
                if (currentPointer > 30) currentPointer = 30;
            }
            renderCalendar();
        }

        function selectRoute(objId, level, objName) {
            currentRoute = { objId, level, name: objName };
            document.querySelectorAll('.route-node').forEach(el => el.classList.remove('active'));
            document.querySelector(`.route-node[data-obj="${objId}"][data-level="${level}"]`).classList.add('active');
            
            const nivelesStr = ['Vagabundo', 'Soñador', 'Soldado', 'Ejecutor'];
            document.getElementById('calendarTitle').innerText = `${objName} — Mes ${level} (${nivelesStr[level-1]})`;
            renderCalendar();
        }

        // 4. Renderizado Inteligente
        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            const subnavLabel = document.getElementById('subnavLabel');
            grid.innerHTML = '';
            
            let startDay = 1, endDay = 30;

            if (currentView === 'week') {
                startDay = currentPointer;
                endDay = Math.min(startDay + 6, 30);
                subnavLabel.innerText = `Día ${startDay} al Día ${endDay}`;
            } else if (currentView === 'day') {
                startDay = currentPointer;
                endDay = currentPointer;
                subnavLabel.innerText = `Día Específico: ${startDay}`;
            }

            for (let i = startDay; i <= endDay; i++) {
                const routines = getRoutineData(currentRoute.objId, currentRoute.level, i);
                
                let dayHtml = `<div class="day-card">
                    <div class="day-header" onclick="setViewMode('day', ${i})" title="Ver detalles de este día">
                        Día ${i} ${currentView === 'month' ? '🔍' : ''}
                    </div>
                    <div class="day-body">`;
                
                routines.forEach((r, idx) => {
                    dayHtml += `
                        <div class="habit-block" style="background-color: ${r.color};" 
                             onclick="openModal('${r.title}', '${r.time}', '${r.desc}', '${r.color}')">
                            <span style="opacity:0.8;">${r.time}</span>
                            <span>${r.title}</span>
                        </div>`;
                });
                
                dayHtml += `</div></div>`;
                grid.innerHTML += dayHtml;
            }
        }

        // 5. Modal Logic
        function openModal(title, time, desc, color) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalTime').innerText = time;
            document.getElementById('modalTime').style.backgroundColor = color;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('habitModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('habitModal').classList.remove('active');
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', () => {
            renderMatrix();
            selectRoute('laboral', 1, 'Competencia Laboral');
        });
    </script>
</body>
</html>