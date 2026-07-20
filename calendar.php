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
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-hover: #6B46C1; --border-color: #E2E8F0; --danger: #E53E3E; --danger-light: #FED7D7; }
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
        
        /* Botones Base */
        .btn { border: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 0.9rem; font-family: inherit; }
        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-muted); }
        .btn-outline:hover { background: var(--bg-base); color: var(--text-main); }
        .btn-danger { background: var(--danger-light); color: var(--danger); }
        .btn-danger:hover { background: #FEB2B2; }

        /* Contenedor del Calendario Responsivo */
        .calendar-wrapper { flex: 1; background: var(--bg-panel); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.02); min-height: 0; display: flex; flex-direction: column; }
        #calendar { flex: 1; min-height: 0; } 
        
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border-color); }
        .fc-button-primary { background-color: var(--accent) !important; border-color: var(--accent) !important; text-transform: capitalize; }
        .fc-event { border-radius: 6px; border: none; padding: 2px 4px; font-size: 0.8em; font-weight: 600; cursor: pointer; color: #fff !important; }

        /* Modales (Ventanas Emergentes) */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; opacity: 0; transition: opacity 0.2s ease; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background: #fff; width: 100%; max-width: 450px; border-radius: 16px; padding: 30px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s ease; }
        .modal-overlay.active .modal-content { transform: translateY(0); }
        .close-btn { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; border: none; background: none; color: var(--text-muted); line-height: 1; }
        
        /* Formularios en Modal */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-muted); }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
        .modal-actions { display: flex; gap: 10px; margin-top: 25px; }
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
                    <p>8h sueño, 3 comidas base, 30m actividad física leve.</p>
                    <button class="btn btn-primary" style="width: 100%; background: #38A169;" onclick="generateRoutine('simple')">Aplicar al mes</button>
                </div>
                <div class="routine-card">
                    <h4 style="color: #D69E2E;">Nivel Intermedio</h4>
                    <p>7h sueño, 4h Deep Work, 1h ejercicio, lectura.</p>
                    <button class="btn btn-primary" style="width: 100%; background:#D69E2E;" onclick="generateRoutine('intermedio')">Aplicar al mes</button>
                </div>
                <div class="routine-card">
                    <h4 style="color: #E53E3E;">Nivel Extremo</h4>
                    <p>6h sueño, 8h bloques, entrenamiento intenso, ayuno.</p>
                    <button class="btn btn-primary" style="width: 100%; background:#E53E3E;" onclick="generateRoutine('extremo')">Aplicar al mes</button>
                </div>
            </div>
        </div>

        <div class="calendar-wrapper">
            <div id='calendar'></div>
        </div>
    </main>

    <!-- ========================================== -->
    <!-- SISTEMA DE MODALES (Reemplaza las Alertas) -->
    <!-- ========================================== -->

    <!-- 1. Modal para Crear/Editar Eventos -->
    <div id="eventModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('eventModal')">&times;</button>
            <h2 id="modalTitle" style="margin-bottom: 20px; color: var(--accent);">Nuevo Registro</h2>
            
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
                    <label>Color de Etiqueta</label>
                    <input type="color" id="eventColor" value="#805AD5" style="padding: 0; height: 40px; cursor:pointer;">
                </div>

                <div class="modal-actions">
                    <button type="button" id="btnDelete" class="btn btn-danger" onclick="requestDelete()" style="display: none;">Eliminar</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Modal de Confirmación -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <h2 id="confirmTitle" style="color: var(--text-main); margin-bottom: 10px;">¿Estás seguro?</h2>
            <p id="confirmMessage" style="color: var(--text-muted); margin-bottom: 25px; line-height: 1.5;"></p>
            <div class="modal-actions" style="justify-content: center;">
                <button class="btn btn-outline" onclick="closeModal('confirmModal')">Cancelar</button>
                <button id="confirmActionBtn" class="btn btn-primary">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- 3. Modal de Información (Solo lectura/Avisos) -->
    <div id="infoModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 10px;">💡</div>
            <h2 id="infoTitle" style="color: var(--text-main); margin-bottom: 10px;">Información</h2>
            <p id="infoMessage" style="color: var(--text-muted); margin-bottom: 25px; line-height: 1.5;"></p>
            <button class="btn btn-primary" style="width: 100%;" onclick="closeModal('infoModal')">Entendido</button>
        </div>
    </div>

    <script>
        let calendar;
        let currentFilter = 'all';

        // ==========================================
        // CONTROLADOR DE INTERFAZ Y MODALES
        // ==========================================
        function setFilter(type, btn) {
            currentFilter = type;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const panel = document.getElementById('routinesPanel');
            if (type === 'rutinas') panel.classList.add('active');
            else panel.classList.remove('active');

            calendar.refetchEvents();
        }

        function formatForInput(dateStr) {
            if(!dateStr) return '';
            const d = new Date(dateStr);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            return d.toISOString().slice(0,16);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function showInfo(title, message) {
            document.getElementById('infoTitle').innerText = title;
            document.getElementById('infoMessage').innerText = message;
            document.getElementById('infoModal').classList.add('active');
        }

        function showConfirm(title, message, confirmCallback, buttonColor = 'var(--accent)') {
            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmMessage').innerText = message;
            
            const btn = document.getElementById('confirmActionBtn');
            btn.style.background = buttonColor;
            
            // Limpiamos eventos previos clonando el botón
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.onclick = function() {
                closeModal('confirmModal');
                confirmCallback();
            };
            
            document.getElementById('confirmModal').classList.add('active');
        }

        function openEventModal(isEdit = false, eventData = null) {
            const form = document.getElementById('eventForm');
            const btnDelete = document.getElementById('btnDelete');
            const titleEl = document.getElementById('modalTitle');

            if (isEdit && eventData) {
                titleEl.innerText = "Editar Registro";
                document.getElementById('eventId').value = eventData.id;
                document.getElementById('eventTitle').value = eventData.title;
                document.getElementById('eventStart').value = formatForInput(eventData.start);
                document.getElementById('eventEnd').value = eventData.end ? formatForInput(eventData.end) : '';
                document.getElementById('eventType').value = eventData.extendedProps.event_type || 'agenda';
                document.getElementById('eventColor').value = eventData.backgroundColor || '#805AD5';
                btnDelete.style.display = 'block';
            } else {
                form.reset();
                titleEl.innerText = "Nuevo Registro";
                document.getElementById('eventId').value = '';
                btnDelete.style.display = 'none';
                
                if(eventData) {
                    document.getElementById('eventStart').value = formatForInput(eventData.start);
                    document.getElementById('eventEnd').value = eventData.end ? formatForInput(eventData.end) : '';
                    if(currentFilter === 'rutinas') document.getElementById('eventType').value = 'rutina';
                }
            }
            document.getElementById('eventModal').classList.add('active');
        }

        // ==========================================
        // LÓGICA DE DATOS (CRUD)
        // ==========================================
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
                    closeModal('eventModal');
                } else { 
                    showInfo("Error", "No se pudo guardar: " + data.message); 
                }
            });
        }

        function requestDelete() {
            const id = document.getElementById('eventId').value;
            const title = document.getElementById('eventTitle').value;
            
            showConfirm(
                "Eliminar Registro", 
                `¿Estás seguro de que deseas eliminar permanentemente "${title}"? Esta acción no se puede deshacer.`, 
                function() {
                    let formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    fetch('api_events.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            calendar.refetchEvents();
                            closeModal('eventModal');
                        }
                    });
                }, 
                'var(--danger)' // Pasamos color rojo para indicar peligro
            );
        }

        function generateRoutine(level) {
            const startDate = document.getElementById('routineStartDate').value;
            
            showConfirm(
                "Inyectar Rutina " + level.toUpperCase(),
                `Se aplicará este bloque de rutinas desde el ${startDate} hasta el último día del mes actual.`,
                function() {
                    let formData = new FormData();
                    formData.append('action', 'generate_routines');
                    formData.append('level', level);
                    formData.append('start_date', startDate);

                    fetch('api_events.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            calendar.refetchEvents();
                        } else { 
                            showInfo("Error", "No se pudo inyectar: " + data.message); 
                        }
                    });
                }
            );
        }

        // ==========================================
        // INICIALIZACIÓN DE FULLCALENDAR
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                height: '100%', 
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
                        showInfo("Modo de Lectura", "La Línea de Progreso se genera automáticamente en base a tu Bitácora. No puedes añadir eventos manualmente aquí.");
                        calendar.unselect();
                        return;
                    }
                    openEventModal(false, { start: info.startStr, end: info.endStr });
                    calendar.unselect();
                },

                eventDrop: function(info) { updateDragDrop(info.event); },
                eventResize: function(info) { updateDragDrop(info.event); },

                eventClick: function(info) {
                    if (info.event.extendedProps.is_progress) {
                        showInfo(
                            "Registro de Progreso", 
                            `Título: ${info.event.title}\n\nEste es un registro histórico. Si deseas modificarlo, debes hacerlo desde el módulo de Registro Diario (Bitácora).`
                        );
                        return;
                    }
                    openEventModal(true, info.event);
                }
            });

            calendar.render();

            function updateDragDrop(event) {
                if (event.extendedProps.is_progress) {
                    showInfo("Acción no permitida", "No puedes arrastrar ni modificar el horario de los registros históricos del sistema.");
                    event.revert(); 
                    return;
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
                .then(d => { 
                    if(d.status !== 'success') {
                        showInfo("Error", "Error de sincronización con el servidor.");
                        event.revert(); 
                    }
                });
            }
        });
    </script>
</body>
</html>