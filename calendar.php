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
    <title>Calendario | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Librerías de FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --accent: #805AD5; --border-color: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; margin: 0; }
        
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        
        /* Contenedor del Calendario */
        #calendar-container {
            background: var(--bg-panel);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            flex: 1;
            margin-top: 20px;
        }

        /* Personalización de FullCalendar para que coincida con APH OS */
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border-color); }
        .fc-button-primary { background-color: var(--accent) !important; border-color: var(--accent) !important; }
        .fc-event { border-radius: 6px; border: none; padding: 2px 4px; font-size: 0.85em; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

    <!-- Sidebar Modular -->
    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-dash">
            <h1>Agenda y Rutinas</h1>
            <p>Planifica tu tiempo, gestiona eventos y haz seguimiento a tus bloques de trabajo.</p>
        </div>

        <div id="calendar-container">
            <div id='calendar'></div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek', // Vista semanal con horas por defecto
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                slotMinTime: '06:00:00', // Empieza a las 6 AM
                slotMaxTime: '23:00:00', // Termina a las 11 PM
                editable: true, // Permite arrastrar eventos
                selectable: true, // Permite hacer clic y arrastrar para crear
                nowIndicator: true,
                events: 'api_events.php', // (Pendiente) API que devolverá los eventos en JSON
                
                select: function(info) {
                    let title = prompt('Título del nuevo evento/rutina:');
                    if (title) {
                        // Aquí iría tu llamada AJAX a PHP para guardar en DB
                        calendar.addEvent({
                            title: title,
                            start: info.startStr,
                            end: info.endStr,
                            allDay: info.allDay,
                            backgroundColor: '#805AD5' // Accent color
                        });
                        alert('Implementar guardado en BD por AJAX');
                    }
                    calendar.unselect();
                },
                eventClick: function(info) {
                    if (confirm("¿Marcar tarea como completada o eliminarla? (Aceptar para eliminar)")) {
                        info.event.remove();
                        // Aquí iría tu llamada AJAX para borrar de la BD
                    }
                }
            });

            calendar.render();
        });
    </script>
</body>
</html>