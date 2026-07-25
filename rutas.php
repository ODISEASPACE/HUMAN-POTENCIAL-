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
    <title>Matriz de 24 Rutas | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --accent: #805AD5; --accent-hover: #6B46C1; --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0;
            
            /* Colores por Foco */
            --c-acad: #3182CE; --c-lab: #DD6B20; --c-fin: #38A169; 
            --c-salud: #E53E3E; --c-auto: #805AD5; --c-crea: #D53F8C;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* ---------------------------------------------------
           ESTRUCTURA PRINCIPAL Y SIDEBAR
           --------------------------------------------------- */
        .app-container { display: flex; width: 100%; height: 100%; }
        
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; flex-shrink: 0; z-index: 20; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s;}
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        
        main { flex: 1; display: flex; flex-direction: column; padding: 25px 40px; overflow-y: auto; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; flex-shrink: 0; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }

        /* ---------------------------------------------------
           LA MATRIZ DE 24 RUTAS (SELECTOR VISUAL)
           --------------------------------------------------- */
        .matrix-container {
            background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            overflow-x: auto;
        }
        .matrix-table { width: 100%; border-collapse: separate; border-spacing: 8px; min-width: 800px; }
        .matrix-table th { text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid var(--border-color); }
        .matrix-table td { padding: 5px; }
        
        .route-node {
            display: block; width: 100%; padding: 12px; background: var(--bg-base); border: 2px solid var(--border-color); border-radius: 8px;
            text-align: center; cursor: pointer; transition: 0.2s; font-weight: 600; font-size: 0.85rem; color: var(--text-muted);
        }
        .route-node:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        /* Estados Activos Dinámicos */
        .route-node.active[data-obj="academico"] { border-color: var(--c-acad); background: var(--c-acad); color: white; }
        .route-node.active[data-obj="laboral"] { border-color: var(--c-lab); background: var(--c-lab); color: white; }
        .route-node.active[data-obj="financiero"] { border-color: var(--c-fin); background: var(--c-fin); color: white; }
        .route-node.active[data-obj="salud"] { border-color: var(--c-salud); background: var(--c-salud); color: white; }
        .route-node.active[data-obj="autonomo"] { border-color: var(--c-auto); background: var(--c-auto); color: white; }
        .route-node.active[data-obj="creativo"] { border-color: var(--c-crea); background: var(--c-crea); color: white; }

        /* ---------------------------------------------------
           CALENDARIO MENSUAL GENÉRICO (30 DÍAS)
           --------------------------------------------------- */
        .month-planner {
            background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .month-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .month-title { font-size: 1.2rem; font-weight: 800; color: var(--text-main); }
        
        .grid-30 {
            display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px;
        }
        .day-card {
            background: var(--bg-base); border: 1px solid var(--border-color); border-radius: 8px; min-height: 100px; padding: 8px; display: flex; flex-direction: column;
        }
        .day-num { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-align: right; }
        
        .habit-block {
            padding: 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; color: white; margin-bottom: 4px; line-height: 1.2;
            word-wrap: break-word; text-align: left; animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    </style>
</head>
<body>

    <div class="app-container">
        <nav class="sidebar">
            <div class="brand"><h2>A P H</h2></div>
            <div class="nav-links">
                <a href="#" class="nav-link">⌂ Panel Central</a>
                <a href="#" class="nav-link active">🗓 Matriz de Progresión</a>
                <a href="#" class="nav-link">🌳 Árbol de Habilidades</a>
            </div>
        </nav>

        <main>
            <div class="header-dash">
                <div>
                    <h1>Planificador de 24 Rutas</h1>
                    <p>Visualización del mapa de evolución de hábitos mensual (Agnóstico a la fecha real).</p>
                </div>
            </div>

            <!-- MATRIZ VISUAL DE RUTAS -->
            <div class="matrix-container">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Foco / Objetivo</th>
                            <th style="width: 20%;">Mes 1 (Vagabundo)</th>
                            <th style="width: 20%;">Mes 2 (Soñador)</th>
                            <th style="width: 20%;">Mes 3 (Soldado)</th>
                            <th style="width: 20%;">Mes 4 (Ejecutor)</th>
                        </tr>
                    </thead>
                    <tbody id="matrixBody">
                        <!-- Generado por JS -->
                    </tbody>
                </table>
            </div>

            <!-- VISTA MENSUAL GENÉRICA -->
            <div class="month-planner">
                <div class="month-header">
                    <div class="month-title" id="calendarTitle">Selecciona una ruta en la matriz superior</div>
                </div>
                <div class="grid-30" id="monthGrid">
                    <!-- 30 días generados por JS -->
                </div>
            </div>
        </main>
    </div>

    <script>
        // Configuración de las 24 rutas
        const objetivos = [
            { id: 'academico', name: 'Académico Universitario', color: 'var(--c-acad)' },
            { id: 'laboral', name: 'Competencia Laboral', color: 'var(--c-lab)' },
            { id: 'financiero', name: 'Desarrollo Financiero', color: 'var(--c-fin)' },
            { id: 'salud', name: 'Salud y Fisiología', color: 'var(--c-salud)' },
            { id: 'autonomo', name: 'Aprendizaje Autónomo', color: 'var(--c-auto)' },
            { id: 'creativo', name: 'Desarrollo Creativo / Social', color: 'var(--c-crea)' }
        ];

        // 1. Renderizar la Matriz de Selección
        function renderMatrix() {
            const tbody = document.getElementById('matrixBody');
            let html = '';
            
            objetivos.forEach(obj => {
                html += `<tr>
                    <td style="font-weight: 700; color: var(--text-main);">${obj.name}</td>
                    <td><div class="route-node" data-obj="${obj.id}" data-level="1" onclick="selectRoute('${obj.id}', 1, '${obj.name}')">Vagabundo</div></td>
                    <td><div class="route-node" data-obj="${obj.id}" data-level="2" onclick="selectRoute('${obj.id}', 2, '${obj.name}')">Soñador</div></td>
                    <td><div class="route-node" data-obj="${obj.id}" data-level="3" onclick="selectRoute('${obj.id}', 3, '${obj.name}')">Soldado</div></td>
                    <td><div class="route-node" data-obj="${obj.id}" data-level="4" onclick="selectRoute('${obj.id}', 4, '${obj.name}')">Ejecutor</div></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // 2. Lógica para generar el contenido de los 30 días según la ruta
        function getRoutineData(objId, level, dayNum) {
            let blocks = [];
            const isWeekend = (dayNum % 7 === 6 || dayNum % 7 === 0); // Asumimos que los días 6, 7, 13, 14 son fin de semana
            const color = objetivos.find(o => o.id === objId).color;

            // --- EJEMPLO DE INYECCIÓN DE RUTINAS PARA EL MES COMPLETO ---
            
            if (objId === 'laboral') {
                if (level === 1) { // Vagabundo: Micro rutinas diarias, 0 fricción
                    blocks.push({ time: '07:50', title: '5m Limpieza Espacio', color: color });
                } 
                else if (level === 2 && !isWeekend) { // Soñador: Bloques medios, límite de tiempo
                    blocks.push({ time: '08:00', title: '30m Práctica Código', color: color });
                }
                else if (level === 3 && !isWeekend) { // Soldado: Inmersión profunda, reglas estrictas
                    blocks.push({ time: '07:30', title: 'Planificación de Turno', color: '#4A5568' });
                    blocks.push({ time: '08:00', title: '2H Deep Work (Sin distracciones)', color: color });
                    if (dayNum % 7 === 5) blocks.push({ time: '17:00', title: 'Auditoría Semanal', color: '#E53E3E' });
                }
                else if (level === 4) { // Ejecutor: Sistematización total
                    if (!isWeekend) {
                        blocks.push({ time: '07:00', title: 'Sincronización de Equipo', color: '#4A5568' });
                        blocks.push({ time: '08:00', title: '4H Desarrollo Arquitectura', color: color });
                    } else {
                        blocks.push({ time: '10:00', title: 'I+D Proyectos Personales', color: color });
                    }
                }
            }
            
            else if (objId === 'salud') {
                if (level === 1) {
                    blocks.push({ time: 'AM', title: 'Vaso de agua + 10 Sentadillas', color: color });
                }
                else if (level === 2 && (dayNum % 2 !== 0)) { // 3 días por semana aprox
                    blocks.push({ time: '18:00', title: '45m Ejercicio (Día Intermitente)', color: color });
                }
                else if (level === 3) {
                    blocks.push({ time: '22:00', title: 'Apagar Pantallas (Higiene Sueño)', color: '#4A5568' });
                    if (dayNum % 2 !== 0) blocks.push({ time: '18:00', title: '1H Entrenamiento Fijo', color: color });
                }
                else if (level === 4) {
                    blocks.push({ time: 'AM', title: 'Seguimiento Biométrico', color: '#4A5568' });
                    blocks.push({ time: '18:00', title: 'Entrenamiento Periodizado', color: color });
                    if (dayNum % 7 === 0) blocks.push({ time: 'PM', title: 'Meal Prep (Semana entera)', color: '#38A169' });
                }
            }

            // Fallback genérico para los demás objetivos en la demo
            else {
                if (level === 1) blocks.push({ time: 'Any', title: `Micro-hábito ${objId} (10m)`, color: color });
                if (level === 2 && !isWeekend) blocks.push({ time: 'PM', title: `Bloque ${objId} (45m)`, color: color });
                if (level === 3) blocks.push({ time: 'PM', title: `Inmersión ${objId} (2H)`, color: color });
                if (level === 4) {
                    blocks.push({ time: 'AM', title: `Sistema Integrado ${objId}`, color: color });
                    if (isWeekend) blocks.push({ time: 'ALL', title: 'Desarrollo de Proyecto', color: color });
                }
            }

            return blocks;
        }

        // 3. Renderizar la Cuadrícula del Mes
        function selectRoute(objId, level, objName) {
            // Actualizar interfaz activa
            document.querySelectorAll('.route-node').forEach(el => el.classList.remove('active'));
            document.querySelector(`.route-node[data-obj="${objId}"][data-level="${level}"]`).classList.add('active');
            
            // Títulos de niveles
            const nivelesStr = ['Vagabundo', 'Soñador', 'Soldado', 'Ejecutor'];
            document.getElementById('calendarTitle').innerText = `${objName} — Mes ${level} (${nivelesStr[level-1]})`;

            // Construir los 30 días
            const grid = document.getElementById('monthGrid');
            grid.innerHTML = ''; // Limpiar
            
            for (let i = 1; i <= 30; i++) {
                const routines = getRoutineData(objId, level, i);
                
                let dayHtml = `<div class="day-card">
                                 <div class="day-num">Día ${i}</div>`;
                
                routines.forEach(r => {
                    dayHtml += `<div class="habit-block" style="background-color: ${r.color};">
                                    <strong>${r.time}</strong> - ${r.title}
                                </div>`;
                });
                
                dayHtml += `</div>`;
                grid.innerHTML += dayHtml;
            }
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', () => {
            renderMatrix();
            // Seleccionar el primer elemento por defecto
            selectRoute('laboral', 1, 'Competencia Laboral');
        });
    </script>
</body>
</html>