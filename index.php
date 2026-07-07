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
            --accent: #805AD5; /* Tono púrpura suave y tecnológico */
            --accent-hover: #6B46C1;
            --border-color: #E2E8F0;
            --nav-bg: rgba(255, 255, 255, 0.85);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- BARRA DE NAVEGACIÓN SUPERIOR --- */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: var(--nav-bg);
            backdrop-filter: blur(12px); /* Efecto cristal moderno */
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: baseline;
            gap: 10px;
            text-decoration: none;
        }

        .brand h2 {
            font-weight: 800;
            letter-spacing: 2px;
            font-size: 1.5rem;
            color: var(--text-main);
        }

        .brand span {
            font-size: 0.75rem;
            color: var(--accent);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .nav-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn-ghost {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            transition: color 0.3s;
            font-size: 0.95rem;
        }
        
        .btn-ghost:hover { color: var(--text-main); }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(128, 90, 213, 0.25);
            font-size: 0.95rem;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(128, 90, 213, 0.35);
        }

        /* --- SECCIÓN HERO (PANTALLA PRINCIPAL) --- */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 150px 20px 80px;
            background: radial-gradient(circle at top, #ffffff 0%, #FAFAFC 100%);
        }

        .hero-badge {
            background: rgba(128, 90, 213, 0.1);
            color: var(--accent);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        h1 {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            max-width: 850px;
            margin-bottom: 24px;
            letter-spacing: -1.5px;
            color: var(--text-main);
        }

        h1 span { color: var(--accent); }

        p.subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 45px;
        }

        .hero-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-secondary {
            background: #fff;
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.05rem;
        }

        .btn-secondary:hover { border-color: var(--text-muted); }
        .btn-primary.large { padding: 12px 32px; font-size: 1.05rem; }

        /* Animación de entrada */
        .fade-in { animation: fadeIn 0.8s ease forwards; }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Responsividad para móviles */
        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            .nav-actions { display: none; } /* En móvil se podría ocultar o hacer menú hamburguesa */
        }
    </style>
</head>
<body>

    <header>
        <a href="/" class="brand">
            <h2>A P H</h2>
            <span>Core System V3.1</span>
        </a>
        <div class="nav-actions">
            <a href="login.php" class="btn-ghost">Iniciar Sesión</a>
            <a href="registro.php" class="btn-primary">Registrarse</a>
        </div>
    </header>

    <main class="fade-in">
        <div class="hero-badge">Infraestructura Central</div>
        <h1>El entorno para tu <span>expansión cognitiva.</span></h1>
        <p class="subtitle">Descubre un ecosistema minimalista diseñado para maximizar tu enfoque, estructurar tus herramientas y tomar el control de tus hábitos de vida.</p>
        
        <div class="hero-actions">
            <a href="registro.php" class="btn-primary large">Comenzar ahora</a>
            <a href="/anthropotechnology.apk" class="btn-secondary">Descargar App</a>
        </div>
    </main>

</body>
</html>