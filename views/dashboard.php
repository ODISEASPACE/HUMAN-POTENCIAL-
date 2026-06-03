<div class="top-bar">
    <h2>Resumen Global</h2>
    <div>
        <button class="btn" onclick="cargarModulos()">↻ Sincronizar</button>
        <button class="btn btn-primary" onclick="alert('Función para añadir nodo en desarrollo')">+ Nuevo Nodo</button>
    </div>
</div>

<div class="console" id="status-console">
    > Analizando biometría y estado del servidor AWS...<br>
    > Esperando respuesta del motor lógico...
</div>

<div class="grid-container" id="modulos-container">
    </div>

<script>
    // 1. Ir a Python a preguntar el estado del sistema
    async function comprobarEstado() {
        const consoleDiv = document.getElementById('status-console');
        try {
            const response = await fetch('/api/status');
            const data = await response.json();
            const memoryColor = data.estado_memoria.includes("Conectada") ? "#3fb950" : "#f85149";

            consoleDiv.innerHTML = `
                > Ping a servidor central: <span style="color: #58a6ff;">OK</span><br>
                > Servidor Lógico: <span style="color: #fff;">${data.estado_servidor}</span><br>
                > Memoria PostgreSQL: <span style="color: ${memoryColor}; font-weight: bold;">${data.estado_memoria}</span><br>
                > <span style="color: #58a6ff;">Mensaje: ${data.mensaje}</span>
            `;
        } catch (error) {
            consoleDiv.innerHTML = "> <span style='color: #f85149;'>Error crítico: No hay respuesta del motor API en Python.</span>";
        }
    }

    // 2. Ir a Python a traer los módulos guardados en RDS
    async function cargarModulos() {
        const container = document.getElementById('modulos-container');
        container.innerHTML = "<p style='grid-column: 1/-1; text-align:center; color: var(--text-muted);'>Sincronizando con base de datos...</p>";
        
        try {
            const response = await fetch('/api/modulos');
            const modulos = await response.json();
            
            container.innerHTML = ''; 
            
            modulos.forEach(modulo => {
                const card = `
                    <div class="card">
                        <span class="status-badge">${modulo.estado}</span>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;">[ ${modulo.tipo} ]</p>
                        <h3>${modulo.nombre}</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Nodo sincronizado y operativo. Captura de datos en curso.</p>
                    </div>
                `;
                container.innerHTML += card;
            });
        } catch (error) {
            container.innerHTML = "<p style='grid-column: 1/-1; color: #f85149; text-align:center;'>Fallo de conexión con la API.</p>";
        }
    }

    // Ejecutar ambas funciones apenas se cargue este archivo dashboard.php
    comprobarEstado();
    cargarModulos();
</script>