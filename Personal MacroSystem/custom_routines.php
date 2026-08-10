<?php
session_start();
require '../db.php'; // Apunta a la raíz

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_symbiote']) || $_SESSION['is_symbiote'] !== true) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constructor de Rutinas | Symbiote</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #09090b; --panel: #18181b; --text: #e4e4e7; --accent: #a855f7; --border: #27272a; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            margin: 0;
            padding: 40px; 
            
            /* NUEVO LAYOUT GRID PARA INYECTAR IA */
            display: grid; 
            grid-template-columns: 1fr 350px; 
            gap: 40px;
            height: 100vh;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        /* Contenedor principal con scroll independiente */
        .main-content {
            overflow-y: auto;
            padding-right: 20px;
            display: flex;
            flex-direction: column;
            align-items: center; /* Mantiene tu panel centrado */
        }

        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 30px; width: 100%; max-width: 800px; }
        h2 { font-family: 'JetBrains Mono'; color: var(--accent); margin-top: 0; border-bottom: 1px dashed var(--border); padding-bottom: 10px; }
        
        .routine-builder { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
        .block-row { display: grid; grid-template-columns: 120px 1fr auto; gap: 10px; align-items: center; background: #000; padding: 10px; border: 1px solid var(--border); border-radius: 6px; }
        
        .input-field { background: #09090b; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 4px; font-family: 'Inter'; font-size: 0.85rem; }
        .input-time { font-family: 'JetBrains Mono'; text-align: center; }
        
        .btn-action { background: var(--accent); color: #000; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-family: 'JetBrains Mono'; }
        .btn-add { background: transparent; color: var(--accent); border: 1px dashed var(--accent); width: 100%; padding: 10px; margin-top: 10px; cursor: pointer; font-family: 'JetBrains Mono'; transition: 0.2s; }
        .btn-add:hover { background: rgba(168, 85, 247, 0.1); }
    </style>
</head>
<body>
    <!-- ENVOLTORIO DEL CONTENIDO PRINCIPAL -->
    <div class="main-content">
        <button onclick="window.location.href='symbiote_core.php'" style="background:transparent; color:#a1a1aa; border:none; cursor:pointer; align-self:flex-start; margin-bottom:20px; font-family:'JetBrains Mono';">< Volver a la Consola</button>
        
        <div class="panel">
            <h2>>> Diseño_Estructural_Personalizado</h2>
            
            <div style="margin-bottom: 20px;">
                <label style="font-size:0.8rem; color:#a1a1aa; font-family:'JetBrains Mono';">Identificador de la Rutina</label>
                <input type="text" class="input-field" style="width: 100%; box-sizing: border-box;" placeholder="Ej. Protocolo Deep Work V2">
            </div>

            <div id="blocks-container" class="routine-builder">
                <!-- Bloque Base por defecto -->
                <div class="block-row">
                    <input type="time" class="input-field input-time" value="06:00">
                    <input type="text" class="input-field" placeholder="Nombre de la actividad (Ej. Meditación / Lectura)">
                    <input type="color" value="#3b82f6" style="border:none; background:none; cursor:pointer; height:35px;">
                </div>
            </div>
            
            <button class="btn-add" onclick="addBlock()">+ Añadir Bloque Horario</button>
            
            <div style="margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px; text-align: right;">
                <button class="btn-action" onclick="compileRoutine()">Compilar e Inyectar a la BD</button>
            </div>
        </div>
    </div>

    <!-- INYECCIÓN DEL MÓDULO IA -->
    <?php include 'ai_module.php'; ?>

    <script>
        function addBlock() {
            const container = document.getElementById('blocks-container');
            const row = document.createElement('div');
            row.className = 'block-row';
            row.innerHTML = `
                <input type="time" class="input-field input-time" value="12:00">
                <input type="text" class="input-field" placeholder="Nombre de la actividad...">
                <input type="color" value="#10b981" style="border:none; background:none; cursor:pointer; height:35px;">
            `;
            container.appendChild(row);
        }

        function compileRoutine() {
            // Aquí capturaremos todos los bloques generados y los enviaremos al backend 
            // para guardarlos en una nueva tabla de 'rutinas_personalizadas'.
            alert("Compilador de rutinas listo para integración con el backend.");
        }
    </script>
</body>
</html>