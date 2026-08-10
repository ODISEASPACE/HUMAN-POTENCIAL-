<?php
// Parámetros extraídos estrictamente de la consola de AWS
$host = 'aph-database.cy78m0oi65y5.us-east-1.rds.amazonaws.com'; 
$dbname = 'prod2'; // Ajustado al nombre real evidenciado en AWS
$user = 'postgres';
$password = 'Limitless20xx'; // Se asume correcta; AWS no expone esto en texto plano.

$dsn = "pgsql:host=$host;port=5432;dbname=$dbname;";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión a la infraestructura: " . $e->getMessage());
}
?>