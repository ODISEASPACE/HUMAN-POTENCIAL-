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
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; margin: 0; overflow: hidden;}
        
        main { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; flex-shrink: 0; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }

        /* Controles de Filtro */
        .calendar-controls { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; flex-shrink: 0; flex-wrap: wrap;}
        .filter-btn { background: transparent; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.3s; }
        .filter-btn:hover { background: rgba(0,0,0,0.03); color: var(--text-main); }
        .filter-btn.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }

        /* Panel de Rutinas */
        .routines-panel { display: none; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); flex-shrink: 0; }
        .routines-panel.active { display: block; }
        .routines-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .routines-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .routine-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; text-align: center; transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between;}
        .routine-card:hover { border-color: var(--accent); transform: translateY(-2px); }
        .routine-card h4 { margin-bottom: 5px; font-size: 1rem; }
        .routine-card p { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px; flex-grow: 1; }
        .btn-generate { background: var(--accent); color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.85rem;}
        .btn-generate:hover { background: var(--accent-hover); }

        /* Contenedor del Calendario Responsivo */
        .calendar-wrapper { flex: 1; background: var(--bg-panel); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.02); min-height: 0; display: flex; flex-direction: column; }
        #calendar { flex: 1; min-height: 0; } 
        
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border-color); }
        .fc-button-primary { background-color: var(--accent) !important; border-color: var(--accent) !important; text-transform: capitalize; }
        .fc-event { border-radius: 6px; border: none; padding: 2px 4px; font-size: 0.8em; font-weight: 600; cursor: pointer; color: #fff !important; }

        /* Modal Personalizado */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #fff; width: 100%; max-width: 450px; border-radius: 16px; padding: 30px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .close-btn { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; border: none; background: none; color: var(--text-muted); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-muted); }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-save { flex: 1; background: var(--accent); color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-delete { background: #FED7D7; color: #C53030; border: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-dash">
            <div>
                <h1>Gestión de Tiempo</h1>
                <p>Visualiza tus eventos, inyecta rutinas y monitorea tu progreso humano.</p>
            </div>
        </div>

        <div class="calendar-controls">
            <button class="filter-btn active" onclick="setFilter('all', this)">🌐 Todo</button>
            <button class="filter-btn" onclick="setFilter('agenda', this)">📅 Agenda / Tareas</button>
            <button class="filter-btn" onclick="setFilter('rutinas', this)">🔁 Hábitos y Rutinas</button>
            <button class="filter-btn" onclick="setFilter('progreso', this)">📈 Línea de Progreso</button>
        </div>

        <div class="routines-panel" id="routinesPanel">
            <div class="routines-header">
                <h3>Inyección de Rutinas Base</h3>
                <div>
                    <label style="font-size:0.85rem; font-weight:bold; color:var(--text-muted); margin-right: 10px;">Aplicar a partir del día:</label>
                    <input type="date" id="routineStartDate" style="padding: 5px 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family:inherit;" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="routines-grid">
                <div class="routine-card">
                    <h4 style="color: #38A169;">Nivel Simple</h4>
                    <p>8h sueño, 3 comidas base, 30m actividad física leve. Ideal para mantenimiento.</p>
                    <button class="btn-generate" onclick="generateRoutine('simple')">Aplicar hasta fin de mes</button>
                </div>
                <div class="routine-card">
                    <h4 style="color: #D69E2E;">Nivel Intermedio</h4>
                    <p>7h sueño, 4h Deep Work, 1h ejercicio, lectura. Enfoque y disciplina.</p>
                    <button class="btn-generate" style="background:#D69E2E;" onclick="generateRoutine('intermedio')">Aplicar hasta fin de mes</button>
                </div>
                <div class="routine-card">
                    <h4 style="color: #E53E3E;">Nivel Extremo</h4>
                    <p>6h sueño, 8h bloques de trabajo, entrenamiento intenso, ayuno pautado.</p>
                    <button class="btn-generate" style="background:#E53E3E;" onclick="generateRoutine('extremo')">Aplicar hasta fin de mes</button>
                </div>
            </div>
        </div>

        <!-- Contenedor adaptativo -->
        <div class="calendar-wrapper">
            <div id='calendar'></div>
        </div>
    </main>

    <!-- Modal para Eventos -->
    <div id="eventModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <h2 id="modalTitle" style="margin-bottom: 20px; color: var(--accent);">Nuevo Evento</h2>
            
            <form id="eventForm" onsubmit="saveEvent(event)">
                <input type="hidden" id="eventId">
                
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" id="eventTitle" required placeholder="Ej. Sesión de Estudio, Gimnasio...">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Inicio</label>
                        <input type="datetime-local" id="eventStart" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Fin (Opcional)</label>
                        <input type="datetime-local" id="eventEnd">
                    </div>
                </div>

                <div class="form-group">
                    <label>Tipo de Registro</label>
                    <select id="eventType">
                        <option value="agenda">📅 Evento / Tarea</option>
                        <option value="rutina">🔁 Rutina / Hábito</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Color</label>
                    <input type="color" id="eventColor" value="#805AD5" style="padding: 0; height: 40px; cursor:pointer;">
                </div>

                <div class="modal-actions">
                    <button type="button" id="btnDelete" class="btn-delete" onclick="deleteEvent()">Eliminar</button>
                    <button type="submit" class="btn-save">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let calendar;
        let currentFilter = 'all';

        function setFilter(type, btn) {
            currentFilter = type;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const panel = document.getElementById('routinesPanel');
            if (type === 'rutinas') panel.classList.add('active');
            else panel.classList.remove('active');

            calendar.refetchEvents();
        }

        // Formatear fechas para el input datetime-local
        function formatForInput(dateStr) {
            if(!dateStr) return '';
            const d = new Date(dateStr);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            return d.toISOString().slice(0,16);
        }

        // Manejo del Modal
        function openModal(isEdit = false, eventData = null) {
            document.getElementById('eventModal').classList.add('active');
            const form = document.getElementById('eventForm');
            const btnDelete = document.getElementById('btnDelete');
            const titleEl = document.getElementById('modalTitle');

            if (isEdit && eventData) {
                titleEl.innerText = "Editar Evento";
                document.getElementById('eventId').value = eventData.id;
                document.getElementById('eventTitle').value = eventData.title;
                document.getElementById('eventStart').value = formatForInput(eventData.start);
                document.getElementById('eventEnd').value = eventData.end ? formatForInput(eventData.end) : '';
                document.getElementById('eventType').value = eventData.extendedProps.event_type || 'agenda';
                document.getElementById('eventColor').value = eventData.backgroundColor || '#805AD5';
                btnDelete.style.display = 'block';
            } else {
                form.reset();
                titleEl.innerText = "Nuevo Evento";
                document.getElementById('eventId').value = '';
                btnDelete.style.display = 'none';
                
                if(eventData) {
                    document.getElementById('eventStart').value = formatForInput(eventData.start);
                    document.getElementById('eventEnd').value = eventData.end ? formatForInput(eventData.end) : '';
                    if(currentFilter === 'rutinas') document.getElementById('eventType').value = 'rutina';
                }
            }
        }

        function closeModal() {
            document.getElementById('eventModal').classList.remove('active');
        }

        // Guardar desde el Modal
        function saveEvent(e) {
            e.preventDefault();
            const id = document.getElementById('eventId').value;
            const action = id ? 'update' : 'add';
            
            let formData = new FormData();
            formData.append('action', action);
            if(id) formData.append('id', id);
            formData.append('title', document.getElementById('eventTitle').value);
            formData.append('start', document.getElementById('eventStart').value);
            formData.append('end', document.getElementById('eventEnd').value);
            formData.append('event_type', document.getElementById('eventType').value);
            formData.append('color', document.getElementById('eventColor').value);

            fetch('api_events.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    calendar.refetchEvents();
                    closeModal();
                } else { alert('Error al guardar: ' + data.message); }
            });
        }

        // Eliminar desde el Modal
        function deleteEvent() {
            const id = document.getElementById('eventId').value;
            if(confirm("¿Eliminar este registro permanentemente?")) {
                let formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('api_events.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        calendar.refetchEvents();
                        closeModal();
                    }
                });
            }
        }

        // Inyección de Rutinas (Mes Completo)
        function generateRoutine(level) {
            const startDate = document.getElementById('routineStartDate').value;
            if (confirm(`¿Inyectar rutina "${level}" desde el ${startDate} hasta el último día del mes?`)) {
                let formData = new FormData();
                formData.append('action', 'generate_routines');
                formData.append('level', level);
                formData.append('start_date', startDate);

                fetch('api_events.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        calendar.refetchEvents();
                    } else { alert('Error: ' + data.message); }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                height: '100%', // Hace que el calendario ocupe exactamente el espacio disponible
                initialView: 'timeGridWeek', 
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                slotMinTime: '04:00:00',
                slotMaxTime: '24:00:00',
                editable: true, 
                selectable: true, 
                nowIndicator: true,
                
                events: function(info, successCallback, failureCallback) {
                    fetch(`api_events.php?action=fetch&type=${currentFilter}&start=${info.startStr}&end=${info.endStr}`)
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => failureCallback(error));
                },
                
                select: function(info) {
                    if(currentFilter === 'progreso') {
                        alert("Los registros de progreso son automáticos.");
                        calendar.unselect();
                        return;
                    }
                    openModal(false, { start: info.startStr, end: info.endStr });
                    calendar.unselect();
                },

                eventDrop: function(info) { updateDragDrop(info.event); },
                eventResize: function(info) { updateDragDrop(info.event); },

                eventClick: function(info) {
                    if (info.event.extendedProps.is_progress) {
                        alert(`Registro de Progreso:\n\n${info.event.title}\n\nNota: Ve a la Bitácora para editar esto.`);
                        return;
                    }
                    openModal(true, info.event);
                }
            });

            calendar.render();

            function updateDragDrop(event) {
                if (event.extendedProps.is_progress) {
                    alert("No puedes mover el historial.");
                    event.revert(); return;
                }
                let formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', event.id);
                formData.append('title', event.title);
                formData.append('start', event.start.toISOString());
                if (event.end) formData.append('end', event.end.toISOString());
                formData.append('color', event.backgroundColor);
                formData.append('event_type', event.extendedProps.event_type);

                fetch('api_events.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { if(d.status !== 'success') event.revert(); });
            }
        });
    </script>
</body>
</html>