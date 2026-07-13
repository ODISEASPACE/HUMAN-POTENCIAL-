<?php
session_start();
require '../db.php'; 

// 1. OBTENER EL NODO ACTUAL DESDE LA URL
$current_skill_key = $_GET['skill'] ?? 'estudio'; 
$user_id = $_SESSION['user_id'] ?? 1;

// 2. CONFIGURACIÓN VISUAL BASE POR RAMA
$branch_themes = [
    'estudio'  => ['color' => '#805AD5', 'icon' => '📚'],
    'laboral'  => ['color' => '#3182CE', 'icon' => '💼'],
    'finanzas' => ['color' => '#D69E2E', 'icon' => '💰'],
    'salud'    => ['color' => '#38A169', 'icon' => '🍎'],
    'espiritu' => ['color' => '#D53F8C', 'icon' => '🧘'],
    'base'     => ['color' => '#4A5568', 'icon' => '💠']
];

// 3. OBTENER TODO EL CATÁLOGO PARA CONSTRUIR RUTAS Y DATOS
$stmt = $pdo->query("SELECT * FROM skills_catalog");
$catalog_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catalog = [];
foreach ($catalog_raw as $row) {
    $catalog[$row['node_key']] = $row;
}

$current_node = $catalog[$current_skill_key] ?? null;

if (!$current_node) {
    header("Location: ../habilidades.php");
    exit;
}

$branch_name = $current_node['branch'];
$theme = $branch_themes[$branch_name] ?? $branch_themes['base'];
$theme_color = $theme['color'];
$branch_icon = $theme['icon'];

// 4. CONSTRUIR BREADCRUMBS (Ruta del explorador)
$full_path = [];
$temp_key = $current_skill_key;

while ($temp_key != null && isset($catalog[$temp_key])) {
    $node_info = $catalog[$temp_key];
    array_unshift($full_path, [
        'name' => $node_info['label'],
        'url' => "rama.php?skill=" . $node_info['node_key']
    ]);
    $temp_key = $node_info['parent_key'];
}

// Lógica de Acortamiento del Explorador ("... / Padre / Actual")
$display_path = [];
$total_paths = count($full_path);

if ($total_paths > 3) {
    // Si es muy larga, mostramos: [Origen] / ... / [Padre] / [Actual]
    $display_path[] = $full_path[0]; // La raíz
    $display_path[] = [
        'name' => '...', 
        'url' => $full_path[$total_paths - 3]['url'] // El '...' lleva al abuelo
    ];
    $display_path[] = $full_path[$total_paths - 2]; // El padre
    $display_path[] = $full_path[$total_paths - 1]; // El nodo actual
} else {
    $display_path = $full_path;
}

// 5. OBTENER PROGRESO DEL USUARIO
$stmt = $pdo->prepare("SELECT current_level FROM user_skills WHERE user_id = ? AND node_key = ?");
$stmt->execute([$user_id, $current_skill_key]);
$userProgress = $stmt->fetch(PDO::FETCH_ASSOC);

$core_level = $userProgress ? floor($userProgress['current_level']) : 0;
$core_max = $current_node['max_level'];
$progress_percent = ($core_max > 0) ? ($core_level / $core_max) * 100 : 0;

// 6. OBTENER LOS SUBNIVELES
$stmt = $pdo->prepare("SELECT node_key, label FROM skills_catalog WHERE parent_key = ? ORDER BY label ASC");
$stmt->execute([$current_skill_key]);
$sublevels_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = ['username' => 'Daniel', 'profession' => 'Ingeniería de Sistemas', 'profile_picture' => ''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($current_node['label']) ?> | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --border-color: #E2E8F0; 
            --theme-color: <?= $theme_color ?>; 
            --theme-light: <?= $theme_color ?>1A; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0;}
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--theme-color); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--theme-light); color: var(--theme-color); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }

        main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 40px; position: relative; }
        
        /* BREADCRUMBS OPTIMIZADOS */
        .header-dash { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        /* Fuente reducida de 2rem a 1.4rem para aguantar rutas largas */
        .title-area h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; font-family: 'Orbitron', sans-serif; text-transform: uppercase; white-space: nowrap;}
        .breadcrumb-icon { font-size: 1.8rem; margin-right: 5px; }
        .breadcrumb-link { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .breadcrumb-link:hover { color: var(--theme-color); }
        .breadcrumb-separator { color: var(--border-color); margin: 0 5px; font-weight: 300; }
        .breadcrumb-current { color: var(--theme-color); }
        
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 8px 15px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 0.85rem; white-space: nowrap; }
        .btn-return:hover { border-color: var(--theme-color); color: var(--theme-color); background: var(--theme-light); }

        /* WINDOWS / TABS (Subniveles) */
        .sublevels-wrapper { margin-bottom: 30px; }
        .sublevels-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .sublevels-header h3 { font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        
        .sublevels-container { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
        .sublevel-tab { padding: 12px 20px; background: var(--bg-panel); border: 2px solid var(--border-color); border-radius: 12px; font-weight: 700; font-size: 0.9rem; color: var(--text-main); text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .sublevel-tab:hover { border-color: var(--theme-color); transform: translateY(-2px); box-shadow: 0 5px 15px var(--theme-light); }
        
        /* PANEL CENTRAL Y BOTONES */
        .progress-master-panel { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); text-align: center; margin-bottom: 30px; }
        .progress-title { font-size: 1.1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; }
        .level-display { font-size: 4rem; font-family: 'Orbitron', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 20px; }
        .level-display span { color: var(--theme-color); font-size: 2rem; }
        .progress-bar-bg { width: 100%; height: 16px; background: var(--border-color); border-radius: 20px; overflow: hidden; margin-bottom: 40px; }
        .progress-bar-fill { height: 100%; background: var(--theme-color); border-radius: 20px; transition: width 1s ease-in-out; }

        .action-buttons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .action-btn { background: var(--bg-base); border: 2px solid var(--border-color); border-radius: 12px; padding: 20px; color: var(--text-main); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; transition: 0.3s; cursor: pointer; text-decoration: none; }
        .action-btn:hover { border-color: var(--theme-color); background: var(--theme-light); transform: translateY(-3px); }
        .action-btn .icon { font-size: 2rem; }
        .action-btn .title { font-weight: 700; font-size: 1.05rem; }
        .action-btn .desc { font-size: 0.75rem; color: var(--text-muted); text-align: center; }

        /* ==========================================
           SISTEMA DE VENTANAS MODALES (NUEVO)
           ========================================== */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(10, 10, 15, 0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000; opacity: 0; pointer-events: none; transition: 0.3s ease;
        }
        .modal-overlay.active { opacity: 1; pointer-events: all; }
        
        .modal-window {
            background: var(--bg-panel); border: 1px solid var(--border-color);
            border-radius: 16px; width: 90%; max-width: 800px; max-height: 85vh;
            display: flex; flex-direction: column; overflow: hidden;
            transform: translateY(20px); transition: 0.3s ease;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .modal-overlay.active .modal-window { transform: translateY(0); }
        
        .modal-header {
            padding: 20px 25px; border-bottom: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg-base);
        }
        .modal-header h3 { font-size: 1.2rem; font-weight: 700; color: var(--text-main); margin: 0; }
        .modal-close {
            background: none; border: none; font-size: 1.5rem; color: var(--text-muted);
            cursor: pointer; transition: 0.2s;
        }
        .modal-close:hover { color: #E53E3E; }
        
        .modal-body { padding: 30px; overflow-y: auto; flex: 1; }
        
        /* Spinner de carga */
        .loader {
            border: 4px solid var(--border-color); border-top: 4px solid var(--theme-color);
            border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;
            margin: 40px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="../dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="../habilidades.php" class="nav-link active">🌳 Árbol de Habilidades</a>
        </div>
        <div class="user-mini">
            <div style="width: 40px; height: 40px; background: #E2E8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">👤</div>
            <div><h4>Daniel</h4><p>Ingeniería de Sistemas</p></div>
        </div>
    </nav>

    <main>
        <div class="header-dash">
            <div class="title-area">
                <h1>
                    <span class="breadcrumb-icon"><?= $branch_icon ?></span>
                    <?php 
                    $total_display = count($display_path);
                    foreach ($display_path as $index => $path): 
                        if ($index === $total_display - 1): ?>
                            <span class="breadcrumb-current"><?= htmlspecialchars($path['name']) ?></span>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($path['url']) ?>" class="breadcrumb-link"><?= htmlspecialchars($path['name']) ?></a>
                            <span class="breadcrumb-separator">/</span>
                        <?php endif; 
                    endforeach; 
                    ?>
                </h1>
            </div>
            <a href="../habilidades.php" class="btn-return">⮐ Volver a la Matriz</a>
        </div>

        <div class="sublevels-wrapper">
            <div class="sublevels-header">
                <h3>Subniveles Abiertos</h3>
            </div>
            <div class="sublevels-container">
                <?php foreach($sublevels_db as $sublevel): ?>
                    <a href="rama.php?skill=<?= urlencode($sublevel['node_key']) ?>" class="sublevel-tab">
                        <?= htmlspecialchars($sublevel['label']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if(empty($sublevels_db)): ?>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">No hay subniveles en este punto.</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="progress-master-panel">
            <div class="progress-title">Progreso del Núcleo Actual</div>
            <div class="level-display"><?= $core_level ?> <span>/ <?= $core_max ?></span></div>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div></div>

            <div class="action-buttons-grid">
                <button onclick="openModal('aumentar.php?skill=<?= urlencode($current_skill_key) ?>', 'Aumentar Nivel')" class="action-btn">
                    <span class="icon">📈</span><span class="title">Aumentar</span>
                    <span class="desc">Gestionar nodos de especialización e invertir puntos.</span>
                </button>

                <button onclick="openModal('ruta_potencia.php?skill=<?= urlencode($current_skill_key) ?>', 'Ruta de Potencia')" class="action-btn">
                    <span class="icon">⚡</span><span class="title">Ruta de Potencia</span>
                    <span class="desc">Visualizar gráfica, agregar metas y notas estratégicas.</span>
                </button>

                <button onclick="openModal('presentacion.php?skill=<?= urlencode($current_skill_key) ?>', 'Presentación')" class="action-btn">
                    <span class="icon">📊</span><span class="title">Presentación</span>
                    <span class="desc">Comparativa del nivel actual frente al potencial máximo.</span>
                </button>
            </div>
        </div>

        <div class="modal-overlay" id="mainModal" onclick="closeModal(event)">
            <div class="modal-window" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3 id="modalTitle">Título Ventana</h3>
                    <button class="modal-close" onclick="closeModal(true)">&times;</button>
                </div>
                <div class="modal-body" id="modalBody">
                    </div>
            </div>
        </div>

    </main>

    <script>
        // MOTOR DE VENTANAS MODALES
        const modal = document.getElementById('mainModal');
        const modalBody = document.getElementById('modalBody');
        const modalTitle = document.getElementById('modalTitle');
        
        let progresoModificado = false; // Rastrea si se invirtieron puntos

        // Esta función es activada desde aumentar.php
        function actualizarMasterBar() {
            progresoModificado = true;
        }

        function openModal(url, title) {
            modalTitle.innerText = title;
            modalBody.innerHTML = '<div class="loader"></div><p style="text-align:center; color: var(--text-muted);">Cargando módulo...</p>';
            modal.classList.add('active');
            progresoModificado = false; // Reiniciamos el estado al abrir

            fetch(url)
                .then(response => {
                    if(!response.ok) throw new Error("Error al cargar el módulo");
                    return response.text();
                })
                .then(html => {
                    modalBody.innerHTML = html;
                    const scripts = modalBody.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.body.appendChild(newScript);
                        document.body.removeChild(newScript);
                    });
                })
                .catch(err => {
                    modalBody.innerHTML = `<p style="color: #E53E3E; text-align:center;">Error al cargar. Asegúrate de que el archivo existe.</p>`;
                });
        }

        function closeModal(force = false) {
            if (force || event.target === modal) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modalBody.innerHTML = '';
                    // Si se invirtieron puntos, recargamos la página principal
                    if (progresoModificado) {
                        window.location.reload();
                    }
                }, 300);
            }
        }
    </script>
</body>
</html>