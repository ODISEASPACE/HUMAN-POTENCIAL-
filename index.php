<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APH | Anthropotechnology</title>
    <style>
        /* Estilos base (puedes mover esto a un archivo style.css luego) */
        :root {
            --bg-base: #010409;
            --bg-panel: #0d1117;
            --border-color: #30363d;
            --text-main: #c9d1d9;
            --accent: #58a6ff;
            --card-bg: #161b22;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            margin: 0;
            display: flex;
            height: 100vh;
        }
        /* Menú Lateral */
        nav {
            width: 250px;
            background-color: var(--bg-panel);
            border-right: 1px solid var(--border-color);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        nav h2 { color: #fff; text-align: center; letter-spacing: 2px; }
        .nav-link {
            display: block;
            padding: 15px;
            color: var(--text-main);
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(88, 166, 255, 0.1);
            color: var(--accent);
            border: 1px solid rgba(88, 166, 255, 0.2);
        }
        /* Contenido Principal */
        main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--card-bg), var(--bg-base));
            border: 1px solid var(--border-color);
            padding: 40px;
            border-radius: 10px;
            text-align: center;
        }
        .welcome-card h1 { color: #fff; font-size: 2.5em; margin-bottom: 10px; }
        .welcome-card p { color: #8b949e; font-size: 1.2em; }
    </style>
</head>
<body>

    <nav>
        <h2>A P H</h2>
        <p style="text-align: center; font-size: 0.8em; color: #8b949e; margin-bottom: 30px;">Core System V2.0</p>
        
        <a href="?view=home" class="nav-link">Inicio</a>
        <a href="?view=dashboard" class="nav-link">Panel de Control (API)</a>
        <a href="?view=calendar" class="nav-link">Sistemas de Vida</a>
        <a href="?view=forum" class="nav-link">Comunidad Activa</a>
    </nav>

    <main>
        <?php
            // Comprobamos qué vista quiere ver el usuario (por defecto: home)
            $view = isset($_GET['view']) ? $_GET['view'] : 'home';

            // Validamos que el archivo exista por seguridad
            if ($view === 'home') {
                echo '
                <div class="welcome-card">
                    <h1>Bienvenido a Anthropotechnology</h1>
                    <p>El primer ecosistema enfocado en el desarrollo y la evolución humana.</p>
                    <p>Selecciona un módulo en el menú lateral para inicializar el sistema.</p>
                </div>';
            } elseif (file_exists("views/{$view}.php")) {
                // Incluimos el archivo correspondiente
                include("views/{$view}.php");
            } else {
                echo "<h2>Error 404: Módulo no encontrado.</h2>";
            }
        ?>
    </main>

</body>
</html>