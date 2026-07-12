<?php
session_start();
// require '../db.php';

// ==========================================
// 1. CONFIGURACIÓN DINÁMICA DE LA RAMA
// Cambiando esto, el mismo archivo sirve para cualquier rama
// ==========================================
$branch = [
    'id' => 'estudio',
    'name' => 'Estudio',
    'icon' => '📚',
    'theme_color' => '#805AD5', // Morado para estudio. (Ej: #ecc94b para Finanzas)
    'description' => 'Gestión de aprendizaje, lógica y desarrollo técnico.'
];

// Ruta de navegación (Breadcrumbs - Simulación como Explorador de archivos)
$current_path = [
    ['name' => 'Estudio', 'url' => '?path=estudio'],
    ['name' => 'Desarrollo', 'url' => '?path=estudio/desarrollo'],
    ['name' => 'Web', 'url' => '#'] // Nivel actual
];

$user = ['username' => 'Daniel', 'profession' => 'Ingeniería de Sistemas', 'profile_picture' => ''];
$core_level = 7;
$core_max = 10;
$progress_percent = ($core_level / $core_max) * 100;

// Nodos de especialización más profundos (Simulación)
$specializations = [
    ['name' => 'React & Next.js', 'level' => 5, 'max' => 10, 'contribution' => '+15%'],
    ['name' => 'Node.js & APIs', 'level' => 3, 'max' => 10, 'contribution' => '+10%'],
    ['name' => 'Bases de Datos (SQL)', 'level' => 4, 'max' => 10, 'contribution' => '+12%'],
    ['name' => 'Arquitectura Cloud', 'level' => 1, 'max' => 10, 'contribution' => '+5%']
];

function renderAvatar($avatarData) {
    if (empty($avatarData)) return "<div class='avatar-circle' style='background: #E2E8F0; color: #4A5568;'>👤</div>";
    if (strpos($avatarData, '.') !== false) return "<div class='avatar-circle' style='background-image: url(\"{$avatarData}\"); background-size: cover;'></div>";
    return "<div class='avatar-circle' style='background: rgba(128, 90, 213, 0.1); color: #805AD5;'>{$avatarData}</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $branch['name'] ?> | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --accent: #805AD5; --accent-light: rgba(128, 90, 213, 0.1); --border-color: #E2E8F0; 
            --theme-color: <?= $branch['theme_color'] ?>; /* Inyección dinámica del color */
            --theme-light: <?= $branch['theme_color'] ?>1A; /* Versión transparente (Hex + Alpha) */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 20; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }

        /* Main Content */
        main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 40px; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        
        /* BREADCRUMBS (Explorador de Archivos) */
        .title-area h1 { font-size: 2rem; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; font-family: 'Orbitron', sans-serif; text-transform: uppercase; }
        .breadcrumb-icon { font-size: 2.2rem; margin-right: 5px; }
        .breadcrumb-link { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .breadcrumb-link:hover { color: var(--theme-color); }
        .breadcrumb-separator { color: var(--border-color); margin: 0 5px; font-weight: 300; }
        .breadcrumb-current { color: var(--theme-color); }
        .title-area p { color: var(--text-muted); font-size: 1.05rem; }
        
        .btn-return { background: var(--bg-panel); border: 2px solid var(--border-color); color: var(--text-main); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 0.9rem; }
        .btn-return:hover { border-color: var(--theme-color); color: var(--theme-color); background: var(--theme-light); }

        /* HORIZONTAL WINDOWS (Subniveles Administrables) */
        .sublevels-wrapper { margin-bottom: 30px; }
        .sublevels-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .sublevels-header h3 { font-size: 1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .sublevels-count { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; background: var(--border-color); padding: 3px 10px; border-radius: 12px; }
        
        .sublevels-container { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; }
        .sublevels-container::-webkit-scrollbar { height: 6px; }
        .sublevels-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
        
        .sublevel-tab { min-width: 180px; background: var(--bg-panel); border: 2px solid var(--border-color); border-radius: 12px; padding: 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: 0.2s; position: relative; }
        .sublevel-tab:hover { border-color: var(--theme-color); transform: translateY(-2px); box-shadow: 0 5px 15px var(--theme-light); }
        .sublevel-tab.active { border-color: var(--theme-color); background: var(--theme-light); }
        
        .sublevel-name { font-weight: 700; font-size: 0.95rem; color: var(--text-main); flex: 1; outline: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sublevel-name[contenteditable="true"] { border-bottom: 1px dashed var(--theme-color); cursor: text; }
        
        .sublevel-actions { display: flex; gap: 5px; opacity: 0; transition: 0.2s; }
        .sublevel-tab:hover .sublevel-actions { opacity: 1; }
        .btn-icon { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1rem; display: flex; align-items: center; justify-content: center; padding: 2px; }
        .btn-icon:hover { color: #E53E3E; }
        .btn-icon.edit:hover { color: var(--theme-color); }

        .btn-add-tab { min-width: 60px; border: 2px dashed var(--border-color); border-radius: 12px; background: transparent; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-add-tab:hover { border-color: var(--theme-color); color: var(--theme-color); background: var(--theme-light); }

        /* Progress Banner */
        .progress-banner { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; margin-bottom: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .progress-header h3 { font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .level-badge { background: var(--theme-light); color: var(--theme-color); padding: 5px 15px; border-radius: 20px; font-weight: 800; font-family: 'Orbitron', sans-serif; font-size: 1.1rem; }
        
        .progress-bar-bg { width: 100%; height: 12px; background: var(--border-color); border-radius: 10px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: var(--theme-color); border-radius: 10px; transition: width 0.5s ease; }

        /* Sub-skills Grid (Nodos de Especialización) */
        .section-title { margin-bottom: 20px; color: var(--text-muted); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 10px; }
        .section-title::after { content: ''; flex: 1; height: 1px; background: var(--border-color); }

        .skills-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .skill-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; transition: 0.3s; display: flex; flex-direction: column; gap: 15px; position: relative; overflow: hidden; }
        .skill-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--theme-color); opacity: 0; transition: 0.3s; }
        .skill-card:hover { border-color: var(--theme-color); box-shadow: 0 10px 25px var(--theme-light); transform: translateY(-3px); }
        .skill-card:hover::before { opacity: 1; }
        
        .skill-card-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .skill-title-block { flex: 1; }
        .skill-title-block h4 { font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 5px; }
        .contribution-badge { font-size: 0.75rem; font-weight: 700; color: var(--theme-color); background: var(--theme-light); padding: 3px 8px; border-radius: 6px; display: inline-block; }
        
        .skill-fraction { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--text-muted); }
        .skill-fraction span { color: var(--text-main); }
        
        .skill-actions { display: flex; gap: 10px; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--border-color); }
        .btn-upgrade { flex: 1; background: transparent; border: 2px solid var(--border-color); padding: 10px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; color: var(--text-main); font-size: 0.9rem; }
        .btn-upgrade:hover:not(:disabled) { background: var(--theme-color); color: white; border-color: var(--theme-color); }
        .btn-upgrade:disabled { opacity: 0.5; cursor: not-allowed; background: var(--border-color); color: var(--text-muted); }
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
                    <span class="breadcrumb-icon"><?= $branch['icon'] ?></span>
                    <?php 
                    // Generador del Breadcrumb (Explorador)
                    $total_paths = count($current_path);
                    foreach ($current_path as $index => $path): 
                        if ($index === $total_paths - 1): ?>
                            <span class="breadcrumb-current"><?= $path['name'] ?></span>
                        <?php else: ?>
                            <a href="<?= $path['url'] ?>" class="breadcrumb-link"><?= $path['name'] ?></a>
                            <span class="breadcrumb-separator">/</span>
                        <?php endif; 
                    endforeach; 
                    ?>
                </h1>
                <p><?= $branch['description'] ?></p>
            </div>
            <a href="../habilidades.php" class="btn-return">⮐ Volver a la Matriz</a>
        </div>

        <!-- SISTEMA DE SUBNIVELES (Ventanas Horizontales) -->
        <div class="sublevels-wrapper">
            <div class="sublevels-header">
                <h3>Subniveles de la Rama</h3>
                <span class="sublevels-count" id="tab-count">2/5</span>
            </div>
            <div class="sublevels-container" id="tabs-container">
                <!-- Las pestañas se renderizan vía JavaScript -->
            </div>
        </div>

        <div class="progress-banner">
            <div class="progress-header">
                <h3>Progreso del Núcleo Actual</h3>
                <div class="level-badge">NIVEL <?= $core_level ?> / <?= $core_max ?></div>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div>
            </div>
        </div>

        <h3 class="section-title">Nodos de Especialización Profunda</h3>
        
        <div class="skills-grid">
            <?php foreach($specializations as $skill): 
                $percent = ($skill['level'] / $skill['max']) * 100;
                $isMaxed = $skill['level'] >= $skill['max'];
            ?>
                <div class="skill-card">
                    <div class="skill-card-header">
                        <div class="skill-title-block">
                            <h4><?= $skill['name'] ?></h4>
                            <span class="contribution-badge">Aporte al núcleo: <?= $skill['contribution'] ?></span>
                        </div>
                        <div class="skill-fraction">
                            <span><?= $skill['level'] ?></span>/<?= $skill['max'] ?>
                        </div>
                    </div>
                    <div class="progress-bar-bg" style="height: 6px;">
                        <div class="progress-bar-fill" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <div class="skill-actions">
                        <button class="btn-upgrade" <?= $isMaxed ? 'disabled' : '' ?>>
                            <?= $isMaxed ? 'Maximizada' : 'Invertir Puntos' ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // ==========================================
        // LÓGICA DE GESTIÓN DE SUBNIVELES (Pestañas)
        // ==========================================
        const MAX_TABS = 5;
        let tabs = [
            { id: 1, name: 'Frontend', isDefault: true, active: true },
            { id: 2, name: 'Backend', isDefault: true, active: false }
        ];

        const container = document.getElementById('tabs-container');
        const countIndicator = document.getElementById('tab-count');

        function renderTabs() {
            container.innerHTML = '';
            
            tabs.forEach((tab, index) => {
                const tabEl = document.createElement('div');
                tabEl.className = `sublevel-tab ${tab.active ? 'active' : ''}`;
                tabEl.onclick = (e) => {
                    // Evitar que el click en editar o borrar active la pestaña
                    if (!e.target.closest('.sublevel-actions') && e.target.tagName !== 'SPAN') {
                        setActive(tab.id);
                    }
                };

                const nameSpan = document.createElement('span');
                nameSpan.className = 'sublevel-name';
                nameSpan.innerText = tab.name;
                nameSpan.onblur = () => finishEdit(tab.id, nameSpan.innerText);
                nameSpan.onkeydown = (e) => { if (e.key === 'Enter') nameSpan.blur(); };

                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'sublevel-actions';

                // Botón Editar (Disponible para todas, incuso las default)
                const editBtn = document.createElement('button');
                editBtn.className = 'btn-icon edit';
                editBtn.innerHTML = '✎';
                editBtn.title = 'Editar nombre';
                editBtn.onclick = (e) => { e.stopPropagation(); startEdit(nameSpan); };
                actionsDiv.appendChild(editBtn);

                // Botón Borrar (Solo para las que no son por defecto)
                if (!tab.isDefault) {
                    const delBtn = document.createElement('button');
                    delBtn.className = 'btn-icon';
                    delBtn.innerHTML = '✕';
                    delBtn.title = 'Eliminar subnivel';
                    delBtn.onclick = (e) => { e.stopPropagation(); deleteTab(tab.id); };
                    actionsDiv.appendChild(delBtn);
                }

                tabEl.appendChild(nameSpan);
                tabEl.appendChild(actionsDiv);
                container.appendChild(tabEl);
            });

            // Botón Agregar
            if (tabs.length < MAX_TABS) {
                const addBtn = document.createElement('button');
                addBtn.className = 'btn-add-tab';
                addBtn.innerHTML = '+';
                addBtn.title = 'Añadir nuevo subnivel';
                addBtn.onclick = addTab;
                container.appendChild(addBtn);
            }

            countIndicator.innerText = `${tabs.length}/${MAX_TABS}`;
        }

        function setActive(id) {
            tabs.forEach(t => t.active = (t.id === id));
            renderTabs();
            // Aquí podrías hacer una llamada AJAX para cargar el contenido de ese subnivel
        }

        function addTab() {
            if (tabs.length >= MAX_TABS) return;
            const newId = Date.now();
            tabs.push({ id: newId, name: 'Nuevo Subnivel', isDefault: false, active: false });
            setActive(newId);
            
            // Foco automático en edición tras agregarlo
            setTimeout(() => {
                const newTabSpan = container.querySelector('.active .sublevel-name');
                if(newTabSpan) startEdit(newTabSpan);
            }, 50);
        }

        function deleteTab(id) {
            if (confirm("¿Estás seguro de eliminar este subnivel?")) {
                const index = tabs.findIndex(t => t.id === id);
                tabs = tabs.filter(t => t.id !== id);
                if (tabs.length > 0 && index > 0) setActive(tabs[index-1].id);
                else if (tabs.length > 0) setActive(tabs[0].id);
                else renderTabs();
            }
        }

        function startEdit(spanElement) {
            spanElement.setAttribute('contenteditable', 'true');
            spanElement.focus();
            // Seleccionar todo el texto
            document.execCommand('selectAll', false, null);
        }

        function finishEdit(id, newName) {
            const tab = tabs.find(t => t.id === id);
            if (tab) {
                tab.name = newName.trim() === '' ? 'Subnivel sin nombre' : newName.trim();
            }
            renderTabs();
            // Aquí guardarías el nuevo nombre en la BD
        }

        // Inicializar render
        renderTabs();
    </script>
</body>
</html>