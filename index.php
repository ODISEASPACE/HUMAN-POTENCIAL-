<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APH OS | Anthropotechnology</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #F5F7FA;
            --bg-panel: #FFFFFF;
            --border-color: #E2E8F0;
            --text-main: #2D3748;
            --text-muted: #718096;
            --accent: #3182CE;
            --accent-hover: #2B6CB0;
            --success: #38A169;
            --card-bg: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* --- MENÚ LATERAL (SIDEBAR) --- */
        nav {
            width: 280px;
            background: var(--bg-panel);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            z-index: 10;
            box-shadow: 2px 0 15px rgba(0,0,0,0.02);
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand h2 {
            font-weight: 800;
            letter-spacing: 3px;
            font-size: 1.8rem;
            color: #2D3748;
        }

        .brand span { font-size: 0.75rem; color: var(--accent); letter-spacing: 1px; text-transform: uppercase; font-weight: 600; }

        .nav-links { flex: 1; }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(49, 130, 206, 0.08);
            color: var(--accent);
            border: 1px solid rgba(49, 130, 206, 0.2);
        }

        /* --- BOTONES DEL SIDEBAR --- */
        .bottom-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        .btn-download {
            background: var(--success);
            color: #fff;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 10px rgba(56, 161, 105, 0.2);
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(56, 161, 105, 0.3);
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
            font-weight: 600;
        }

        .btn-api:hover { color: var(--text-main); border-color: #CBD5E0; }

        /* --- CONTENIDO PRINCIPAL --- */
        main {
            flex: 1;
            padding: 50px;
            overflow-y: auto;
            position: relative;
        }

        .welcome-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 50px;
            border-radius: 16px;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .welcome-card h1 { font-size: 3rem; margin-bottom: 20px; color: var(--text-main); }
        .welcome-card p { color: var(--text-muted); font-size: 1.2rem; line-height: 1.8; }

        /* Componentes globales para las vistas */
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .btn-primary { background: var(--accent); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(49, 130, 206, 0.2); }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(49, 130, 206, 0.3); }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; border-radius: 16px; transition: 0.3s; position: relative; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .card:hover { border-color: var(--accent); transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.08); }
        .badge { position: absolute; top: 20px; right: 20px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; }
        
        .fade-in { animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav>
        <div class="brand">
            <h2>A P H</h2>
            <span>Core System V3.1</span>
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
            <a href="red-de-produccion.php" target="_blank" class="btn-api">Red de Producción ↗</a>
        </div>
    </nav>

    <main class="fade-in">
        <?php
            if ($view === 'home') {
                echo '
                <div class="welcome-card">
                    <h1>Ecosistema APH</h1>
                    <p>Bienvenido a la infraestructura central. Este entorno claro y minimalista está diseñado para maximizar el enfoque y control sobre tus herramientas de expansión cognitiva y hábitos de vida.</p>
                </div>';
            } elseif (file_exists("views/{$view}.php")) {
                include("views/{$view}.php");
            } else {
                echo "<div class='welcome-card'><h2 style='color: #E53E3E;'>Error 404</h2><p>El módulo cognitivo que buscas no existe o está desconectado.</p></div>";
            }
        ?>
    </main>

</body>
</html>