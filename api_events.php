<?php
session_start();
require 'db.php'; // Tu conexión PDO a PostgreSQL

// Asegurarnos de devolver siempre JSON
header('Content-Type: application/json');

// Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Determinar qué acción estamos realizando. 
// FullCalendar hace un GET por defecto para buscar eventos.
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'fetch');

try {
    if ($action === 'fetch') {
        // FullCalendar envía automáticamente los parámetros 'start' y 'end' en la URL
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;

        // Adaptamos los nombres de las columnas para que FullCalendar los entienda de forma nativa
        $query = "SELECT 
                    id, 
                    title, 
                    start_time AS start, 
                    end_time AS end, 
                    color AS backgroundColor,
                    is_completed 
                  FROM calendar_events 
                  WHERE user_id = ?";
        
        $params = [$user_id];

        // Si FullCalendar provee un rango de fechas, optimizamos la búsqueda
        if ($start && $end) {
            $query .= " AND start_time >= ? AND start_time <= ?";
            $params[] = $start;
            $params[] = $end;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Devolvemos el array de eventos a FullCalendar
        echo json_encode($events);
        exit;
    } 
    
    elseif ($action === 'add') {
        $title = $_POST['title'];
        $start = $_POST['start'];
        $end = !empty($_POST['end']) ? $_POST['end'] : null;
        $color = $_POST['color'] ?? '#805AD5'; // APH OS accent color

        $stmt = $pdo->prepare("
            INSERT INTO calendar_events (user_id, title, start_time, end_time, color) 
            VALUES (?, ?, ?, ?, ?) 
            RETURNING id
        ");
        $stmt->execute([$user_id, $title, $start, $end, $color]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'id' => $result['id']]);
        exit;
    }
    
    elseif ($action === 'update') {
        // Se ejecuta cuando el usuario arrastra un evento a otra hora/día o cambia su duración
        $id = $_POST['id'];
        $start = $_POST['start'];
        $end = !empty($_POST['end']) ? $_POST['end'] : null;
        
        $stmt = $pdo->prepare("
            UPDATE calendar_events 
            SET start_time = ?, end_time = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$start, $end, $id, $user_id]);

        echo json_encode(['status' => 'success']);
        exit;
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>