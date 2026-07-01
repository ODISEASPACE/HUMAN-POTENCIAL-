<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red de Producción | APH OS</title>
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
            
            /* Colores para las insignias y bordes de los entornos */
            --db-color: #38A169;       /* Verde */
            --algo-color: #805AD5;     /* Morado */
            --ui-color: #DD6B20;       /* Naranja */
            --flow-color: #319795;     /* Teal/Cian oscuro */
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

        .nav-link:hover {
            background: rgba(49, 130, 206, 0.08);
            color: var(--accent);
            border: 1px solid rgba(49, 130, 206, 0.2);
        }

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

        .btn-download:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(56, 161, 105, 0.3); }

        .btn-api {
            background: rgba(49, 130, 206, 0.08);
            color: var(--accent);
            text-decoration: none;
            text-align: center;
            font-size: 0.85rem;
            padding: 10px;
            border: 1px solid rgba(49, 130, 206, 0.2);
            border-radius: 8px;
            font-weight: 600;
        }

        /* --- CONTENIDO PRINCIPAL --- */
        main {
            flex: 1;
            padding: 40px 50px;
            overflow-y: auto;
        }

        .top-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            margin-bottom: 40px; 
            border-bottom: 1px solid var(--border-color); 
            padding-bottom: 20px; 
        }

        .header-titles h1 { font-size: 2rem; color: var(--text-main); margin-bottom: 8px; }
        .header-titles p { color: var(--text-muted); font-size: 0.95rem; max-width: 600px; line-height: 1.5; }

        .btn-primary { 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s; 
            box-shadow: 0 4px 10px rgba(49, 130, 206, 0.2); 
            text-decoration: none;
        }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(49, 130, 206, 0.3); }

        /* --- TARJETAS DE ENTORNOS --- */
        .grid-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
            gap: 25px; 
        }

        .card { 
            background: var(--card-bg); 
            border: 1px solid var(--border-color); 
            padding: 30px; 
            border-radius: 12px; 
            transition: 0.3s; 
            position: relative; 
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); 
        }

        .card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.06); }

        .card h3 { font-size: 1.3rem; margin-bottom: 15px; color: var(--text-main); padding-right: 80px; }
        .card p { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 25px; flex-grow: 1; }

        .badge { 
            position: absolute; 
            top: 25px; 
            right: 25px; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 700; 
            letter-spacing: 0.5px; 
            background: #fff;
        }

        /* Variaciones de color por módulo de prueba */
        .card-db:hover { border-color: var(--db-color); }
        .card-db .badge { color: var(--db-color); border: 1px solid var(--db-color); }
        .btn-outline-db { background: transparent; color: var(--db-color); border: 1px solid var(--db-color); padding: 10px; border-radius: 6px; text-align: center; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-outline-db:hover { background: var(--db-color); color: #fff; }

        .card-algo:hover { border-color: var(--algo-color); }
        .card-algo .badge { color: var(--algo-color); border: 1px solid var(--algo-color); }
        .btn-outline-algo { background: transparent; color: var(--algo-color); border: 1px solid var(--algo-color); padding: 10px; border-radius: 6px; text-align: center; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-outline-algo:hover { background: var(--algo-color); color: #fff; }

        .card-ui:hover { border-color: var(--ui-color); }
        .card-ui .badge { color: var(--ui-color); border: 1px solid var(--ui-color); }
        .btn-outline-ui { background: transparent; color: var(--ui-color); border: 1px solid var(--ui-color); padding: 10px; border-radius: 6px; text-align: center; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-outline-ui:hover { background: var(--ui-color); color: #fff; }

        .card-flow:hover { border-color: var(--flow-color); }
        .card-flow .badge { color: var(--flow-color); border: 1px solid var(--flow-color); }
        .btn-outline-flow { background: transparent; color: var(--flow-color); border: 1px solid var(--flow-color); padding: 10px; border-radius: 6px; text-align: center; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-outline-flow:hover { background: var(--flow-color); color: #fff; }

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
        
        <div class="nav-links">
            <a href="index.php?view=home" class="nav-link">⌂ Inicio</a>
            <a href="index.php?view=dashboard" class="nav-link">⛁ Panel de Control (API)</a>
            <a href="index.php?view=calendar" class="nav-link">⏱ Sistemas de Vida</a>
            <a href="index.php?view=forum" class="nav-link">⚑ Comunidad Activa</a>
            <a href="index.php?view=market" class="nav-link">✦ Hub de Herramientas</a>
        </div>

        <div class="bottom-actions">
            <a href="/anthropotechnology.apk" class="btn-download">↓ Descargar APH App</a>
            <a href="#" class="btn-api active">Red de Producción ◉</a>
        </div>
    </nav>

    <main class="fade-in">
        <div class="top-bar">
            <div class="header-titles">
                <h1>Red de Producción y Pruebas</h1>
                <p>Entornos aislados (Sandboxes) diseñados para la integración de nuevos desarrolladores. Realiza pruebas seguras de QA sin afectar la rama principal (Main).</p>
            </div>
            <a href="#" class="btn-primary">+ Inicializar Instancia</a>
        </div>

        <div class="grid-container">
            
            <div class="card card-db">
                <span class="badge">Data Layer</span>
                <h3>Sandbox SQL / Firebase</h3>
                <p>Entorno seguro con datos ficticios (Mock Data). Permite ejecutar consultas destructivas, testear migraciones relacionales y validar reglas de seguridad en la nube.</p>
                <div style="font-size: 0.8rem; color: #A0AEC0; margin-bottom: 15px;">Stack: MySQL, Firebase Firestore</div>
                <a href="Consola.php" target="_blank" class="btn-outline-db">Acceder a Consola DB</a>
            </div>

            <div class="card card-algo">
                <span class="badge">Lógica Core</span>
                <h3>Testing de Algoritmos</h3>
                <p>Consola para ejecutar y debugear scripts aislados. Valida el rendimiento del rastreo algorítmico, lógica de presupuestos y automatizaciones de hábitos.</p>
                <div style="font-size: 0.8rem; color: #A0AEC0; margin-bottom: 15px;">Stack: Python, PHP Scripts</div>
                <a href="#" class="btn-outline-algo">Ejecutar Pruebas</a>
            </div>

            <div class="card card-ui">
                <span class="badge">Frontend / Mobile</span>
                <h3>Visual Storybook</h3>
                <p>Galería interactiva de componentes UI. Permite al desarrollador probar la responsividad, estados de botones, tarjetas y el renderizado en vistas web y aplicaciones móviles.</p>
                <div style="font-size: 0.8rem; color: #A0AEC0; margin-bottom: 15px;">Stack: JavaScript, CSS, Flutter (Dart)</div>
                <a href="#" class="btn-outline-ui">Ver Componentes</a>
            </div>

            <div class="card card-flow">
                <span class="badge">Network Layer</span>
                <h3>Monitor de Flujo (API)</h3>
                <p>Rastreador en tiempo real de peticiones (Requests/Responses). Verifica la comunicación entre los endpoints de APH OS y las herramientas cliente, evaluando latencia y payloads.</p>
                <div style="font-size: 0.8rem; color: #A0AEC0; margin-bottom: 15px;">Stack: REST API, JSON</div>
                <a href="#" class="btn-outline-flow">Monitorear Tráfico</a>
            </div>

        </div>
    </main>

</body>
</html>