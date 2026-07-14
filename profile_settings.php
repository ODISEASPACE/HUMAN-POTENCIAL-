<?php
session_start();
require 'db.php';

// Verificación estricta de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $profession = trim($_POST['profession']);
    $profile_picture = trim($_POST['profile_picture']); // Puede ser emoji o URL según tu lógica de renderAvatar

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, profession = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$username, $profession, $profile_picture, $user_id]);
        $mensaje = "<span style='color: #38A169;'>Perfil actualizado correctamente.</span>";
    } catch (PDOException $e) {
        $mensaje = "<span style='color: #E53E3E;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
}

// Obtener los datos actuales del usuario
$stmt = $pdo->prepare("SELECT email, username, profession, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Perfil | APH</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FAFAFC; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #1A202C; }
        .settings-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; border: 1px solid #E2E8F0; }
        .settings-card h2 { text-align: center; margin-bottom: 30px; color: #805AD5; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #4A5568; }
        .input-group input[type="text"], .input-group input[type="email"] { width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .input-group input:focus { outline: none; border-color: #805AD5; box-shadow: 0 0 0 3px rgba(128, 90, 213, 0.1); }
        .input-group input:disabled { background: #EDF2F7; color: #A0AEC0; cursor: not-allowed; }
        .btn-primary { width: 100%; background: #805AD5; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-primary:hover { background: #6B46C1; }
        .status-msg { font-size: 0.9rem; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .links { text-align: center; margin-top: 25px; font-size: 0.9rem; }
        .links a { color: #805AD5; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .links a:hover { color: #6B46C1; }
    </style>
</head>
<body>
    <div class="settings-card">
        <h2>Configuración de Perfil</h2>
        
        <?php if($mensaje): ?> 
            <div class="status-msg"><?= $mensaje ?></div> 
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>Correo Electrónico (Solo lectura)</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="input-group">
                <label>Nombre de Usuario</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
            </div>
            <div class="input-group">
                <label>Profesión</label>
                <input type="text" name="profession" value="<?= htmlspecialchars($user['profession'] ?? '') ?>" placeholder="Ej: Desarrollador Backend">
            </div>
            <div class="input-group">
                <label>Avatar (Emoji o URL de imagen)</label>
                <input type="text" name="profile_picture" value="<?= htmlspecialchars($user['profile_picture'] ?? '') ?>" placeholder="Ej: 👨‍💻 o https://...">
            </div>
            
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </form>
        
        <div class="links">
            <a href="dashboard.php">← Volver al Panel Central</a>
        </div>
    </div>
</body>
</html>