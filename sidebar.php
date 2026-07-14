<?php
// Aseguramos que la función renderAvatar no se declare múltiples veces
if (!function_exists('renderAvatar')) {
    function renderAvatar($avatarData) {
        if (empty($avatarData)) return "<div class='avatar-circle' style='background: #E2E8F0; color: #4A5568;'>👤</div>";
        if (strpos($avatarData, '.') !== false) return "<div class='avatar-circle' style='background-image: url(\"{$avatarData}\"); background-size: cover;'></div>";
        return "<div class='avatar-circle' style='background: rgba(128, 90, 213, 0.1); color: #805AD5;'>{$avatarData}</div>";
    }
}

// Si $user no está definido por el archivo padre, lo buscamos dinámicamente incluyendo el arquetipo
if (!isset($user) && isset($pdo, $_SESSION['user_id'])) {
    // Añadimos 'archetype' a la consulta. COALESCE asegura que si es nulo, actúe como 1 (Vagabundo)
    $stmtUser = $pdo->prepare("SELECT username, profile_picture, profession, COALESCE(archetype, 1) as archetype FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
}

// LÓGICA DE RUTAS Y PÁGINA ACTUAL
$current_page = basename($_SERVER['PHP_SELF']);
$is_skills = ($current_page == 'habilidades.php' || $current_page == 'rama.php');
$base_path = ($current_page == 'rama.php') ? '../' : '';

// LÓGICA DE DESBLOQUEO POR ARQUETIPOS
// 1 = Vagabundo | 2 = Soñador | 3 = Soldado | 4 = Ejecutor
$archetype = (int)($user['archetype'] ?? 1);

// Definimos qué puede ver según su nivel (el nivel superior hereda todo lo del inferior)
$show_dashboard  = true; // Nivel 1+
$show_registro   = true; // Nivel 1+
$show_estado     = ($archetype >= 2); // Nivel 2+
$show_proyectos  = ($archetype >= 3); // Nivel 3+
$show_arbol_dec  = ($archetype >= 4); // Nivel 4
$show_habilidades= ($archetype >= 4); // Nivel 4 (Lo agrupo con Ejecutor por su complejidad)
?>
<style>
    nav.sidebar { width: 260px; background: var(--bg-panel, #FFFFFF); border-right: 1px solid var(--border-color, #E2E8F0); display: flex; flex-direction: column; padding: 30px 20px; z-index: 100; flex-shrink: 0; }
    .brand { text-align: center; margin-bottom: 40px; } 
    .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent, #805AD5); }
    
    .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
    .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted, #718096); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
    .nav-link:hover, .nav-link.active { background: var(--accent-light, rgba(128, 90, 213, 0.1)); color: var(--accent, #805AD5); }
    
    /* Nueva área de usuario convertida en botón clickable */
    .user-mini-btn { 
        display: flex; align-items: center; gap: 12px; padding: 15px; 
        border-top: 1px solid var(--border-color, #E2E8F0); 
        margin-top: auto; text-decoration: none; border-radius: 12px;
        transition: background 0.3s, transform 0.2s;
    }
    .user-mini-btn:hover { background: var(--bg-base, #FAFAFC); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    
    .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; color: var(--text-main, #1A202C); }
    .user-info-mini p { font-size: 0.75rem; color: var(--text-muted, #718096); margin:0; }
    
    .btn-logout { margin-top: 10px; text-align: center; font-size: 0.85rem; color: #E53E3E; text-decoration: none; font-weight: 600; padding: 8px; border-radius: 6px; transition: background 0.3s; }
    .btn-logout:hover { background: #FFF5F5; }
    
    /* Candadito para módulos bloqueados (opcional si luego quieres mostrarlos en gris en vez de ocultarlos) */
    .nav-link.locked { opacity: 0.5; cursor: not-allowed; }
</style>

<nav class="sidebar">
    <div class="brand"><h2>A P H</h2></div>
    
    <div class="nav-links">
        <?php if ($show_dashboard): ?>
            <a href="<?= $base_path ?>dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">⌂ Panel Central</a>
        <?php endif; ?>
        
        <?php if ($show_registro): ?>
            <a href="<?= $base_path ?>registro-diario.php" class="nav-link <?= ($current_page == 'registro-diario.php') ? 'active' : '' ?>">⏱ Registro Diario</a>
        <?php endif; ?>

        <?php if ($show_estado): ?>
            <a href="<?= $base_path ?>estado-humano.php" class="nav-link <?= ($current_page == 'estado-humano.php') ? 'active' : '' ?>">👤 Estado Humano</a>
        <?php endif; ?>

        <?php if ($show_proyectos): ?>
            <a href="<?= $base_path ?>proyectos.php" class="nav-link <?= ($current_page == 'proyectos.php') ? 'active' : '' ?>">🚀 Proyectos</a>
        <?php endif; ?>

        <?php if ($show_arbol_dec): ?>
            <a href="<?= $base_path ?>arbol_de_decisiones.php" class="nav-link <?= ($current_page == 'arbol_de_decisiones.php') ? 'active' : '' ?>">🌲 Árbol de Decisiones</a>
        <?php endif; ?>
        
        <?php if ($show_habilidades): ?>
            <a href="<?= $base_path ?>habilidades.php" class="nav-link <?= $is_skills ? 'active' : '' ?>">🌳 Árbol de Habilidades</a>
        <?php endif; ?>
    </div>
    
    <a href="<?= $base_path ?>profile_settings.php" class="user-mini-btn">
        <?= renderAvatar($user['profile_picture'] ?? '') ?>
        <div class="user-info-mini">
            <h4><?= htmlspecialchars($user['username'] ?? 'Usuario') ?></h4>
            <p>
                <?php 
                // Mostrar texto descriptivo en lugar de solo la profesión si se desea
                if($archetype == 1) echo "Arquetipo: Vagabundo";
                elseif($archetype == 2) echo "Arquetipo: Soñador";
                elseif($archetype == 3) echo "Arquetipo: Soldado";
                elseif($archetype == 4) echo "Arquetipo: Ejecutor";
                else echo htmlspecialchars($user['profession'] ?? '');
                ?>
            </p>
        </div>
    </a>
    
    <a href="<?= $base_path ?>logout.php" class="btn-logout">Cerrar Sesión</a>
</nav>