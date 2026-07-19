<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'fetch');

try {
    // ---------------------------------------------------------
    // 1. OBTENER EVENTOS (GET api_events.php?action=fetch)
    // ---------------------------------------------------------
    if ($action === 'fetch') {
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $type = $_GET['type'] ?? 'all'; // all, agenda, rutinas, progreso
        
        $final_events = [];

        // A. Cargar eventos del calendario (Agenda o Rutinas)
        if (in_array($type, ['all', 'agenda', 'rutinas'])) {
            $query = "SELECT id, title, start_time AS start, end_time AS end, color AS backgroundColor, is_completed, event_type 
                      FROM calendar_events 
                      WHERE user_id = ?";
            $params = [$user_id];

            if ($start && $end) {
                $query .= " AND start_time >= ? AND start_time <= ?";
                $params[] = $start;
                $params[] = $end;
            }

            if ($type === 'agenda') {
                $query .= " AND (event_type = 'agenda' OR event_type IS NULL)";
            } elseif ($type === 'rutinas') {
                $query .= " AND event_type = 'rutina'";
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $final_events = array_merge($final_events, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // B. Cargar registros de la Bitácora (Progreso)
        if (in_array($type, ['all', 'progreso'])) {
            // Usamos created_at como el momento exacto en el que se guardó el progreso
            $queryProgress = "SELECT id, COALESCE(title, 'Registro Diario') AS title, 
                                     created_at AS start, created_at AS end, 
                                     '#38A169' AS backgroundColor 
                              FROM daily_logs 
                              WHERE user_id = ?";
            $paramsProgress = [$user_id];

            if ($start && $end) {
                $queryProgress .= " AND created_at >= ? AND created_at <= ?";
                $paramsProgress[] = $start;
                $paramsProgress[] = $end;
            }

            $stmtProg = $pdo->prepare($queryProgress);
            $stmtProg->execute($paramsProgress);
            $progress_logs = $stmtProg->fetchAll(PDO::FETCH_ASSOC);

            // Mapeamos los resultados para añadirles el icono y la bandera de "is_progress"
            foreach ($progress_logs as $log) {
                $final_events[] = [
                    'id' => $log['id'],
                    'title' => '📝 ' . $log['title'],
                    'start' => $log['start'],
                    'end' => $log['end'],
                    'backgroundColor' => '#2F855A', // Verde oscuro para diferenciar progreso
                    'borderColor' => '#22543D',
                    'is_progress' => true // Esta bandera la lee FullCalendar para bloquear la edición
                ];
            }
        }

        echo json_encode($final_events);
        exit;
    } 
    
    // ---------------------------------------------------------
    // 2. CREAR EVENTO MANUAL
    // ---------------------------------------------------------
    elseif ($action === 'add') {
        $title = $_POST['title'];
        $start = $_POST['start'];
        $end = !empty($_POST['end']) ? $_POST['end'] : null;
        $event_type = $_POST['event_type'] ?? 'agenda'; 
        
        // Asignar colores según el tipo
        $color = ($event_type === 'rutina') ? '#3182CE' : '#805AD5';

        $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, title, start_time, end_time, color, event_type) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
        $stmt->execute([$user_id, $title, $start, $end, $color, $event_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'id' => $result['id']]);
        exit;
    }
    
    // ---------------------------------------------------------
    // 3. ACTUALIZAR EVENTO (Arrastrar / Redimensionar)
    // ---------------------------------------------------------
    elseif ($action === 'update') {
        $id = $_POST['id'];
        $start = $_POST['start'];
        $end = !empty($_POST['end']) ? $_POST['end'] : null;
        
        $stmt = $pdo->prepare("UPDATE calendar_events SET start_time = ?, end_time = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$start, $end, $id, $user_id]);

        echo json_encode(['status' => 'success']);
        exit;
    }
    
    // ---------------------------------------------------------
    // 4. ELIMINAR EVENTO
    // ---------------------------------------------------------
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    // ---------------------------------------------------------
    // 5. GENERADOR DE RUTINAS AUTOMÁTICAS
    // ---------------------------------------------------------
    elseif ($action === 'generate_routines') {
        $level = $_POST['level'];
        $start_date = $_POST['start_date']; // Fecha de inicio enviada por el calendario
        
        $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, title, start_time, end_time, color, event_type) VALUES (?, ?, ?, ?, ?, 'rutina')");
        
        // Loop para inyectar 7 días de rutinas
        for ($i = 0; $i < 7; $i++) {
            $currentDate = (new DateTime($start_date))->modify("+$i days")->format('Y-m-d');
            $nextDate = (new DateTime($start_date))->modify("+".($i+1)." days")->format('Y-m-d');
            
            $routines = [];

            if ($level === 'simple') {
                $routines = [
                    ['title' => '💤 Sueño (8h)', 'start' => "$currentDate 22:00:00", 'end' => "$nextDate 06:00:00", 'color' => '#4A5568'],
                    ['title' => '🍳 Desayuno', 'start' => "$currentDate 07:00:00", 'end' => "$currentDate 07:30:00", 'color' => '#38A169'],
                    ['title' => '🍲 Almuerzo', 'start' => "$currentDate 13:00:00", 'end' => "$currentDate 14:00:00", 'color' => '#38A169'],
                    ['title' => '🚶‍♂️ Actividad Leve', 'start' => "$currentDate 17:30:00", 'end' => "$currentDate 18:00:00", 'color' => '#3182CE'],
                    ['title' => '🥗 Cena', 'start' => "$currentDate 19:30:00", 'end' => "$currentDate 20:00:00", 'color' => '#38A169'],
                ];
            } 
            elseif ($level === 'intermedio') {
                $routines = [
                    ['title' => '💤 Sueño (7h)', 'start' => "$currentDate 23:00:00", 'end' => "$nextDate 06:00:00", 'color' => '#4A5568'],
                    ['title' => '⚡ Deep Work', 'start' => "$currentDate 08:00:00", 'end' => "$currentDate 12:00:00", 'color' => '#D69E2E'],
                    ['title' => '🍲 Almuerzo & Descanso', 'start' => "$currentDate 12:00:00", 'end' => "$currentDate 13:30:00", 'color' => '#38A169'],
                    ['title' => '🏋️‍♂️ Ejercicio', 'start' => "$currentDate 18:00:00", 'end' => "$currentDate 19:00:00", 'color' => '#3182CE'],
                    ['title' => '📖 Lectura', 'start' => "$currentDate 20:00:00", 'end' => "$currentDate 21:00:00", 'color' => '#805AD5'],
                ];
            } 
            elseif ($level === 'extremo') {
                $routines = [
                    ['title' => '💤 Sueño (6h)', 'start' => "$currentDate 23:00:00", 'end' => "$nextDate 05:00:00", 'color' => '#4A5568'],
                    ['title' => '⚡ Deep Work I', 'start' => "$currentDate 06:00:00", 'end' => "$currentDate 10:00:00", 'color' => '#E53E3E'],
                    ['title' => '🥩 Primera Comida (Ayuno Roto)', 'start' => "$currentDate 12:00:00", 'end' => "$currentDate 13:00:00", 'color' => '#38A169'],
                    ['title' => '⚡ Deep Work II', 'start' => "$currentDate 14:00:00", 'end' => "$currentDate 18:00:00", 'color' => '#E53E3E'],
                    ['title' => '🦍 Entrenamiento Intenso', 'start' => "$currentDate 18:30:00", 'end' => "$currentDate 20:00:00", 'color' => '#3182CE'],
                    ['title' => '🥗 Última Comida', 'start' => "$currentDate 20:30:00", 'end' => "$currentDate 21:00:00", 'color' => '#38A169'],
                ];
            }

            // Insertamos cada rutina en la BD
            foreach ($routines as $r) {
                $stmt->execute([$user_id, $r['title'], $r['start'], $r['end'], $r['color']]);
            }
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>