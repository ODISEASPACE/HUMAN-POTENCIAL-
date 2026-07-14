<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $profession = trim($_POST['profession']);
    $profile_picture = trim($_POST['profile_picture']);
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $bio = trim($_POST['bio']);
    $archetype = isset($_POST['archetype']) ? (int)$_POST['archetype'] : 1;
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, profession = ?, profile_picture = ?, birth_date = ?, bio = ?, archetype = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$username, $profession, $profile_picture, $birth_date, $bio, $archetype, $password_hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, profession = ?, profile_picture = ?, birth_date = ?, bio = ?, archetype = ? WHERE id = ?");
            $stmt->execute([$username, $profession, $profile_picture, $birth_date, $bio, $archetype, $user_id]);
        }
        $mensaje = "<span style='color: #38A169;'>Perfil actualizado correctamente.</span>";
    } catch (PDOException $e) {
        $mensaje = "<span style='color: #E53E3E;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
}

// Obtener los datos actuales del usuario, incluyendo el arquetipo
try {
    $stmt = $pdo->prepare("SELECT email, username, profession, profile_picture, birth_date, bio, COALESCE(archetype, 1) as archetype FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error de base de datos: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Perfil | APH</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FAFAFC; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #1A202C; padding: 20px; box-sizing: border-box; }
        .settings-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 650px; border: 1px solid #E2E8F0; }
        .settings-card h2 { text-align: center; margin-bottom: 30px; color: #805AD5; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #4A5568; }
        .input-group input, .input-group textarea, .input-group select { width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; box-sizing: border-box; font-size: 0.95rem; background: #fff; }
        .input-group input:focus, .input-group textarea:focus, .input-group select:focus { outline: none; border-color: #805AD5; box-shadow: 0 0 0 3px rgba(128, 90, 213, 0.1); }
        .input-group input:disabled { background: #EDF2F7; color: #A0AEC0; cursor: not-allowed; }
        .input-group textarea { resize: vertical; min-height: 100px; }
        
        .avatar-input { border: 1px dashed #805AD5 !important; background: rgba(128, 90, 213, 0.02); color: #805AD5; text-align: center; font-weight: 600; }
        .avatar-input::placeholder { color: #B794F4; }

        .btn-primary { width: 100%; background: #805AD5; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 20px; font-size: 1rem; }
        .btn-primary:hover { background: #6B46C1; }
        .status-msg { font-size: 0.95rem; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .links { text-align: center; margin-top: 25px; font-size: 0.9rem; }
        .links a { color: #805AD5; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .links a:hover { color: #6B46C1; }
        
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <div class="settings-card">
        <h2>Configuración de Perfil</h2>
        
        <?php if($mensaje): ?> 
            <div class="status-msg"><?= $mensaje ?></div> 
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-grid">
                <div class="input-group">
                    <label>Usuario (Alias) *</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>
                
                <div class="input-group">
                    <label>Foto de Perfil</label>
                    <input type="text" name="profile_picture" class="avatar-input" value="<?= htmlspecialchars($user['profile_picture'] ?? '') ?>" placeholder="👤 Emoji o URL de imagen">
                </div>

                <div class="input-group">
                    <label>Correo Electrónico *</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled title="El correo no se puede cambiar">
                </div>

                <div class="input-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="password" placeholder="Dejar en blanco para no cambiar">
                </div>

                <div class="input-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>">
                </div>

                <div class="input-group">
                    <label>Profesión / Estudio</label>
                    <input type="text" name="profession" value="<?= htmlspecialchars($user['profession'] ?? '') ?>" placeholder="Ej. Ing. Sistemas">
                </div>
                
                <div class="input-group full-width">
                    <label>Arquetipo (Desbloquea módulos del sistema)</label>
                    <select name="archetype" required>
                        <option value="1" <?= ($user['archetype'] == 1) ? 'selected' : '' ?>>Vagabundo (Nivel 1 - Básico)</option>
                        <option value="2" <?= ($user['archetype'] == 2) ? 'selected' : '' ?>>Soñador (Nivel 2 - Estado Humano)</option>
                        <option value="3" <?= ($user['archetype'] == 3) ? 'selected' : '' ?>>Soldado (Nivel 3 - Proyectos)</option>
                        <option value="4" <?= ($user['archetype'] == 4) ? 'selected' : '' ?>>Ejecutor (Nivel 4 - Árboles)</option>
                    </select>
                </div>

                <div class="input-group full-width">
                    <label>Biografía / Descripción</label>
                    <textarea name="bio" placeholder="Describe brevemente tus objetivos..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </form>
        
        <div class="links">
            <a href="dashboard.php">← Volver al Panel Central</a>
        </div>
    </div>
</body>
</html>