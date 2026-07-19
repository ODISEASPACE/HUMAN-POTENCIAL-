<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario y Rutinas | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Librerías de FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-hover: #6B46C1; --border-color: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; margin: 0; overflow: hidden;}
        
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }

        /* Controles de Filtro (Tabs) */
        .calendar-controls { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .filter-btn { background: transparent; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.3s; }
        .filter-btn:hover { background: rgba(0,0,0,0.03); color: var(--text-main); }
        .filter-btn.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }

        /* Panel de Rutinas (Oculto por defecto) */
        .routines-panel { display: none; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .routines-panel.active { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .routine-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: 0.3s; }
        .routine-card:hover { border-color: var(--accent); transform: translateY(-2px); }
        .routine-card h4 { margin-bottom: 5px; font-size: 1rem; }
        .routine-card p { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 10px; }
        .btn-generate { background: var(--accent); color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.85rem;}
        .btn-generate:hover { background: var(--accent-hover); }

        /* Contenedor del Calendario */
        #calendar-container { background: var(--bg-panel); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.02); flex: 1; min-height: 500px; }

        /* Personalización de FullCalendar */
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border-color); }
        .fc-button-primary { background-color: var(--accent) !important; border-color: var(--accent) !important; }
        .fc-event { border-radius: 6px; border: none; padding: 3px 5px; font-size: 0.85em; font-weight: 600; cursor: pointer; color: #fff !important; }
    </style>
</head>
<body>

    <!-- Sidebar Modular -->
    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-dash">
            <div>
                <h1>Gestión de Tiempo</h1>
                <p>Visualiza tus eventos, inyecta rutinas y monitorea tu progreso humano.</p>
            </div>
        </div>

        <!-- Filtros de Vista -->
        <div class="calendar-controls">
            <button class="filter-btn active" onclick="setFilter('all', this)">🌐 Todo</button>
            <button class="filter-btn" onclick="setFilter('agenda', this)">📅 Agenda / Tareas</button>
            <button class="filter-btn" onclick="setFilter('rutinas', this)">🔁 Hábitos y Rutinas</button>
            <button class="filter-btn" onclick="setFilter('progreso', this)">📈 Línea de Progreso</button>
        </div>

        <!-- Panel de Generación de Rutinas -->
        <div class="routines-panel" id="routinesPanel">
            <div class="routine-card">
                <h4 style="color: #38A169;">Nivel Simple</h4>
                <p>8h sueño, 3 comidas base, 30m actividad física leve. Ideal para mantenimiento.</p>
                <button class="btn-generate" onclick="generateRoutine('simple')">Inyectar en la Semana</button>
            </div>
            <div class="routine-card">
                <h4 style="color: #D69E2E;">Nivel Intermedio</h4>
                <p>7h sueño, 4h Deep Work, 1h ejercicio, lectura. Enfoque y disciplina.</p>
                <button class="btn-generate" onclick="generateRoutine('intermedio')">Inyectar en la Semana</button>
            </div>
            <div class="routine-card">
                <h4 style="color: #E53E3E;">Nivel Extremo</h4>
                <p>6h sueño, 8h bloques de trabajo, entrenamiento intenso, ayuno pautado.</p>
                <button class="btn-generate" onclick="generateRoutine('extremo')">Inyectar en la Semana</button>
            </div>
            <div class="routine-card" style="border-style: dashed;">
                <h4 style="color: var(--accent);">Personalizada</h4>
                <p>Crea tu propio bloque de rutinas desde cero utilizando el calendario.</p>
                <button class="btn-generate" style="background: white; color: var(--accent); border: 1px solid var(--accent);" onclick="alert('Usa el calendario para arrastrar y crear eventos etiquetados como rutinas.')">Configurar Manualmente</button>
            </div>
        </div>

        <div id="calendar-container">
            <div id='calendar'></div>
        </div>
    </main>

    <script>
        let calendar;
        let currentFilter = 'all';

        // Lógica de Pestañas y Filtros
        function setFilter(type, btn) {
            currentFilter = type;
            
            // Actualizar UI botones
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Mostrar/Ocultar panel de rutinas
            const panel = document.getElementById('routinesPanel');
            if (type === 'rutinas') {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }

            // Recargar eventos del calendario
            calendar.refetchEvents();
        }

        // Generador de Rutinas (Llamada al Backend)
        function generateRoutine(level) {
            if (confirm(`¿Estás seguro de que deseas inyectar la rutina nivel "${level}" para los próximos 7 días?`)) {
                let formData = new FormData();
                formData.append('action', 'generate_routines');
                formData.append('level', level);
                formData.append('start_date', calendar.view.activeStart.toISOString());

                fetch('api_events.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Rutinas inyectadas con éxito.');
                        calendar.refetchEvents();
                    } else {
                        alert('Error al generar rutinas.');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // Inicialización de FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek', 
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                slotMinTime: '04:00:00', // Ampliado para el nivel extremo
                slotMaxTime: '24:00:00',
                editable: true, 
                selectable: true, 
                nowIndicator: true,
                
                // 1. OBTENER EVENTOS (Dinámico basado en el filtro)
                events: function(info, successCallback, failureCallback) {
                    fetch(`api_events.php?action=fetch&type=${currentFilter}&start=${info.startStr}&end=${info.endStr}`)
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => failureCallback(error));
                },
                
                // 2. CREAR EVENTO
                select: function(info) {
                    // Si estamos en la vista de progreso, no deberíamos poder crear eventos manuales fácilmente
                    if(currentFilter === 'progreso') {
                        alert("Los registros de progreso se generan automáticamente desde la Bitácora o el Repositorio.");
                        calendar.unselect();
                        return;
                    }

                    let title = prompt('Título del nuevo evento o rutina:');
                    if (title) {
                        let isRoutine = (currentFilter === 'rutinas') ? true : confirm('¿Este evento es una rutina/hábito repetitivo?');
                        
                        let formData = new FormData();
                        formData.append('action', 'add');
                        formData.append('title', title);
                        formData.append('start', info.startStr);
                        if (info.endStr) formData.append('end', info.endStr);
                        formData.append('event_type', isRoutine ? 'rutina' : 'agenda');

                        fetch('api_events.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                calendar.refetchEvents(); // Recargamos para que aplique colores y filtros correctos
                            } else {
                                alert('Error al guardar el evento');
                            }
                        });
                    }
                    calendar.unselect();
                },

                // 3. ACTUALIZAR EVENTO
                eventDrop: function(info) { updateEvent(info.event); },
                eventResize: function(info) { updateEvent(info.event); },

                // 4. ELIMINAR O VER DETALLES
                eventClick: function(info) {
                    if (info.event.extendedProps.is_progress) {
                        // Es un evento de progreso generado automáticamente (no se borra desde el calendario)
                        alert(`Registro de Progreso:\n\n${info.event.title}\n\nNota: Ve a la Bitácora para editar este registro.`);
                        return;
                    }

                    if (confirm(`¿Quieres eliminar el evento "${info.event.title}"?`)) {
                        let formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', info.event.id);

                        fetch('api_events.php', {
                            method: 'POST',
                            body: formData
                        }).then(() => info.event.remove());
                    }
                }
            });

            calendar.render();

            function updateEvent(event) {
                if (event.extendedProps.is_progress) {
                    alert("No puedes modificar las horas de un registro del pasado.");
                    event.revert();
                    return;
                }

                let formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', event.id);
                formData.append('start', event.start.toISOString());
                if (event.end) formData.append('end', event.end.toISOString());

                fetch('api_events.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        alert('Error al actualizar.');
                        event.revert();
                    }
                });
            }
        });
    </script>
</body>
</html>