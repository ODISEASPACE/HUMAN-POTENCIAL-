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

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* --- NAVEGACIÓN --- */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            z-index: 100;
        }

        .brand { display: flex; align-items: baseline; gap: 10px; text-decoration: none; }
        .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--text-main); }
        .brand span { font-size: 0.75rem; color: var(--accent); letter-spacing: 1px; text-transform: uppercase; font-weight: 700; }

        .nav-actions { display: flex; gap: 20px; align-items: center; }
        .btn-ghost { text-decoration: none; color: var(--text-muted); font-weight: 600; transition: color 0.3s; font-size: 0.95rem; }
        .btn-ghost:hover { color: var(--text-main); }

        .btn-primary {
            background: var(--accent); color: #fff; border: none; padding: 10px 24px;
            border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none;
            transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(128, 90, 213, 0.25);
            font-size: 0.95rem;
        }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(128, 90, 213, 0.35); }
        .btn-secondary { background: #fff; color: var(--text-main); border: 1px solid var(--border-color); padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; font-size: 1.05rem; }
        .btn-secondary:hover { border-color: var(--text-muted); }

        /* --- SECCIONES COMUNES --- */
        section { padding: 100px 5%; max-width: 1200px; margin: 0 auto; }
        .section-title { text-align: center; font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; color: var(--text-main); }
        .section-subtitle { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 60px auto; }

        /* --- HERO --- */
        .hero {
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            text-align: center; padding: 180px 5% 100px;
            background: radial-gradient(circle at top, #ffffff 0%, #FAFAFC 100%);
        }
        .hero-badge { background: rgba(128, 90, 213, 0.1); color: var(--accent); padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; margin-bottom: 24px; letter-spacing: 0.5px; }
        .hero h1 { font-size: 4rem; font-weight: 800; line-height: 1.1; max-width: 850px; margin-bottom: 24px; letter-spacing: -1.5px; color: var(--text-main); }
        .hero h1 span { color: var(--accent); }
        .hero p.subtitle { font-size: 1.25rem; color: var(--text-muted); max-width: 600px; margin-bottom: 45px; }
        .hero-actions { display: flex; gap: 15px; flex-wrap: wrap; justify-content: center; }

        /* --- CARACTERÍSTICAS (CARDS) --- */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .feature-card {
            background: var(--card-bg); border: 1px solid var(--border-color); padding: 40px 30px;
            border-radius: 16px; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            text-align: left;
        }
        .feature-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 12px 25px rgba(0,0,0,0.05); }
        .feature-icon { width: 50px; height: 50px; background: rgba(128, 90, 213, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent); margin-bottom: 20px; }
        .feature-card h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 15px; }
        .feature-card p { color: var(--text-muted); font-size: 0.95rem; line-height: 1.7; }

        /* --- INTERACTIVO (MOCKUP DASHBOARD) --- */
        .interactive-section { background: #FFFFFF; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding-top: 100px; padding-bottom: 100px; }
        .dashboard-mockup {
            background: var(--bg-base); border: 1px solid var(--border-color); border-radius: 16px;
            overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.08); margin: 0 auto; max-width: 900px;
        }
        .mockup-header { background: #fff; padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; gap: 10px; }
        .dot { width: 12px; height: 12px; border-radius: 50%; background: #E2E8F0; }
        .dot.r { background: #FC8181; } .dot.y { background: #F6E05E; } .dot.g { background: #68D391; }
        
        .mockup-body { padding: 40px; display: flex; gap: 40px; align-items: center; }
        
        /* Pestañas */
        .mockup-tabs { display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .tab-btn {
            background: transparent; border: 1px solid transparent; padding: 15px 20px; text-align: left;
            border-radius: 8px; font-family: 'Inter', sans-serif; font-weight: 600; color: var(--text-muted);
            cursor: pointer; transition: 0.3s;
        }
        .tab-btn:hover { background: rgba(0,0,0,0.02); }
        .tab-btn.active { background: #fff; border: 1px solid var(--border-color); color: var(--accent); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        
        /* Gráfica animada */
        .mockup-graph-container { flex: 1.5; background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); height: 250px; display: flex; align-items: flex-end; gap: 15px; justify-content: space-between; }
        .bar-wrapper { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; gap: 10px; }
        .bar { width: 100%; background: rgba(128, 90, 213, 0.2); border-radius: 6px 6px 0 0; transition: height 0.8s ease; position: relative; }
        .bar.active { background: var(--accent); }
        .bar-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

        /* --- CTA FINAL --- */
        .cta-section { text-align: center; padding: 120px 5%; background: var(--accent); color: #fff; }
        .cta-section h2 { font-size: 3rem; font-weight: 800; margin-bottom: 20px; color: #fff;}
        .cta-section p { font-size: 1.2rem; opacity: 0.9; margin-bottom: 40px; }
        .btn-white { background: #fff; color: var(--accent); padding: 14px 32px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: 0.3s; font-size: 1.1rem; border: none; }
        .btn-white:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }

        /* Animación general */
        .fade-in { animation: fadeIn 0.8s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

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

    <div class="hero fade-in">
        <div class="hero-badge">Infraestructura Central</div>
        <h1>El entorno para tu <span>expansión cognitiva.</span></h1>
        <p class="subtitle">Descubre un ecosistema minimalista diseñado para maximizar tu enfoque, estructurar tus herramientas y tomar el control de tus hábitos de vida.</p>
        
        <div class="hero-actions">
            <a href="registro.php" class="btn-primary" style="padding: 14px 32px; font-size: 1.05rem;">Comenzar ahora</a>
            <a href="/anthropotechnology.apk" class="btn-secondary">Descargar App</a>
        </div>
    </div>

    <section class="features" id="caracteristicas">
        <h2 class="section-title">Ingeniería para tu rutina</h2>
        <p class="section-subtitle">Módulos integrados que operan en segundo plano para que tú te enfoques en ejecutar.</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">⏱</div>
                <h3>Sistemas de Vida</h3>
                <p>Métricas precisas y seguimiento de hábitos mediante algoritmos y temporizadores integrados. Diseñado para mantener la constancia sin fricción.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🧠</div>
                <h3>Expansión Cognitiva</h3>
                <p>Estructuras y bases de datos preparadas para el meta-aprendizaje. Registra tu progreso y aprovecha los picos de neuroplasticidad.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⛁</div>
                <h3>Control Centralizado</h3>
                <p>Una API robusta que sincroniza tu aplicación móvil y tu panel web en tiempo real. Todos tus datos alojados de manera segura en un solo ecosistema.</p>
            </div>
        </div>
    </section>

    <div class="interactive-section">
        <section style="padding-top: 0; padding-bottom: 0;">
            <h2 class="section-title">Visualiza tu progreso</h2>
            <p class="section-subtitle">El panel de control transforma tus registros diarios en métricas de rendimiento accionables.</p>
            
            <div class="dashboard-mockup">
                <div class="mockup-header">
                    <div class="dot r"></div><div class="dot y"></div><div class="dot g"></div>
                </div>
                <div class="mockup-body">
                    <div class="mockup-tabs">
                        <button class="tab-btn active" onclick="changeGraph(0, this)">Rendimiento Semanal</button>
                        <button class="tab-btn" onclick="changeGraph(1, this)">Hábitos Completados</button>
                        <button class="tab-btn" onclick="changeGraph(2, this)">Horas de Enfoque</button>
                    </div>
                    <div class="mockup-graph-container" id="graph-container">
                        </div>
                </div>
            </div>
        </section>
    </div>

    <div class="cta-section">
        <h2>Listo para el siguiente nivel.</h2>
        <p>Únete y comienza a estructurar tu sistema de vida hoy mismo.</p>
        <a href="registro.php" class="btn-white">Crear mi cuenta gratis</a>
    </div>

    <script>
        // Lógica simple para la gráfica interactiva de demostración
        const graphData = [
            [40, 60, 45, 80, 55, 90, 70], // Datos pestaña 1
            [20, 30, 80, 40, 100, 60, 50], // Datos pestaña 2
            [80, 70, 90, 60, 50, 85, 95]   // Datos pestaña 3
        ];
        const labels = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
        const container = document.getElementById('graph-container');

        function renderGraph(dataIndex) {
            container.innerHTML = '';
            const data = graphData[dataIndex];
            data.forEach((val, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'bar-wrapper';
                
                const bar = document.createElement('div');
                bar.className = 'bar ' + (val > 75 ? 'active' : '');
                // Animación de entrada
                setTimeout(() => { bar.style.height = val + '%'; }, 50);
                
                const label = document.createElement('div');
                label.className = 'bar-label';
                label.innerText = labels[index];

                wrapper.appendChild(bar);
                wrapper.appendChild(label);
                container.appendChild(wrapper);
            });
        }

        function changeGraph(index, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderGraph(index);
        }

        // Render inicial
        renderGraph(0);
    </script>
</body>
</html>