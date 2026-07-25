<?php
session_start();
// require 'db.php'; // Descomentar cuando se conecte a producción

// Simulación de usuario para la demo visual
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
    <title>Rutas de Progreso | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root { 
            --bg-base: #FAFAFC; 
            --bg-panel: #FFFFFF; 
            --text-main: #1A202C; 
            --text-muted: #718096; 
            --accent: #805AD5; /* Púrpura suave moderno */
            --accent-hover: #6B46C1; 
            --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0;
            --blue-soft: #3182CE;
            --orange-soft: #DD6B20;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* ---------------------------------------------------
           BLOQUEO ESTRICTO LANDSCAPE PARA MÓVILES
           --------------------------------------------------- */
        #landscape-lock {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-panel); z-index: 9999;
            flex-direction: column; justify-content: center; align-items: center;
            text-align: center; padding: 30px;
        }
        #landscape-lock h2 { color: var(--accent); margin-bottom: 15px; font-weight: 800; }
        #landscape-lock p { color: var(--text-muted); font-size: 1rem; line-height: 1.5; }
        #landscape-lock .rotate-icon { font-size: 3rem; margin-bottom: 20px; color: var(--accent); animation: rotatePhone 2s infinite ease-in-out; }

        @keyframes rotatePhone {
            0% { transform: rotate(0deg); }
            50% { transform: rotate(-90deg); }
            100% { transform: rotate(-90deg); }
        }

        @media screen and (orientation: portrait) and (max-width: 768px) {
            #landscape-lock { display: flex !important; }
            .app-container { display: none !important; }
        }

        /* ---------------------------------------------------
           ESTRUCTURA PRINCIPAL Y SIDEBAR
           --------------------------------------------------- */
        .app-container { display: flex; width: 100%; height: 100%; }
        
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; flex-shrink: 0; z-index: 20; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s;}
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        
        main { flex: 1; display: flex; flex-direction: column; padding: 25px 40px; overflow: hidden; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; flex-shrink: 0; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }

        /* ---------------------------------------------------
           CONTROLES: SELECTOR Y LÍNEA DE PROGRESO
           --------------------------------------------------- */
        .controls-section { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; flex-shrink: 0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 20px;}
        
        .objective-selector select { width: 100%; max-width: 400px; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; font-weight: 600; color: var(--text-main); background: var(--bg-base); cursor: pointer; outline: none; transition: 0.2s; }
        .objective-selector select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-light); }

        .progression-line { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 10px 0; }
        .progression-line::before { content: ''; position: absolute; top: 50%; left: 0; width: 100%; height: 4px; background: var(--border-color); z-index: 1; transform: translateY(-50%); border-radius: 2px; }
        
        .level-node { position: relative; z-index: 2; background: var(--bg-panel); border: 2px solid var(--border-color); padding: 10px 20px; border-radius: 30px; font-weight: 700; color: var(--text-muted); cursor: pointer; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .level-node:hover { transform: translateY(-3px); border-color: var(--accent-hover); color: var(--accent-hover); }
        .level-node.active { background: var(--accent); border-color: var(--accent); color: white; box-shadow: 0 5px 15px var(--accent-light); transform: scale(1.05); }
        .level-node .month-badge { background: var(--border-color); color: var(--text-muted); padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; }
        .level-node.active .month-badge { background: rgba(255,255,255,0.2); color: white; }

        /* ---------------------------------------------------
           CALENDARIO
           --------------------------------------------------- */
        .calendar-wrapper { flex: 1; background: var(--bg-panel); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); min-height: 0; display: flex; flex-direction: column; }
        #calendar { flex: 1; min-height: 0; }
        
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border-color); }
        .fc-button-primary { background-color: var(--accent) !important; border-color: var(--accent) !important; text-transform: capitalize; }
        .fc-event { border-radius: 6px; border: none !important; padding: 3px 6px; font-size: 0.75em; font-weight: 600; cursor: default; box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: #ffffff !important; }
        .fc-v-event .fc-event-main-frame { padding: 2px; }
    </style>
</head>
<body>

    <!-- Bloqueo Landscape -->
    <div id="landscape-lock">
        <div class="rotate-icon">⟳</div>
        <h2>Modo Horizontal Requerido</h2>
        <p>Para visualizar la agenda mensual y las rutas completas sin recortes visuales, por favor gira tu dispositivo.</p>
    </div>

    <div class="app-container">
        <nav class="sidebar">
            <div class="brand"><h2>A P H</h2></div>
            <div class="nav-links">
                <a href="#" class="nav-link">⌂ Panel Central</a>
                <a href="#" class="nav-link active">🗓 Rutas de Progreso</a>
                <a href="#" class="nav-link">🌳 Árbol de Habilidades</a>
            </div>
        </nav>

        <main>
            <div class="header-dash">
                <div>
                    <h1>Agenda de Progresión</h1>
                    <p>Simulador de inyección de rutinas según tu nivel de fricción actual.</p>
                </div>
            </div>

            <div class="controls-section">
                <div class="objective-selector">
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">1. Selecciona tu Foco Central</label>
                    <select id="objSelect" onchange="renderRoutines()">
                        <option value="academico">Académico Universitario</option>
                        <option value="laboral">Competencia Laboral</option>
                        <option value="financiero">Desarrollo Financiero</option>
                        <option value="salud">Salud y Fisiología</option>
                        <option value="autonomo">Aprendizaje Autónomo</option>
                        <option value="creativo">Desarrollo Creativo / Social</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:var(--text-muted); margin-bottom:15px; text-transform:uppercase;">2. Evolución del Hábito (1 Nivel = 1 Mes)</label>
                    <div class="progression-line">
                        <div class="level-node active" data-level="1" onclick="setLevel(1)">
                            <span class="month-badge">M1</span> Vagabundo
                        </div>
                        <div class="level-node" data-level="2" onclick="setLevel(2)">
                            <span class="month-badge">M2</span> Soñador
                        </div>
                        <div class="level-node" data-level="3" onclick="setLevel(3)">
                            <span class="month-badge">M3</span> Soldado
                        </div>
                        <div class="level-node" data-level="4" onclick="setLevel(4)">
                            <span class="month-badge">M4</span> Ejecutor
                        </div>
                    </div>
                </div>
            </div>

            <div class="calendar-wrapper">
                <div id='calendar'></div>
            </div>
        </main>
    </div>

    <script>
        let calendar;
        let currentLevel = 1;
        const colorAccent = '#805AD5'; // Púrpura principal
        const colorBlue = '#3182CE';   // Azul suave para secundarias
        const colorOrange = '#DD6B20'; // Naranja para advertencias o eventos cortos

        function setLevel(level) {
            currentLevel = level;
            document.querySelectorAll('.level-node').forEach(el => el.classList.remove('active'));
            document.querySelector(`.level-node[data-level="${level}"]`).classList.add('active');
            renderRoutines();
        }

        // Simulación de generador de eventos para la semana actual
        function generateEventsForCurrentWeek(objective, level) {
            let events = [];
            const curr = new Date();
            const first = curr.getDate() - curr.getDay() + 1; // Lunes como inicio
            
            for(let i = 0; i < 7; i++) {
                let d = new Date(curr.setDate(first + i));
                let dateStr = d.toISOString().split('T')[0];
                let isWeekend = (d.getDay() === 0 || d.getDay() === 6);

                // --- Lógica de Rutinas Mockeada según el contexto y reglas visuales ---
                if (objective === 'academico') {
                    if (level === 1) events.push({ title: 'Repaso Algoritmos (10m)', start: `${dateStr}T14:00:00`, end: `${dateStr}T14:10:00`, backgroundColor: colorBlue });
                    if (level === 2) events.push({ title: 'Pomodoro Universidad', start: `${dateStr}T15:00:00`, end: `${dateStr}T15:45:00`, backgroundColor: colorBlue });
                    if (level === 3) events.push({ title: 'Estudio Aislado (Filtros)', start: `${dateStr}T15:00:00`, end: `${dateStr}T17:00:00`, backgroundColor: colorAccent });
                    if (level === 4) events.push({ title: 'Sistematización de Sílabo', start: `${dateStr}T14:00:00`, end: `${dateStr}T17:00:00`, backgroundColor: colorAccent });
                }

                if (objective === 'laboral') {
                    if (level === 1) events.push({ title: 'Limpiar Espacio Trabajo', start: `${dateStr}T01:50:00`, end: `${dateStr}T01:55:00`, backgroundColor: colorOrange });
                    if (level === 2 && !isWeekend) events.push({ title: 'Práctica PHP/Python', start: `${dateStr}T14:00:00`, end: `${dateStr}T14:30:00`, backgroundColor: colorBlue });
                    // Nivel Soldado: Turno operativo integrado y estructurado sin distracciones
                    if (level === 3 && !isWeekend) {
                        events.push({ title: 'Estructuración de Tickets', start: `${dateStr}T01:45:00`, end: `${dateStr}T02:00:00`, backgroundColor: colorOrange });
                        events.push({ title: 'Turno Operativo (Deep Work)', start: `${dateStr}T02:00:00`, end: `${dateStr}T08:00:00`, backgroundColor: colorAccent });
                    }
                    if (level === 4) events.push({ title: 'Desarrollo Core Software', start: `${dateStr}T10:00:00`, end: `${dateStr}T13:00:00`, backgroundColor: colorAccent });
                }

                if (objective === 'creativo') {
                    if (level === 1) events.push({ title: '1 Partida Mobile Legends', start: `${dateStr}T20:00:00`, end: `${dateStr}T20:20:00`, backgroundColor: colorBlue });
                    if (level === 2 && isWeekend) events.push({ title: 'Gestión Server / Hobby', start: `${dateStr}T16:00:00`, end: `${dateStr}T16:45:00`, backgroundColor: colorBlue });
                    if (level === 3 && isWeekend) events.push({ title: 'Tiempo Off-Screen (Familia)', start: `${dateStr}T14:00:00`, end: `${dateStr}T18:00:00`, backgroundColor: colorAccent });
                    if (level === 4 && isWeekend) events.push({ title: 'Proyecto Retrato/Avatar 3D', start: `${dateStr}T09:00:00`, end: `${dateStr}T13:00:00`, backgroundColor: colorAccent });
                }
                
                // Se agregarían los demás objetivos con la misma lógica
            }
            return events;
        }

        function renderRoutines() {
            const obj = document.getElementById('objSelect').value;
            const events = generateEventsForCurrentWeek(obj, currentLevel);
            
            calendar.removeAllEvents();
            calendar.addEventSource(events);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                height: '100%', 
                initialView: 'timeGridWeek', 
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay' },
                slotMinTime: '00:00:00', // Modificado para permitir visualizar los turnos de madrugada
                slotMaxTime: '24:00:00',
                slotEventOverlap: false,
                allDaySlot: false,
                firstDay: 1, // Lunes
                nowIndicator: true,
            });

            calendar.render();
            renderRoutines(); // Carga inicial
        });
    </script>
</body>
</html>