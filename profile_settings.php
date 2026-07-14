<?php
session_start();
require 'db.php';

// Redirección de seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// Procesar el formulario si se envía por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $profession = trim($_POST['profession']);
    $profile_picture = trim($_POST['profile_picture']);
    $archetype = (int)$_POST['archetype'];

    // Validar que el arquetipo esté en el rango permitido
    if ($archetype < 1 || $archetype > 4) {
        $archetype = 1;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, profession = ?, profile_picture = ?, archetype = ? WHERE id = ?");
        $stmt->execute([$username, $profession, $profile_picture, $archetype, $user_id]);
        
        $mensaje = "<div class='status-msg success'>Identidad estructural actualizada con éxito.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='status-msg error'>Error al actualizar el perfil: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Obtener los datos actuales para rellenar el formulario
$stmtUser = $pdo->prepare("SELECT username, profession, profile_picture, COALESCE(archetype, 1) as archetype FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Perfil | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-base: #FAFAFC; 
            --bg-panel: #FFFFFF; 
            --text-main: #1A202C; 
            --text-muted: #718096; 
            --accent: #805AD5; 
            --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Layout Principal */
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; align-items: center; }
        
        .header-dash { width: 100%; max-width: 600px; margin-bottom: 30px; text-align: left; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; color: var(--text-main); }
        .header-dash p { color: var(--text-muted); }

        /* Tarjeta de Formulario basada en el diseño Auth */
        .settings-card { 
            background: var(--bg-panel); 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.02); 
            width: 100%; 
            max-width: 600px; 
            border: 1px solid var(--border-color); 
        }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #4A5568; }
        .input-group input[type="text"], .input-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-family: inherit; 
            color: var(--text-main);
            background: var(--bg-base);
            transition: all 0.3s ease;
        }
        .input-group input:focus, .input-group select:focus { 
            outline: none; 
            border-color: var(--accent); 
            box-shadow: 0 0 0 3px var(--accent-light); 
            background: var(--bg-panel);
        }
        
        .archetype-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
            padding-left: 5px;
        }

        .btn-primary { 
            width: 100%; 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            padding: 14px; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 1rem;
            cursor: pointer; 
            transition: 0.3s; 
            margin-top: 15px; 
        }
        .btn-primary:hover { background: #6B46C1; transform: translateY(-2px); }
        
        /* Mensajes de estado */
        .status-msg { padding: 15px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; margin-bottom: 25px; text-align: center; }
        .status-msg.success { background: #C6F6D5; color: #276749; border: 1px solid #9AE6B4; }
        .status-msg.error { background: #FED7D7; color: #C53030; border: 1px solid #FEB2B2; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-dash">
            <h1>Ajustes de Sistema</h1>
            <p>Configura tu identidad visual y tu nivel evolutivo dentro de APH OS.</p>
        </div>

        <div class="settings-card">
            <?php if($mensaje): ?> 
                <?= $mensaje ?> 
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label>Nombre / Alias de Usuario</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" placeholder="Ej: Daniel" required>
                </div>
                
                <div class="input-group">
                    <label>Especialidad / Profesión</label>
                    <input type="text" name="profession" value="<?= htmlspecialchars($currentUser['profession'] ?? '') ?>" placeholder="Ej: Ingeniería de Sistemas">
                </div>

                <div class="input-group">
                    <label>Avatar (Emoji o URL de imagen)</label>
                    <input type="text" name="profile_picture" value="<?= htmlspecialchars($currentUser['profile_picture'] ?? '') ?>" placeholder="Ej: 👨‍💻 o https://...">
                </div>

                <div class="input-group">
                    <label>Arquetipo Conductual (Nivel de Desbloqueo)</label>
                    <select name="archetype" id="archetype-select" required>
                        <option value="1" <?= ($currentUser['archetype'] == 1) ? 'selected' : '' ?>>Nivel 1: Vagabundo</option>
                        <option value="2" <?= ($currentUser['archetype'] == 2) ? 'selected' : '' ?>>Nivel 2: Soñador</option>
                        <option value="3" <?= ($currentUser['archetype'] == 3) ? 'selected' : '' ?>>Nivel 3: Soldado</option>
                        <option value="4" <?= ($currentUser['archetype'] == 4) ? 'selected' : '' ?>>Nivel 4: Ejecutor</option>
                    </select>
                    <div id="archetype-info" class="archetype-desc"></div>
                </div>

                <button type="submit" class="btn-primary">Actualizar Parámetros</button>
            </form>
        </div>
    </main>

    <script>
        // Script simple para actualizar la descripción del arquetipo seleccionado en tiempo real
        const select = document.getElementById('archetype-select');
        const info = document.getElementById('archetype-info');

        const descripciones = {
            '1': 'Módulos base: Panel Central y Registro Diario.',
            '2': 'Desbloquea: Análisis de Estado Humano.',
            '3': 'Desbloquea: Gestión de Proyectos.',
            '4': 'Acceso Total: Árbol de Decisiones y Matriz de Habilidades.'
        };

        function actualizarDescripcion() {
            info.innerText = descripciones[select.value];
        }

        select.addEventListener('change', actualizarDescripcion);
        actualizarDescripcion(); // Llamada inicial
    </script>
</body>
</html>