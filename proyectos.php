<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';

// 1. PROCESAR EL FORMULARIO (Crear nuevo registro)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO projects_items (user_id, category, title, description, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $category, $title, $description, $status]);
        $mensaje = "<div class='msg-success'>Registro añadido a tu ecosistema.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='msg-error'>Error al guardar: " . $e->getMessage() . "</div>";
    }
}

// 2. OBTENER DATOS DEL USUARIO
$stmtUser = $pdo->prepare("SELECT username, profile_picture, profession FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// 3. OBTENER TODOS LOS PROYECTOS DEL USUARIO
$stmtProjects = $pdo->prepare("SELECT * FROM projects_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtProjects->execute([$user_id]);
$projects = $stmtProjects->fetchAll();

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
    <title>Proyectos | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; --accent: #805AD5; --accent-light: rgba(128, 90, 213, 0.1); --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        nav.sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 10; flex-shrink: 0; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .user-info-mini h4 { font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .user-info-mini p { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout { margin-top: 15px; text-align: center; font-size: 0.85rem; color: #E53E3E; text-decoration: none; font-weight: 600; padding: 8px; border-radius: 6px; }
        
        /* Main */
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; }
        .header-dash { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        .btn-primary { background: var(--accent); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { background: #6B46C1; transform: translateY(-2px); }

        /* Categorías (Estilo Notion Tabs) */
        .category-filters { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; overflow-x: auto; }
        .filter-btn { background: transparent; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.2s; white-space: nowrap; }
        .filter-btn:hover { background: rgba(0,0,0,0.03); color: var(--text-main); }
        .filter-btn.active { background: var(--bg-panel); color: var(--accent); box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }

        /* Grid de Proyectos (Gallery View) */
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .project-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; cursor: pointer; }
        .project-card:hover { border-color: var(--accent); box-shadow: 0 10px 20px rgba(0,0,0,0.04); transform: translateY(-3px); }
        
        .card-category { font-size: 0.75rem; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 10px; color: var(--text-main); }
        .card-desc { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; flex: 1; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        
        .card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: auto; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-activo { background: #EBF4FF; color: #3182CE; }
        .status-completado { background: #C6F6D5; color: #276749; }
        .status-pausado { background: #FEFCBF; color: #B7791F; }
        .card-date { font-size: 0.75rem; color: #A0AEC0; }

        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 100; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #fff; width: 100%; max-width: 500px; border-radius: 16px; padding: 30px; position: relative; }
        .close-btn { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; border: none; background: none; color: var(--text-muted); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-muted); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(128, 90, 213, 0.1); }
        .form-group textarea { resize: vertical; min-height: 100px; }

        .msg-success { background: #C6F6D5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        .msg-error { background: #FED7D7; color: #C53030; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        .empty-state { text-align: center; padding: 50px; color: var(--text-muted); grid-column: 1 / -1; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="estado-humano.php" class="nav-link">👤 Estado Humano</a>
            <a href="registro-diario.php" class="nav-link">⏱ Registro Diario</a>
            <a href="proyectos.php" class="nav-link active">🚀 Proyectos</a>
        </div>
        <div class="user-mini">
            <?= renderAvatar($user['profile_picture']) ?>
            <div class="user-info-mini">
                <h4><?= htmlspecialchars($user['username'] ?? 'Usuario') ?></h4>
                <p><?= htmlspecialchars($user['profession'] ?? 'Sin asignar') ?></p>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </nav>

    <main>
        <div class="header-dash">
            <div>
                <h1>Repositorio Central</h1>
                <p>Gestión interactiva de tus bases de conocimiento y desarrollo.</p>
            </div>
            <button class="btn-primary" onclick="openModal()">+ Nuevo Registro</button>
        </div>

        <?= $mensaje ?>

        <div class="category-filters">
            <button class="filter-btn active" onclick="filterProjects('Todos', this)">Todos</button>
            <button class="filter-btn" onclick="filterProjects('Fuentes de Estudio', this)">Fuentes de Estudio</button>
            <button class="filter-btn" onclick="filterProjects('Logros', this)">Logros</button>
            <button class="filter-btn" onclick="filterProjects('Líneas de Tiempo', this)">Líneas de Tiempo</button>
            <button class="filter-btn" onclick="filterProjects('Recomendaciones', this)">Recomendaciones</button>
            <button class="filter-btn" onclick="filterProjects('Proyecto 1', this)">Proyecto 1 (P1)</button>
            <button class="filter-btn" onclick="filterProjects('Proyecto 2', this)">Proyecto 2 (P2)</button>
        </div>

        <div class="projects-grid" id="projectsGrid">
            <?php if(empty($projects)): ?>
                <div class="empty-state">No tienes registros en este espacio. Crea uno nuevo.</div>
            <?php else: ?>
                <?php foreach($projects as $proj): 
                    $statusClass = 'status-' . strtolower($proj['status']);
                    $date = date("d M Y", strtotime($proj['created_at']));
                ?>
                    <a href="proyecto_detalle.php?id=<?= $proj['id'] ?>" class="project-card" data-category="<?= htmlspecialchars($proj['category']) ?>" style="text-decoration: none; color: inherit;"></a>
                        <div class="card-category"><?= htmlspecialchars($proj['category']) ?></div>
                        <div class="card-title"><?= htmlspecialchars($proj['title']) ?></div>
                        <div class="card-desc"><?= htmlspecialchars($proj['description']) ?></div>
                        <div class="card-footer">
                            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($proj['status']) ?></span>
                            <span class="card-date"><?= $date ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="newProjectModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" type="button" onclick="closeModal()">&times;</button>
            <h2 style="margin-bottom: 20px; color: var(--accent);">Crear Nuevo Registro</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Categoría (Hub)</label>
                    <select name="category" required>
                        <option value="Fuentes de Estudio">Fuentes de Estudio</option>
                        <option value="Logros">Logros</option>
                        <option value="Líneas de Tiempo">Líneas de Tiempo</option>
                        <option value="Recomendaciones">Recomendaciones</option>
                        <option value="Proyecto 1">Proyecto 1 (P1)</option>
                        <option value="Proyecto 2">Proyecto 2 (P2)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="title" required placeholder="Ej. Curso de Arquitectura Cloud">
                </div>

                <div class="form-group">
                    <label>Descripción / Contenido</label>
                    <textarea name="description" placeholder="Añade apuntes, enlaces o detalles de este registro..."></textarea>
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="status">
                        <option value="Activo">Activo (En progreso)</option>
                        <option value="Completado">Completado</option>
                        <option value="Pausado">Pausado</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Guardar Registro</button>
            </form>
        </div>
    </div>

    <script>
        // Funciones del Modal
        function openModal() {
            document.getElementById('newProjectModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('newProjectModal').classList.remove('active');
        }
        
        // Cerrar al hacer clic fuera
        document.getElementById('newProjectModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Lógica de Filtrado Interactivo (Estilo Notion)
        function filterProjects(category, btnElement) {
            // Actualizar diseño de botones
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            // Filtrar tarjetas en el DOM instantáneamente
            const cards = document.querySelectorAll('.project-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'Todos' || cardCat === category) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Manejo del estado vacío dinámico
            let emptyState = document.querySelector('.empty-state-dynamic');
            if (visibleCount === 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'empty-state empty-state-dynamic';
                    emptyState.innerText = `No hay registros en ${category}.`;
                    document.getElementById('projectsGrid').appendChild(emptyState);
                }
            } else {
                if (emptyState) emptyState.remove();
            }
        }
    </script>
</body>
</html>