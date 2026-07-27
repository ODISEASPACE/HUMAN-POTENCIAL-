<?php
session_start();
require 'db.php';

$mensaje = '';

if (isset($_GET['registrado'])) {
    $mensaje = "<span style='color: #38A169;'>Registro exitoso. Ya puedes acceder.</span>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Añadimos is_admin a la consulta SELECT
    $stmt = $pdo->prepare("SELECT id, password_hash, is_admin FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login exitoso: Guardamos los datos en la sesión
        $_SESSION['user_id'] = $user['id'];
        // Verificamos si la clave 'is_admin' existe (por si la columna aún no está creada o retorna null)
        $_SESSION['is_admin'] = isset($user['is_admin']) ? (bool)$user['is_admin'] : false;

        // 2. Redirección condicional según el rol
        if ($_SESSION['is_admin'] === true) {
            header("Location: admin_dashboard.php"); // Ruta para administradores
        } else {
            header("Location: dashboard.php"); // Ruta para usuarios normales
        }
        exit;
    } else {
        $mensaje = "<span style='color: #E53E3E;'>Credenciales no válidas.</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso | APH</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FAFAFC; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #1A202C; }
        .auth-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border: 1px solid #E2E8F0; }
        .auth-card h2 { text-align: center; margin-bottom: 30px; color: #805AD5; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #4A5568; }
        .input-group input[type="email"], .input-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .input-group input:focus { outline: none; border-color: #805AD5; box-shadow: 0 0 0 3px rgba(128, 90, 213, 0.1); }
        .btn-primary { width: 100%; background: #805AD5; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-primary:hover { background: #6B46C1; }
        .status-msg { font-size: 0.85rem; margin-bottom: 15px; text-align: center; }
        .links { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .links a { color: #805AD5; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Acceso al Sistema</h2>
        <?php if($mensaje): ?> <div class="status-msg"><?= $mensaje ?></div> <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Iniciar Sesión</button>
        </form>
        <div class="links">
            <a href="/">Volver al inicio</a> | <a href="registro.php">Crear cuenta</a>
        </div>
    </div>
</body>
</html>