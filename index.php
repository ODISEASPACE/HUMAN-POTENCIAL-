<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APH OS | Anthropotechnology</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #050505;
            --bg-panel: #111111;
            --border-color: #222222;
            --text-main: #e5e5e5;
            --text-muted: #888888;
            --accent: #00f0ff;
            --accent-hover: #00c3ff;
            --success: #10b981;
            --card-bg: rgba(17, 17, 17, 0.6);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
            background-image: radial-gradient(circle at 50% 0%, #1a1a1a 0%, transparent 70%);
        }

        /* --- MENÚ LATERAL (SIDEBAR) --- */
        nav {
            width: 280px;
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            z-index: 10;
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand h2 {
            font-weight: 800;
            letter-spacing: 3px;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #fff, #888);
            background-clip: text; /* standard property for compatibility */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand span { font-size: 0.75rem; color: var(--accent); letter-spacing: 1px; text-transform: uppercase; }

        .nav-links { flex: 1; }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(0, 240, 255, 0.05);
            color: var(--accent);
            border: 1px solid rgba(0, 240, 255, 0.2);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.05);
        }

        /* --- BOTONES DEL SIDEBAR (APK y API) --- */
        .bottom-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        .btn-download {
            background: linear-gradient(135deg, var(--success), #059669);
            color: #fff;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-api {
            background: transparent;
            color: var(--text-muted);
            text-decoration: none;
            text-align: center;
            font-size: 0.85rem;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: color 0.3s, border-color 0.3s;
        }

        .btn-api:hover { color: #fff; border-color: #555; }

        /* --- CONTENIDO PRINCIPAL --- */
        main {
            flex: 1;
            padding: 50px;
            overflow-y: auto;
            position: relative;
        }

        .welcome-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            padding: 50px;
            border-radius: 16px;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .welcome-card h1 { font-size: 3rem; margin-bottom: 20px; }
        .welcome-card p { color: var(--text-muted); font-size: 1.2rem; line-height: 1.8; }

        /* Componentes globales para las vistas */
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .btn-primary { background: var(--accent); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { background: var(--accent-hover); box-shadow: 0 0 20px rgba(0,240,255,0.4); }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; border-radius: 16px; transition: 0.3s; position: relative; }
        .card:hover { border-color: var(--accent); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .badge { position: absolute; top: 20px; right: 20px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; letter-spacing: 1px; }
        
        /* Efecto de entrada suave */
        .fade-in { animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav>
        <div class="brand">
            <h2>A P H</h2>
            <span>Core System V3.0</span>
        </div>
        
        <?php $view = isset($_GET['view']) ? $_GET['view'] : 'home'; ?>

        <div class="nav-links">
            <a href="?view=home" class="nav-link <?= $view == 'home' ? 'active' : '' ?>">⌂ Inicio</a>
            <a href="?view=dashboard" class="nav-link <?= $view == 'dashboard' ? 'active' : '' ?>">⛁ Panel de Control (API)</a>
            <a href="?view=calendar" class="nav-link <?= $view == 'calendar' ? 'active' : '' ?>">⏱ Sistemas de Vida</a>
            <a href="?view=forum" class="nav-link <?= $view == 'forum' ? 'active' : '' ?>">⚑ Comunidad Activa</a>
            <a href="?view=market" class="nav-link <?= $view == 'market' ? 'active' : '' ?>">✦ Hub de Herramientas</a>
        </div>

        <div class="bottom-actions">
            <a href="/anthropotechnology.apk" class="btn-download">↓ Descargar APH App</a>
            <a href="/api/docs" target="_blank" class="btn-api">Ver Documentación API ↗</a>
        </div>
    </nav>

    <main class="fade-in">
        <?php
            if ($view === 'home') {
                echo '
                <div class="welcome-card">
                    <h1>Ecosistema APH</h1>
                    <p>Bienvenido a la infraestructura central. Este entorno ha sido rediseñado para ofrecer máxima claridad y control sobre tus nodos cognitivos, bases de datos y herramientas de expansión.</p>
                </div>';
            } elseif (file_exists("views/{$view}.php")) {
                include("views/{$view}.php");
            } else {
                echo "<div class='welcome-card'><h2 style='color: #f43f5e;'>Error 404</h2><p>El módulo cognitivo que buscas no existe o está desconectado.</p></div>";
            }
        ?>
    </main>

</body>
</html>