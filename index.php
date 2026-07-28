<?php
session_start();
require 'db.php'; // Asegúrate de que tenga el endpoint de AWS RDS

$error_msg = '';
$success_msg = '';
$active_modal = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    
    // LÓGICA DE LOGIN
    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // 1. Agregamos is_admin a la consulta SQL
        $stmt = $pdo->prepare("SELECT id, password_hash, is_admin FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Guardamos ID y Rol en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = isset($user['is_admin']) ? (bool)$user['is_admin'] : false;

            // 2. Redirección inteligente según el rol
            if ($_SESSION['is_admin'] === true) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error_msg = "Credenciales no válidas.";
            $active_modal = 'login';
        }
    }

    // LÓGICA DE REGISTRO
    if ($action === 'register') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $birth_date = $_POST['birth_date'] ?: null; // Null si está vacío
        $profession = trim($_POST['profession']);
        $bio = trim($_POST['bio']);
        $consent = isset($_POST['consent']) ? true : false;
        
        $profile_pic_path = null;

        // 1. Verificar si seleccionó un avatar predefinido
        if (!empty($_POST['selected_avatar'])) {
            $profile_pic_path = $_POST['selected_avatar'];
        }

        // 2. Verificar si subió un archivo (esto sobrescribe el avatar si hay ambos)
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('profile_') . '.' . $ext;
            $destination = 'uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $profile_pic_path = $destination;
            }
        }

        if (empty($email) || empty($password) || empty($username) || !$consent) {
            $error_msg = "Los campos con (*) y el consentimiento son obligatorios.";
            $active_modal = 'register';
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, consent_given, consent_timestamp, username, profile_picture, bio, birth_date, profession) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $email, 
                    $password_hash, 
                    $consent ? 'true' : 'false',
                    $username,
                    $profile_pic_path,
                    $bio,
                    $birth_date,
                    $profession
                ]);
                
                $success_msg = "Registro exitoso. Ya puedes iniciar sesión.";
                $active_modal = 'login';
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') { 
                    $error_msg = "El correo o nombre de usuario ya están registrados.";
                } else {
                    $error_msg = "Error: " . $e->getMessage();
                }
                $active_modal = 'register';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APH | Anthropotechnology</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #FAFAFC;
            --text-main: #1A202C;
            --text-muted: #4A5568;
            --accent: #805AD5; 
            --accent-hover: #6B46C1;
            --border-color: #E2E8F0;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --card-bg: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); line-height: 1.6; overflow-x: hidden; }

        /* --- HEADER Y HERO --- */
        header { position: fixed; top: 0; width: 100%; background: var(--nav-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; z-index: 50; }
        .brand { display: flex; align-items: baseline; gap: 10px; text-decoration: none; }
        .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--text-main); }
        .brand span { font-size: 0.75rem; color: var(--accent); letter-spacing: 1px; text-transform: uppercase; font-weight: 700; }
        .nav-actions { display: flex; gap: 20px; align-items: center; }
        
        .btn-ghost { text-decoration: none; color: var(--text-muted); font-weight: 600; cursor: pointer; background: none; border: none; font-size: 0.95rem; font-family: inherit; }
        .btn-ghost:hover { color: var(--text-main); }
        .btn-primary { background: var(--accent); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; font-family: inherit; }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); }
        .btn-secondary { background: #fff; color: var(--text-main); border: 1px solid var(--border-color); padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 0.95rem; font-family: inherit; }
        .btn-secondary:hover { border-color: var(--text-muted); }

        .hero { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 180px 5% 100px; background: radial-gradient(circle at top, #ffffff 0%, #FAFAFC 100%); min-height: 100vh; }
        .hero h1 { font-size: 4rem; font-weight: 800; line-height: 1.1; max-width: 850px; margin-bottom: 24px; letter-spacing: -1.5px; }
        .hero h1 span { color: var(--accent); }
        .hero p.subtitle { font-size: 1.25rem; color: var(--text-muted); max-width: 600px; margin-bottom: 45px; }

        /* --- MODALES GENERALES --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
            display: none; justify-content: center; align-items: center; z-index: 100;
            opacity: 0; transition: opacity 0.3s ease; padding: 20px;
        }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content {
            background: #fff; width: 100%; max-width: 450px; border-radius: 16px;
            padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative; transform: translateY(20px); transition: transform 0.3s ease;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-overlay.active .modal-content { transform: translateY(0); }
        .close-btn { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); border: none; background: none; }
        .modal-content h2 { color: var(--accent); text-align: center; margin-bottom: 25px; }
        
        /* --- FORMULARIOS --- */
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .input-group input, .input-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
        .input-group input:focus, .input-group textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(128, 90, 213, 0.1); }
        .input-group textarea { resize: vertical; min-height: 80px; }
        
        .checkbox-group { display: flex; gap: 10px; margin-bottom: 20px; font-size: 0.8rem; color: var(--text-muted); align-items: flex-start; }
        .btn-full { width: 100%; padding: 12px; margin-top: 10px; }
        
        .toggle-text { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .toggle-text a { color: var(--accent); cursor: pointer; font-weight: 600; text-decoration: none; }
        
        .msg-error { background: #FED7D7; color: #C53030; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; text-align: center; }
        .msg-success { background: #C6F6D5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; text-align: center; }

        /* --- SECCIÓN AVATAR PERSONALIZADA --- */
        .avatar-selector-btn {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 12px;
            border: 1px dashed var(--accent); border-radius: 8px; background: rgba(128, 90, 213, 0.05);
            cursor: pointer; transition: 0.3s;
        }
        .avatar-selector-btn:hover { background: rgba(128, 90, 213, 0.1); }
        .avatar-preview-circle {
            width: 36px; height: 36px; border-radius: 50%; background-color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            border: 1px solid var(--border-color); background-size: cover; background-position: center;
        }
        .avatar-text { font-size: 0.9rem; font-weight: 600; color: var(--accent); }

        .avatar-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .avatar-option {
            font-size: 2rem; cursor: pointer; padding: 10px; border-radius: 12px;
            background: var(--bg-base); transition: 0.2s; border: 2px solid transparent; text-align: center;
        }
        .avatar-option:hover { transform: scale(1.1); border-color: var(--accent); background: rgba(128, 90, 213, 0.1); }
        
        /* Asegurar que el modal del avatar esté por encima del de registro */
        #avatarModal { z-index: 150; }
    </style>
</head>
<body>

    <header>
        <a href="/" class="brand"><h2>A P H</h2><span>Core System V3.1</span></a>
        <div class="nav-actions">
            <button onclick="openModal('loginModal')" class="btn-ghost">Iniciar Sesión</button>
            <button onclick="openModal('registerModal')" class="btn-primary">Registrarse</button>
        </div>
    </header>

    <div class="hero">
        <div style="background: rgba(128, 90, 213, 0.1); color: var(--accent); padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; margin-bottom: 24px;">Infraestructura Central</div>
        <h1>El entorno para tu <span>expansión cognitiva.</span></h1>
        <p class="subtitle">Descubre un ecosistema minimalista diseñado para maximizar tu enfoque, estructurar tus herramientas y tomar el control de tus hábitos de vida.</p>
        <div class="hero-actions">
            <button onclick="openModal('registerModal')" class="btn-primary" style="padding: 14px 32px; font-size: 1.05rem;">Comenzar ahora</button>
        </div>
    </div>

    <div id="loginModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" type="button" onclick="closeModal('loginModal')">&times;</button>
            <h2>Acceso al Sistema</h2>
            
            <?php if($error_msg && $active_modal == 'login'): ?> <div class="msg-error"><?= $error_msg ?></div> <?php endif; ?>
            <?php if($success_msg && $active_modal == 'login'): ?> <div class="msg-success"><?= $success_msg ?></div> <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="input-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary btn-full">Iniciar Sesión</button>
            </form>
            <div class="toggle-text">
                ¿No tienes un ecosistema? <a onclick="switchModal('loginModal', 'registerModal')">Regístrate aquí</a>
            </div>
        </div>
    </div>

    <div id="registerModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 550px;">
            <button class="close-btn" type="button" onclick="closeModal('registerModal')">&times;</button>
            <h2>Inicializar Sistema (Registro)</h2>

            <?php if($error_msg && $active_modal == 'register'): ?> <div class="msg-error"><?= $error_msg ?></div> <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="register">
                
                <div style="display: flex; gap: 15px;">
                    <div class="input-group" style="flex: 1;">
                        <label>Usuario (Alias) *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>Foto de Perfil</label>
                        <div class="avatar-selector-btn" onclick="openModal('avatarModal')">
                            <div class="avatar-preview-circle" id="mainAvatarPreview">👤</div>
                            <span class="avatar-text" id="mainAvatarText">Elegir Imagen</span>
                        </div>
                        
                        <input type="hidden" name="selected_avatar" id="hiddenAvatarData">
                        <input type="file" name="profile_pic" id="hiddenFileInput" accept="image/png, image/jpeg" style="display: none;" onchange="handleFileUpload(this)">
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="input-group" style="flex: 1;">
                        <label>Correo Electrónico *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>Contraseña *</label>
                        <input type="password" name="password" required>
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="input-group" style="flex: 1;">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="birth_date">
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>Profesión / Estudio</label>
                        <input type="text" name="profession" placeholder="Ej. Ing. Sistemas">
                    </div>
                </div>

                <div class="input-group">
                    <label>Biografía / Descripción</label>
                    <textarea name="bio" placeholder="Describe brevemente tus objetivos..."></textarea>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="consent" id="consent" required style="margin-top: 3px;">
                    <label for="consent" style="margin:0;">Autorizo el tratamiento de mis datos personales según la Ley 1581 de 2012 para el análisis vocacional y de productividad.</label>
                </div>

                <button type="submit" class="btn-primary btn-full">Registrar y Crear Perfil</button>
            </form>
            <div class="toggle-text">
                ¿Ya tienes un sistema activo? <a onclick="switchModal('registerModal', 'loginModal')">Inicia sesión</a>
            </div>
        </div>
    </div>

    <div id="avatarModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 350px; text-align: center;">
            <button class="close-btn" type="button" onclick="closeModal('avatarModal')">&times;</button>
            <h2>Elige tu Avatar</h2>
            
            <div class="avatar-grid">
                <div class="avatar-option" onclick="selectAvatar('🧠')">🧠</div>
                <div class="avatar-option" onclick="selectAvatar('⚡')">⚡</div>
                <div class="avatar-option" onclick="selectAvatar('🚀')">🚀</div>
                <div class="avatar-option" onclick="selectAvatar('💻')">💻</div>
                <div class="avatar-option" onclick="selectAvatar('🦊')">🦊</div>
                <div class="avatar-option" onclick="selectAvatar('🦉')">🦉</div>
                <div class="avatar-option" onclick="selectAvatar('🤖')">🤖</div>
                <div class="avatar-option" onclick="selectAvatar('👽')">👽</div>
            </div>
            
            <div style="margin: 20px 0; color: var(--text-muted); font-size: 0.9rem;">o si prefieres</div>
            
            <button type="button" class="btn-secondary btn-full" onclick="document.getElementById('hiddenFileInput').click();">
                Subir foto desde el equipo
            </button>
        </div>
    </div>

    <script>
        // --- CONTROL DE MODALES ---
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            if(id !== 'avatarModal') { 
                document.body.style.overflow = 'hidden'; 
            }
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            // Si cerramos el modal principal, devolvemos el scroll
            if(id === 'loginModal' || id === 'registerModal') {
                document.body.style.overflow = 'auto'; 
            }
        }

        function switchModal(closeId, openId) {
            closeModal(closeId);
            openModal(openId);
        }

        // Auto-abrir modal si PHP detectó un error
        <?php if($active_modal): ?>
            openModal('<?= $active_modal ?>Modal');
        <?php endif; ?>

        // Cerrar modales haciendo clic fuera del cuadro blanco
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        // --- LÓGICA DE SELECCIÓN DE IMAGEN/AVATAR ---
        function selectAvatar(emoji) {
            // Guardar en el input oculto
            document.getElementById('hiddenAvatarData').value = emoji;
            // Limpiar el input de archivo por si había algo antes
            document.getElementById('hiddenFileInput').value = ''; 
            
            // Actualizar la vista previa visual
            const preview = document.getElementById('mainAvatarPreview');
            preview.innerText = emoji;
            preview.style.backgroundImage = 'none';
            document.getElementById('mainAvatarText').innerText = 'Avatar Seleccionado';
            
            closeModal('avatarModal');
        }

        function handleFileUpload(input) {
            if (input.files && input.files[0]) {
                // Limpiar la selección de avatar predefinido
                document.getElementById('hiddenAvatarData').value = ''; 
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Actualizar la vista previa con la imagen real
                    const preview = document.getElementById('mainAvatarPreview');
                    preview.innerText = '';
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    document.getElementById('mainAvatarText').innerText = 'Foto Cargada';
                    
                    closeModal('avatarModal');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>