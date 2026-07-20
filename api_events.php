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
    // 1. FETCH (EXTRACCIÓN DE DATOS)
    // ---------------------------------------------------------
    if ($action === 'fetch') {
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $type = $_GET['type'] ?? 'all'; 
        
        $final_events = [];

        if (in_array($type, ['all', 'agenda', 'rutinas'])) {
            // CAMBIO CLAVE: Pedimos 'color' en lugar de 'color AS backgroundColor'
            $query = "SELECT id, title, start_time AS start, end_time AS end, color, is_completed, event_type 
                      FROM calendar_events WHERE user_id = ?";
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
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Garantizamos que todos tengan color, si en la BD es NULL, asignamos el morado por defecto
            foreach ($events as &$evt) {
                if (empty($evt['color'])) {
                    $evt['color'] = '#805AD5';
                }
            }
            $final_events = array_merge($final_events, $events);
        }

        if (in_array($type, ['all', 'progreso'])) {
            $queryProgress = "SELECT id, COALESCE(title, 'Registro Diario') AS title, created_at AS start, created_at AS end 
                              FROM daily_logs WHERE user_id = ?";
            $paramsProgress = [$user_id];

            if ($start && $end) {
                $queryProgress .= " AND created_at >= ? AND created_at <= ?";
                $paramsProgress[] = $start;
                $paramsProgress[] = $end;
            }

            $stmtProg = $pdo->prepare($queryProgress);
            $stmtProg->execute($paramsProgress);
            $progress_logs = $stmtProg->fetchAll(PDO::FETCH_ASSOC);

            foreach ($progress_logs as $log) {
                $final_events[] = [
                    'id' => $log['id'],
                    'title' => '📝 ' . $log['title'],
                    'start' => $log['start'],
                    'end' => $log['end'],
                    'color' => '#2F855A', // Verde oscuro para el progreso
                    'is_progress' => true 
                ];
            }
        }

        echo json_encode($final_events);
        exit;
    } 
    
    // ---------------------------------------------------------
    // 2. AÑADIR
    // ---------------------------------------------------------
    elseif ($action === 'add') {
        $title = $_POST['title'];
        $start = $_POST['start'];
        $end = !empty($_POST['end']) ? $_POST['end'] : null;
        $event_type = $_POST['event_type'] ?? 'agenda'; 
        $color = !empty($_POST['color']) ? $_POST['color'] : '#805AD5'; // Validación estricta del color

        $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, title, start_time, end_time, color, event_type) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
        $stmt->execute([$user_id, $title, $start, $end, $color, $event_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'id' => $result['id']]);
        exit;
    }
    
    // ---------------------------------------------------------
    // 3. ACTUALIZAR
    // ---------------------------------------------------------
    elseif ($action === 'update') {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $start = $_POST['start'];
        $end = !empty($_POST['end']) ? $_POST['end'] : null;
        $color = !empty($_POST['color']) ? $_POST['color'] : '#805AD5';
        $event_type = $_POST['event_type'] ?? 'agenda'; 
        
        $stmt = $pdo->prepare("UPDATE calendar_events SET title = ?, start_time = ?, end_time = ?, color = ?, event_type = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $start, $end, $color, $event_type, $id, $user_id]);

        echo json_encode(['status' => 'success']);
        exit;
    }
    
    // ---------------------------------------------------------
    // 4. ELIMINAR
    // ---------------------------------------------------------
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    // ---------------------------------------------------------
    // 5. GENERAR RUTINAS MASIVAS
    // ---------------------------------------------------------
    elseif ($action === 'generate_routines') {
        $level = $_POST['level'];
        $start_date = $_POST['start_date']; 
        
        $start_dt = new DateTime($start_date);
        $end_dt = new DateTime($start_date);
        $end_dt->modify('last day of this month');
        
        $interval = $start_dt->diff($end_dt);
        $days_to_inject = $interval->days + 1; 
        
        $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, title, start_time, end_time, color, event_type) VALUES (?, ?, ?, ?, ?, 'rutina')");
        
        for ($i = 0; $i < $days_to_inject; $i++) {
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
                    ['title' => '🥩 Primera Comida', 'start' => "$currentDate 12:00:00", 'end' => "$currentDate 13:00:00", 'color' => '#38A169'],
                    ['title' => '⚡ Deep Work II', 'start' => "$currentDate 14:00:00", 'end' => "$currentDate 18:00:00", 'color' => '#E53E3E'],
                    ['title' => '🦍 Entrenamiento Intenso', 'start' => "$currentDate 18:30:00", 'end' => "$currentDate 20:00:00", 'color' => '#3182CE'],
                    ['title' => '🥗 Última Comida', 'start' => "$currentDate 20:30:00", 'end' => "$currentDate 21:00:00", 'color' => '#38A169'],
                ];
            }

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