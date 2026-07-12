<?php
session_start();
require '../db.php'; // Asegúrate de que la ruta a tu BD sea correcta

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

// Datos del nodo actual
$current_node = $catalog[$current_skill_key] ?? null;

// Si el nodo no existe, redirigir a la matriz
if (!$current_node) {
    header("Location: ../habilidades.php");
    exit;
}

// Configuración de tema basada en la rama principal del nodo
$branch_name = $current_node['branch'];
$theme = $branch_themes[$branch_name] ?? $branch_themes['base'];
$theme_color = $theme['color'];
$branch_icon = $theme['icon'];

// 4. CONSTRUIR BREADCRUMBS (Ruta del explorador)
$current_path = [];
$temp_key = $current_skill_key;

while ($temp_key != null && isset($catalog[$temp_key])) {
    $node_info = $catalog[$temp_key];
    // Añadimos al inicio del array para que quede en orden: Abuelo > Padre > Hijo
    array_unshift($current_path, [
        'name' => $node_info['label'],
        'url' => "rama.php?skill=" . $node_info['node_key']
    ]);
    $temp_key = $node_info['parent_key'];
}

// 5. OBTENER PROGRESO DEL USUARIO EN ESTE NODO
$stmt = $pdo->prepare("SELECT current_level FROM user_skills WHERE user_id = ? AND node_key = ?");
$stmt->execute([$user_id, $current_skill_key]);
$userProgress = $stmt->fetch(PDO::FETCH_ASSOC);

$core_level = $userProgress ? floor($userProgress['current_level']) : 0;
$core_max = $current_node['max_level'];
$progress_percent = ($core_max > 0) ? ($core_level / $core_max) * 100 : 0;

// 6. OBTENER LOS SUBNIVELES (Hijos de este nodo) PARA LAS PESTAÑAS
$stmt = $pdo->prepare("SELECT node_key, label FROM skills_catalog WHERE parent_key = ? ORDER BY label ASC");
$stmt->execute([$current_skill_key]);
$sublevels_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Simulación de datos de usuario para el sidebar
$user = [
    'username' => 'Daniel',
    'profession' => 'Ingeniería de Sistemas',
    'profile_picture' => ''
];

function renderAvatar($avatarData) {
    if (empty($avatarData)) return "<div class='avatar-circle' style='background: #E2E8F0; color: #4A5568;'>👤</div>";
    if (strpos($avatarData, '.') !== false) return "<div class='avatar-circle' style='background-image: url(\"{$avatarData}\"); background-size: cover;'></div>";
    return "<div class='avatar-circle' style='background: rgba(128, 90, 213, 0.1); color: var(--theme-color);'>{$avatarData}</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        /* === SIDEBAR (Restaurado) === */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0;}
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--theme-color); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--theme-light); color: var(--theme-color); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }

        /* === MAIN CONTENT === */
        main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 40px; }
        
        /* BREADCRUMBS */
        .header-dash { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .title-area h1 { font-size: 2rem; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; font-family: 'Orbitron', sans-serif; text-transform: uppercase; }
        .breadcrumb-icon { font-size: 2.2rem; margin-right: 5px; }
        .breadcrumb-link { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .breadcrumb-link:hover { color: var(--theme-color); }
        .breadcrumb-separator { color: var(--border-color); margin: 0 5px; font-weight: 300; }
        .breadcrumb-current { color: var(--theme-color); }
        
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 0.9rem; }
        .btn-return:hover { border-color: var(--theme-color); color: var(--theme-color); background: var(--theme-light); }

        /* WINDOWS / TABS (Subniveles) */
        .sublevels-wrapper { margin-bottom: 30px; }
        .sublevels-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .sublevels-header h3 { font-size: 1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .sublevels-count { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; background: var(--border-color); padding: 3px 10px; border-radius: 12px; }
        
        .sublevels-container { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; }
        .sublevels-container::-webkit-scrollbar { height: 6px; }
        .sublevels-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
        
        .sublevel-tab { min-width: 180px; background: var(--bg-panel); border: 2px solid var(--border-color); border-radius: 12px; padding: 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .sublevel-tab:hover { border-color: var(--theme-color); transform: translateY(-2px); box-shadow: 0 5px 15px var(--theme-light); }
        .sublevel-tab.active { border-color: var(--theme-color); background: var(--theme-light); }
        .sublevel-name { font-weight: 700; font-size: 0.95rem; color: var(--text-main); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .btn-add-tab { min-width: 60px; border: 2px dashed var(--border-color); border-radius: 12px; background: transparent; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-add-tab:hover { border-color: var(--theme-color); color: var(--theme-color); background: var(--theme-light); }

        /* PROGRESS PANEL */
        .progress-master-panel { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); text-align: center; margin-bottom: 30px; }
        .progress-title { font-size: 1.2rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; }
        .level-display { font-size: 4rem; font-family: 'Orbitron', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 20px; }
        .level-display span { color: var(--theme-color); font-size: 2rem; }
        
        .progress-bar-bg { width: 100%; height: 16px; background: var(--border-color); border-radius: 20px; overflow: hidden; margin-bottom: 40px; }
        .progress-bar-fill { height: 100%; background: var(--theme-color); border-radius: 20px; transition: width 1s ease-in-out; }

        /* ACTION BUTTONS */
        .action-buttons-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .action-btn { background: var(--bg-base); border: 2px solid var(--border-color); border-radius: 12px; padding: 20px; text-decoration: none; color: var(--text-main); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; transition: 0.3s; cursor: pointer; }
        .action-btn:hover { border-color: var(--theme-color); background: var(--theme-light); transform: translateY(-3px); }
        .action-btn .icon { font-size: 2rem; }
        .action-btn .title { font-weight: 700; font-size: 1.1rem; }
        .action-btn .desc { font-size: 0.8rem; color: var(--text-muted); text-align: center; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="../dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="../estado-humano.php" class="nav-link">👤 Estado Humano</a>
            <a href="../registro-diario.php" class="nav-link">⏱ Registro Diario</a>
            <a href="../proyectos.php" class="nav-link">🚀 Proyectos</a>
            <a href="../habilidades.php" class="nav-link active">🌳 Árbol de Habilidades</a>
        </div>
        <div class="user-mini">
            <?= renderAvatar($user['profile_picture']) ?>
            <div class="user-info-mini">
                <h4><?= htmlspecialchars($user['username']) ?></h4>
                <p><?= htmlspecialchars($user['profession']) ?></p>
            </div>
        </div>
    </nav>

    <main>
        <div class="header-dash">
            <div class="title-area">
                <h1>
                    <span class="breadcrumb-icon"><?= $branch_icon ?></span>
                    <?php 
                    $total_paths = count($current_path);
                    foreach ($current_path as $index => $path): 
                        if ($index === $total_paths - 1): ?>
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
                <h3>Subniveles de <?= htmlspecialchars($current_node['label']) ?></h3>
                <span class="sublevels-count" id="tab-count"><?= count($sublevels_db) ?>/5</span>
            </div>
            <div class="sublevels-container" id="tabs-container">
                <?php foreach($sublevels_db as $sublevel): ?>
                    <a href="rama.php?skill=<?= urlencode($sublevel['node_key']) ?>" class="sublevel-tab">
                        <span class="sublevel-name"><?= htmlspecialchars($sublevel['label']) ?></span>
                    </a>
                <?php endforeach; ?>
                
                <?php if(count($sublevels_db) < 5): ?>
                    <button class="btn-add-tab" title="Añadir nuevo subnivel (Próximamente)" onclick="alert('La adición dinámica requerirá un formulario conectado a INSERT INTO skills_catalog.')">+</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="progress-master-panel">
            <div class="progress-title">Progreso del Núcleo Actual</div>
            <div class="level-display">
                <?= $core_level ?> <span>/ <?= $core_max ?></span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div>
            </div>

            <div class="action-buttons-grid">
                <a href="aumentar.php?skill=<?= urlencode($current_skill_key) ?>" class="action-btn">
                    <span class="icon">📈</span>
                    <span class="title">Aumentar</span>
                    <span class="desc">Gestionar nodos de especialización e invertir puntos.</span>
                </a>

                <a href="ruta_potencia.php?skill=<?= urlencode($current_skill_key) ?>" class="action-btn">
                    <span class="icon">⚡</span>
                    <span class="title">Ruta de Potencia</span>
                    <span class="desc">Visualizar gráfica, agregar metas y notas estratégicas.</span>
                </a>

                <a href="presentacion.php?skill=<?= urlencode($current_skill_key) ?>" class="action-btn">
                    <span class="icon">📊</span>
                    <span class="title">Presentación</span>
                    <span class="desc">Comparativa del nivel actual frente al potencial máximo.</span>
                </a>
            </div>
        </div>
    </main>

</body>
</html>