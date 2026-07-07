<?php
session_start();
require 'db.php';

// Verificación de seguridad: Si no hay sesión, expulsar al index
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Cargar los datos del usuario activo
$stmt = $pdo->prepare("SELECT username, email, profile_picture, profession, bio FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Función rápida para determinar si mostrar un emoji o una imagen de ruta
function renderAvatar($avatarData) {
    if (empty($avatarData)) {
        return "<div class='avatar-circle' style='background: #E2E8F0; color: #4A5568;'>👤</div>";
    }
    // Si tiene un punto, asumimos que es un archivo (ej. .jpg, .png)
    if (strpos($avatarData, '.') !== false) {
        return "<div class='avatar-circle' style='background-image: url(\"{$avatarData}\"); background-size: cover;'></div>";
    }
    // Si no, es un emoji
    return "<div class='avatar-circle' style='background: rgba(128, 90, 213, 0.1); color: #805AD5;'>{$avatarData}</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | APH OS</title>
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

        /* --- SIDEBAR MINIMALISTA --- */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 10; }
        .brand { text-align: center; margin-bottom: 40px; }
        .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        
        /* User Profile Mini */
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout { margin-top: 15px; text-align: center; font-size: 0.85rem; color: #E53E3E; text-decoration: none; font-weight: 600; padding: 8px; border-radius: 6px; }
        .btn-logout:hover { background: #FED7D7; }

        /* --- MAIN DASHBOARD --- */
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header-dash { margin-bottom: 40px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }

        /* --- GRID PRINCIPAL DE TARJETAS --- */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); display: flex; flex-direction: column; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .card-header h3 { font-size: 1.2rem; font-weight: 700; }
        
        /* --- TARJETA 1: GRÁFICOS ROTATIVOS --- */
        .graph-carousel { position: relative; height: 280px; width: 100%; overflow: hidden; }
        .graph-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease; display: flex; flex-direction: column; justify-content: flex-end; }
        .graph-slide.active { opacity: 1; z-index: 2; }
        
        .graph-title { position: absolute; top: 0; left: 0; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); }
        .bars-container { display: flex; align-items: flex-end; justify-content: space-between; height: 200px; gap: 15px; width: 100%; padding-top: 30px; border-bottom: 2px solid var(--border-color); }
        .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 10px; }
        .bar-fill { width: 100%; background: var(--accent-light); border-radius: 6px 6px 0 0; position: relative; transition: height 1s ease; }
        .bar-fill.highlight { background: var(--accent); }
        .bar-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); }

        /* --- TARJETA 2: WIDGETS INTERACTIVOS --- */
        .widget-controls { display: flex; gap: 10px; margin-bottom: 20px; background: var(--bg-base); padding: 5px; border-radius: 10px; border: 1px solid var(--border-color); }
        .widget-btn { flex: 1; background: transparent; border: none; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; transition: 0.3s; }
        .widget-btn:hover { color: var(--text-main); }
        .widget-btn.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .widget-content-area { flex: 1; position: relative; }
        .widget-panel { display: none; height: 100%; animation: fadeIn 0.4s ease forwards; }
        .widget-panel.active { display: block; }
        
        /* Estilos internos de los widgets basados en el Excel */
        .metric-list { list-style: none; display: flex; flex-direction: column; gap: 15px; }
        .metric-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--border-color); border-radius: 10px; }
        .metric-info h4 { font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; }
        .metric-info p { font-size: 0.8rem; color: var(--text-muted); }
        .metric-status { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: var(--accent-light); color: var(--accent); }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Responsividad */
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <h2>A P H</h2>
        </div>
        <div class="nav-links">
            <a href="#" class="nav-link active">⌂ Panel Central</a>
            <a href="#" class="nav-link">⏱ Hábitos</a>
            <a href="#" class="nav-link">🧠 Expansión Cognitiva</a>
            <a href="#" class="nav-link">⛁ Repositorio</a>
        </div>
        
        <div class="user-mini">
            <?= renderAvatar($user['profile_picture']) ?>
            <div class="user-info-mini">
                <h4><?= htmlspecialchars($user['username'] ?? 'Usuario') ?></h4>
                <p><?= htmlspecialchars($user['profession'] ?? 'Sin asignar') ?></p>
            </div>
        </div>
        <a href="index.php" class="btn-logout" onclick="/* Idealmente redirigir a un script de logout */">Cerrar Sesión</a>
    </nav>

    <main>
        <div class="header-dash">
            <h1>Sistemas Activos</h1>
            <p>Resumen de rendimiento y módulos de desarrollo.</p>
        </div>

        <div class="dashboard-grid">
            
            <div class="card">
                <div class="card-header">
                    <h3>Métricas Generales</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Actualización 5s</span>
                </div>
                
                <div class="graph-carousel">
                    <div class="graph-slide active" id="slide-0">
                        <div class="graph-title">Medición de Desempeño (Productividad)</div>
                        <div class="bars-container">
                            <div class="bar-col"><div class="bar-fill" style="height: 40%;"></div><span class="bar-label">Lun</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 65%;"></div><span class="bar-label">Mar</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 35%;"></div><span class="bar-label">Mié</span></div>
                            <div class="bar-col"><div class="bar-fill highlight" style="height: 90%;"></div><span class="bar-label">Jue</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 50%;"></div><span class="bar-label">Vie</span></div>
                        </div>
                    </div>
                    <div class="graph-slide" id="slide-1">
                        <div class="graph-title">Estado de Salud y Recuperación (Soma)</div>
                        <div class="bars-container">
                            <div class="bar-col"><div class="bar-fill" style="height: 80%;"></div><span class="bar-label">S1</span></div>
                            <div class="bar-col"><div class="bar-fill highlight" style="height: 85%;"></div><span class="bar-label">S2</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 60%;"></div><span class="bar-label">S3</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 75%;"></div><span class="bar-label">S4</span></div>
                            <div class="bar-col"><div class="bar-fill highlight" style="height: 95%;"></div><span class="bar-label">S5</span></div>
                        </div>
                    </div>
                    <div class="graph-slide" id="slide-2">
                        <div class="graph-title">Regulación Hormonal / Pathos</div>
                        <div class="bars-container">
                            <div class="bar-col"><div class="bar-fill highlight" style="height: 70%;"></div><span class="bar-label">D1</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 40%;"></div><span class="bar-label">D2</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 55%;"></div><span class="bar-label">D3</span></div>
                            <div class="bar-col"><div class="bar-fill" style="height: 65%;"></div><span class="bar-label">D4</span></div>
                            <div class="bar-col"><div class="bar-fill highlight" style="height: 80%;"></div><span class="bar-label">D5</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>SubContenido APH</h3>
                </div>
                
                <div class="widget-controls">
                    <button class="widget-btn active" onclick="showWidget(0)">Estado Humano</button>
                    <button class="widget-btn" onclick="showWidget(1)">Registro Diario</button>
                    <button class="widget-btn" onclick="showWidget(2)">Proyectos</button>
                </div>

                <div class="widget-content-area">
                    <div class="widget-panel active" id="widget-0">
                        <ul class="metric-list">
                            <li class="metric-item">
                                <div class="metric-info"><h4>Virtudes</h4><p>Desarrollo Psique y Pathos</p></div>
                                <div class="metric-status">En balance</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Capacidades</h4><p>Expansión Cognitiva</p></div>
                                <div class="metric-status" style="background:#C6F6D5; color:#276749;">Óptimo</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Metas</h4><p>Objetivos a corto/mediano plazo</p></div>
                                <div class="metric-status" style="background:#FEEBC8; color:#C05621;">En progreso</div>
                            </li>
                        </ul>
                    </div>

                    <div class="widget-panel" id="widget-1">
                        <ul class="metric-list">
                            <li class="metric-item">
                                <div class="metric-info"><h4>Rutinas y Eventos</h4><p>Gestión del tiempo</p></div>
                                <div class="metric-status">Completado</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Estado de Ánimo</h4><p>Inteligencia Emocional</p></div>
                                <div class="metric-status">Estable</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Estado de Salud y Económico</h4><p>Soma y Finanzas</p></div>
                                <div class="metric-status">Requiere atención</div>
                            </li>
                        </ul>
                    </div>

                    <div class="widget-panel" id="widget-2">
                        <ul class="metric-list">
                            <li class="metric-item">
                                <div class="metric-info"><h4>Fuentes de Estudio</h4><p>Recolección de datos</p></div>
                                <div class="metric-status">2 Activas</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Logros</h4><p>Hitos alcanzados</p></div>
                                <div class="metric-status" style="background:#C6F6D5; color:#276749;">+3 este mes</div>
                            </li>
                            <li class="metric-item">
                                <div class="metric-info"><h4>Líneas de Tiempo</h4><p>Proyección de desarrollo</p></div>
                                <div class="metric-status">Actualizado</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Lógica de Rotación Automática de Gráficos (Cada 5 Segundos)
        let currentSlide = 0;
        const totalSlides = 3;

        setInterval(() => {
            // Ocultar la actual
            document.getElementById(`slide-${currentSlide}`).classList.remove('active');
            
            // Calcular la siguiente
            currentSlide = (currentSlide + 1) % totalSlides;
            
            // Mostrar la nueva
            document.getElementById(`slide-${currentSlide}`).classList.add('active');
        }, 5000); // 5000ms = 5 segundos

        // Lógica de Selección de Widgets
        function showWidget(index) {
            // Limpiar botones
            document.querySelectorAll('.widget-btn').forEach(btn => btn.classList.remove('active'));
            // Limpiar paneles
            document.querySelectorAll('.widget-panel').forEach(panel => panel.classList.remove('active'));
            
            // Activar seleccionados (usamos el evento actual para el botón)
            event.target.classList.add('active');
            document.getElementById(`widget-${index}`).classList.add('active');
        }
    </script>
</body>
</html>