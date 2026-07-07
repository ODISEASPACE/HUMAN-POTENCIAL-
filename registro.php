<?php
session_start();
require 'db.php';

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $consent = isset($_POST['consent']) ? true : false;

    if (empty($email) || empty($password) || !$consent) {
        $mensaje = "Todos los campos y el consentimiento son obligatorios.";
    } else {
        // Generar hash seguro de la contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, consent_given, consent_timestamp) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$email, $password_hash, $consent ? 'true' : 'false']);
            
            header("Location: login.php?registrado=1");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { // Código de error de Postgres para Unique Violation
                $mensaje = "Este correo ya está registrado en el ecosistema.";
            } else {
                $mensaje = "Error en la creación del perfil: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro | APH</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FAFAFC; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #1A202C; }
        .auth-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border: 1px solid #E2E8F0; }
        .auth-card h2 { text-align: center; margin-bottom: 30px; color: #805AD5; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #4A5568; }
        .input-group input[type="email"], .input-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .input-group input:focus { outline: none; border-color: #805AD5; box-shadow: 0 0 0 3px rgba(128, 90, 213, 0.1); }
        .checkbox-group { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 25px; font-size: 0.8rem; color: #718096; }
        .btn-primary { width: 100%; background: #805AD5; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { background: #6B46C1; }
        .error { color: #E53E3E; font-size: 0.85rem; margin-bottom: 15px; text-align: center; }
        .links { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .links a { color: #805AD5; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Crear Cuenta APH</h2>
        <?php if($mensaje): ?> <div class="error"><?= $mensaje ?></div> <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="consent" id="consent" required>
                <label for="consent">Autorizo el tratamiento de mis datos personales según la Ley 1581 de 2012 para el análisis vocacional y de productividad.</label>
            </div>
            <button type="submit" class="btn-primary">Registrar mi sistema</button>
        </form>
        <div class="links">
            ¿Ya tienes acceso? <a href="login.php">Inicia sesión</a>
        </div>
    </div>
</body>
</html>