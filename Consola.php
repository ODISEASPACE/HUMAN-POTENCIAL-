<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APH Core - Sandbox SQL</title>
    <style>
        :root {
            --bg-backend: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --text-main: #c9d1d9;
            --text-muted: #8b949e;
            --accent-neon: #58a6ff;
            --terminal-green: #7ee787;
        }

        body {
            background-color: var(--bg-backend);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 85vh;
        }

        .console-container {
            width: 100%;
            max-width: 800px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .console-header {
            background-color: #090d13;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .window-dots {
            display: flex;
            gap: 8px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .dot.r { background-color: #f85149; }
        .dot.y { background-color: #f0883e; }
        .dot.g { background-color: #56d364; }

        .console-title {
            font-size: 0.85rem;
            font-family: monospace;
            color: var(--text-muted);
            letter-spacing: 1px;
        }

        .console-body {
            padding: 25px;
        }

        h2 {
            margin-top: 0;
            font-size: 1.5rem;
            font-weight: 500;
            color: #fff;
        }

        p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .terminal-mock {
            background-color: #040406;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
            font-family: 'Fira Code', monospace, Consolas;
            font-size: 0.9rem;
            color: var(--terminal-green);
            margin-top: 20px;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.8);
        }

        .terminal-line {
            margin-bottom: 8px;
        }

        .terminal-line span {
            color: var(--accent-neon);
        }

        .action-input {
            width: 100%;
            background: transparent;
            border: none;
            color: #fff;
            font-family: monospace;
            font-size: 0.95rem;
            outline: none;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed var(--border-color);
        }
    </style>
</head>
<body>

<div class="console-container">
    <div class="console-header">
        <div class="window-dots">
            <div class="dot r"></div>
            <div class="dot y"></div>
            <div class="dot g"></div>
        </div>
        <div class="console-title">APH_CORE_SANDBOX // RDS_PG</div>
    </div>
    
    <div class="console-body">
        <h2>Consola de Datos Relacionales</h2>
        <p>Entorno aislado de pruebas de ingeniería para la manipulación y optimización de flujos lógicos estructurados. Ejecuta sentencias directas sobre la infraestructura AWS de manera controlada.</p>
        
        <div class="terminal-mock">
            <div class="terminal-line"><span>aph-coreDB=></span> SELECT status, version FROM cluster_meta;</div>
            <div class="terminal-line" style="color: var(--text-main);">[INFO] Conectado exitosamente a AWS RDS Instance.</div>
            <div class="terminal-line" style="color: var(--text-muted);">[DATA] Status: Synchronized | Engine: PostgreSQL 15.3</div>
            <div class="terminal-line"><span>aph-coreDB=></span> <input type="text" class="action-input" placeholder="Escribe una consulta SQL o comando aquí..." autofocus></div>
        </div>
    </div>
</div>

</body>
</html>