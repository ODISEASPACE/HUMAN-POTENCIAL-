<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        /* Base Styles APH OS (Light Mode) */
        :root { --bg-base: #F1F5F9; --bg-panel: #FFFFFF; --border-color: #E2E8F0; --text-main: #0F172A; --text-muted: #64748B; --accent: #4F46E5; --danger: #E11D48; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        nav { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.03); z-index: 10; }
        .brand { text-align: center; margin-bottom: 40px; }
        .brand h2 { font-weight: 800; color: var(--accent); }
        .brand span { font-size: 0.75rem; font-weight: bold; background: #FEE2E2; color: var(--danger); padding: 2px 8px; border-radius: 10px; text-transform: uppercase; }
        
        .nav-links { flex: 1; }
        .nav-link { display: flex; align-items: center; padding: 12px; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active, .nav-link:hover { background: rgba(79, 70, 229, 0.08); color: var(--accent); }
        
        main { flex: 1; padding: 40px; overflow-y: auto; }
        .header { margin-bottom: 30px; }
        
        /* Tabla de Gestión de Usuarios */
        .data-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .data-card h3 { margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 12px; font-size: 0.85rem; color: var(--text-muted); border-bottom: 2px solid var(--border-color); }
        td { padding: 15px 12px; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .role-admin { background: #E0E7FF; color: #4338CA; }
        .role-dev { background: #FEF3C7; color: #B45309; }
        .role-user { background: #D1FAE5; color: #047857; }
        
        .action-btn { background: transparent; border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .action-btn:hover { background: var(--bg-base); }
        .action-btn.danger { color: var(--danger); border-color: #FECDD3; }
        .action-btn.danger:hover { background: #FFF1F2; }
    </style>
</head>
<body>
    <nav>
        <div class="brand">
            <h2>APH OS</h2>
            <span>Acceso Nivel 1</span>
        </div>
        <div class="nav-links">
            <a href="#" class="nav-link active">👥 Control de Usuarios (RBAC)</a>
            <a href="#" class="nav-link">🛡️ Logs de Auditoría</a>
            <a href="#" class="nav-link">⚙️ Configuración Global</a>
        </div>
    </nav>
    <main>
        <div class="header">
            <h1>Panel de Administración Global</h1>
            <p style="color: var(--text-muted);">Gestión de identidades, roles de acceso y auditoría de la plataforma Odisea.</p>
        </div>

        <div class="data-card">
            <h3>Directorio de Usuarios Activos</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol Asignado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#001</td>
                        <td><strong>Jorge M.</strong></td>
                        <td>jorge.admin@aph.os</td>
                        <td><span class="role-badge role-admin">Administrador</span></td>
                        <td>Hace 2 min</td>
                        <td><button class="action-btn">Editar</button></td>
                    </tr>
                    <tr>
                        <td>#002</td>
                        <td><strong>Christian Santos</strong></td>
                        <td>csantos.dev@aph.os</td>
                        <td><span class="role-badge role-dev">Desarrollador</span></td>
                        <td>Hace 1 hora</td>
                        <td><button class="action-btn">Editar</button></td>
                    </tr>
                    <tr>
                        <td>#003</td>
                        <td><strong>Daniel (QA)</strong></td>
                        <td>daniel.tester@aph.os</td>
                        <td><span class="role-badge role-user">Usuario Estándar</span></td>
                        <td>Ayer 18:30</td>
                        <td>
                            <button class="action-btn">Editar</button>
                            <button class="action-btn danger">Suspender</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>