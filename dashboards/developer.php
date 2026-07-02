<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Console | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        /* Base Styles heredados de APH OS */
        :root { --bg-base: #0F172A; --bg-panel: #1E293B; --border-color: #334155; --text-main: #F8FAFC; --text-muted: #94A3B8; --accent: #38BDF8; --success: #34D399; --danger: #F87171; --warning: #FBBF24; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        nav { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; }
        .brand { text-align: center; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .brand h2 { font-weight: 800; letter-spacing: 2px; color: var(--accent); }
        .brand span { font-size: 0.75rem; color: var(--text-muted); }
        
        .nav-links { flex: 1; }
        .nav-link { display: flex; align-items: center; padding: 12px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; margin-bottom: 5px; transition: 0.2s; }
        .nav-link.active, .nav-link:hover { background: rgba(56, 189, 248, 0.1); color: var(--accent); }
        
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        
        /* Grid de Estado de Servicios */
        .services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .service-card { background: var(--bg-panel); border: 1px solid var(--border-color); padding: 20px; border-radius: 10px; }
        .service-card h4 { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 10px; }
        .service-status { display: flex; align-items: center; gap: 10px; font-weight: 600; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot.up { background: var(--success); box-shadow: 0 0 8px var(--success); }
        
        /* Consola / Terminal Simulada */
        .terminal { background: #000; border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; font-family: monospace; color: var(--success); }
        .terminal-header { color: var(--text-muted); font-size: 0.8rem; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .log-line { margin-bottom: 5px; font-size: 0.9rem; }
        .log-error { color: var(--danger); }
        .log-warn { color: var(--warning); }
    </style>
</head>
<body>
    <nav>
        <div class="brand">
            <h2>APH DEV</h2>
            <span>Entorno: Staging (Pruebas)</span>
        </div>
        <div class="nav-links">
            <a href="#" class="nav-link active">⛁ Estado de Red</a>
            <a href="#" class="nav-link">⚡ Sandboxes (SQL/Algo)</a>
            <a href="#" class="nav-link">🔑 Gestión API Keys</a>
            <a href="#" class="nav-link">📖 Documentación Core</a>
        </div>
    </nav>
    <main>
        <div class="header">
            <div>
                <h1>Monitor de Flujo (API)</h1>
                <p style="color: var(--text-muted);">Rastreador en tiempo real de peticiones APH OS.</p>
            </div>
            <button style="background: var(--accent); color: #000; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">Ejecutar Script</button>
        </div>

        <div class="services-grid">
            <div class="service-card">
                <h4>Base de Datos (PostgreSQL)</h4>
                <div class="service-status"><div class="dot up"></div> Latencia: 12ms</div>
            </div>
            <div class="service-card">
                <h4>API REST Core</h4>
                <div class="service-status"><div class="dot up"></div> Status: 200 OK</div>
            </div>
            <div class="service-card">
                <h4>Motor de Algoritmos (Python)</h4>
                <div class="service-status"><div class="dot up"></div> Memoria: 45%</div>
            </div>
        </div>

        <div class="terminal">
            <div class="terminal-header">Terminal de Logs en Vivo (Monitor de Flujo) - TTY1</div>
            <div class="log-line">[19:05:22] GET /api/v1/users/metrics - 200 OK (8ms)</div>
            <div class="log-line">[19:05:25] POST /api/v1/auth/login - 200 OK (45ms)</div>
            <div class="log-warn">[19:05:30] WARN: Intento de acceso fallido IP 192.168.1.45 (Invalid Token)</div>
            <div class="log-line">[19:05:42] GET /api/v1/habits/sync - 200 OK (15ms)</div>
            <div class="log-error">[19:06:01] ERROR: Relación "registro_habitos" no encontrada. Ejecutando rollback...</div>
            <div class="log-line">root@aph-os-dev:~# _</div>
        </div>
    </main>
</body>
</html>