<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistemas de Vida APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #F5F7FA;
            --bg-panel: #FFFFFF;
            --border-color: #E2E8F0;
            --text-main: #2D3748;
            --text-muted: #718096;
            --accent: #3182CE;
            --success: #38A169;
            --warning: #D69E2E;
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

        /* --- MENÚ LATERAL DEL USUARIO --- */
        nav {
            width: 260px;
            background: var(--bg-panel);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            z-index: 10;
        }

        .user-profile {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .avatar {
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 auto 15px auto;
        }

        .user-profile h3 { font-size: 1.1rem; color: var(--text-main); }
        .user-profile span { font-size: 0.8rem; color: var(--text-muted); }

        .nav-links { flex: 1; }
        .nav-link {
            display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted);
            text-decoration: none; font-weight: 600; border-radius: 8px; margin-bottom: 8px;
            transition: 0.3s;
        }
        .nav-link.active, .nav-link:hover {
            background: rgba(49, 130, 206, 0.08); color: var(--accent);
        }

        .logout-btn {
            margin-top: auto;
            text-align: center;
            padding: 12px;
            color: #E53E3E;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid #FC8181;
            border-radius: 8px;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #FFF5F5; }

        /* --- CONTENIDO PRINCIPAL --- */
        main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .greeting h1 { font-size: 1.8rem; margin-bottom: 5px; }
        .greeting p { color: var(--text-muted); font-size: 0.95rem; }

        /* ESTADO DE CONEXIÓN */
        .connection-status {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg);
            padding: 10px 20px;
            border-radius: 30px;
            border: 1px solid var(--border-color);
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .status-dot {
            width: 10px;
            height: 10px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(56, 161, 105, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(56, 161, 105, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 161, 105, 0); }
        }

        /* MÉTRICAS DE POTENCIAL HUMANO */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .metric-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .metric-card h4 { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .metric-card .value { font-size: 2.5rem; font-weight: 800; color: var(--text-main); }
        .metric-card .trend { font-size: 0.85rem; color: var(--success); font-weight: 600; margin-top: 5px; }

        /* SECCIÓN DE HÁBITOS RECIENTES */
        .habits-section {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .habits-section h2 { font-size: 1.2rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }

        .habit-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #EDF2F7;
        }
        .habit-item:last-child { border-bottom: none; }
        .habit-info strong { display: block; color: var(--text-main); }
        .habit-info span { font-size: 0.85rem; color: var(--text-muted); }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-badge.done { background: #C6F6D5; color: #22543D; }
        .status-badge.pending { background: #FEEBC8; color: #7B341E; }

    </style>
</head>
<body>

    <nav>
        <div class="user-profile">
            <div class="avatar">U</div>
            <h3 id="display-name">Usuario Estándar</h3>
            <span id="display-email">usuario@aph.os</span>
        </div>
        
        <div class="nav-links">
            <a href="#" class="nav-link active">⌂ Panel Resumen</a>
            <a href="#" class="nav-link">⏱ Sistemas de Vida</a>
            <a href="#" class="nav-link">📊 Analítica Personal</a>
            <a href="#" class="nav-link">⚙️ Configuración</a>
        </div>

        <a href="login.html" class="logout-btn">Cerrar Sesión</a>
    </nav>

    <main>
        <div class="top-header">
            <div class="greeting">
                <h1>Bienvenido al Sistema Central</h1>
                <p>Tu infraestructura de desarrollo de potencial humano está lista.</p>
            </div>
            
            <div class="connection-status">
                <div class="status-dot"></div>
                <span id="db-status">Conectado a PostgreSQL</span>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <h4>Consistencia Semanal</h4>
                <div class="value">85%</div>
                <div class="trend">↑ +5% vs semana anterior</div>
            </div>
            <div class="metric-card">
                <h4>Hábitos Activos</h4>
                <div class="value">4</div>
                <div class="trend" style="color: var(--text-muted);">En ejecución</div>
            </div>
            <div class="metric-card">
                <h4>Racha Actual</h4>
                <div class="value">12 <span style="font-size: 1rem; color: var(--text-muted);">Días</span></div>
                <div class="trend">Mantén el ritmo</div>
            </div>
        </div>

        <div class="habits-section">
            <h2>Ejecución de Hábitos (Hoy)</h2>
            <div class="habit-item">
                <div class="habit-info">
                    <strong>Lectura Técnica</strong>
                    <span>30 minutos diarios</span>
                </div>
                <span class="status-badge done">Completado</span>
            </div>
            <div class="habit-item">
                <div class="habit-info">
                    <strong>Práctica de Idiomas (Shadowing)</strong>
                    <span>Serie objetivo / Anki</span>
                </div>
                <span class="status-badge done">Completado</span>
            </div>
            <div class="habit-item">
                <div class="habit-info">
                    <strong>Desarrollo Proyecto Odisea</strong>
                    <span>Revisión de arquitectura base</span>
                </div>
                <span class="status-badge pending">Pendiente</span>
            </div>
        </div>
    </main>

    <script>
        // En un entorno real, estos datos vendrían del backend al renderizar la vista
        // o mediante una petición fetch() a tu API.
        document.addEventListener('DOMContentLoaded', () => {
            console.log("Dashboard inicializado. Esperando datos reales de la BD.");
            // Aquí iría la lógica para validar el token de sesión.
        });
    </script>
</body>
</html>