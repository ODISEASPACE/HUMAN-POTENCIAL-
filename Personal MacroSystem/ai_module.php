<!-- ai_module.php -->
<style>
    /* Estilos encapsulados para el módulo IA */
    .ia-panel { 
        width: 350px; 
        border-left: 1px solid var(--border); 
        background: var(--panel); 
        padding: 20px; 
        display: flex; 
        flex-direction: column; 
        height: 100vh; 
        position: fixed; 
        right: 0; 
        top: 0; 
        box-sizing: border-box;
        z-index: 999;
    }
    .ai-console { 
        flex: 1; 
        background: #000; 
        border: 1px solid var(--border); 
        border-radius: 6px; 
        padding: 15px; 
        font-family: 'JetBrains Mono', monospace; 
        font-size: 0.8rem; 
        color: #a1a1aa; 
        overflow-y: auto; 
        margin-top: 15px; 
    }
    .ai-input-card { 
        background: rgba(255,255,255,0.02); 
        border: 1px solid var(--border); 
        border-radius: 6px; 
        padding: 15px; 
        margin-top: 15px; 
    }
    .ai-input-field { 
        width: 100%; 
        background: #000; 
        border: 1px solid var(--border); 
        color: #fff; 
        padding: 10px; 
        border-radius: 4px; 
        font-family: 'Inter', sans-serif; 
        font-size: 0.85rem; 
        margin-bottom: 10px; 
        box-sizing: border-box; 
        resize: vertical; 
        min-height: 80px; 
    }
    .ai-btn-action { 
        width: 100%; 
        background: var(--accent); 
        color: #000; 
        border: none; 
        padding: 10px; 
        border-radius: 4px; 
        cursor: pointer; 
        font-weight: bold; 
        font-family: 'JetBrains Mono', monospace; 
        transition: 0.2s;
    }
    .ai-btn-action:hover { opacity: 0.8; }
</style>

<div class="ia-panel">
    <h2 style="font-size: 0.9rem; font-family: 'JetBrains Mono'; color: var(--accent); margin: 0; border-bottom: 1px dashed var(--border); padding-bottom: 10px;">>> Enlace_Gemini_Flash</h2>
    
    <div class="ai-console" id="aiResponse">
        > Sistema a la escucha.<br>
        > Conciencia inicializada en este submódulo.
    </div>

    <div class="ai-input-card">
        <textarea class="ai-input-field" id="quick-log" placeholder="Consulta al asistente antes de actuar..."></textarea>
        <button class="ai-btn-action" onclick="triggerIA()">Analizar Contexto</button>
    </div>
</div>

<script>
    function logToAI(msg, color = '#a1a1aa') {
        const consoleBox = document.getElementById('aiResponse');
        consoleBox.innerHTML += `<br><br><span style="color:${color};">${msg}</span>`;
        consoleBox.scrollTop = consoleBox.scrollHeight;
    }

    async function triggerIA() {
        const payload = document.getElementById('quick-log').value;
        if(!payload) return;
        document.getElementById('quick-log').value = '';

        // Detección automática del contexto (Lee el título de la página y la URL)
        const currentModule = document.title.split('|')[0].trim();
        const path = window.location.pathname.split('/').pop();
        const activeContext = `${currentModule} (${path})`;
        
        logToAI(`> Analizando input desde: [${activeContext}]...`, '#f59e0b');
        
        if(result.status === 'success') {
    // 1. Verificamos si 'data' ya es un objeto procesado
    if (typeof result.data === 'object' && result.data !== null) {
        logToAI(`> Análisis de Conciencia:`, '#10b981');
        // Si el objeto trae la propiedad 'analysis', la mostramos. 
        // Si no, convertimos todo el objeto a texto visible para que sepas qué llegó.
        let textoSalida = result.data.analysis ? result.data.analysis : JSON.stringify(result.data, null, 2);
        logToAI(`"${textoSalida}"`, '#d8b4fe');
        
    } else {
        // 2. Si 'data' es un string (ej: la IA respondió con bloques de código Markdown)
        try {
            let cleanJson = result.data.replace(/```json/g, '').replace(/```/g, '').trim();
            let aiData = JSON.parse(cleanJson);
            logToAI(`> Análisis de Conciencia:`, '#10b981');
            logToAI(`"${aiData.analysis}"`, '#d8b4fe');
        } catch(e) {
            // Si el parseo falla, mostramos el texto crudo.
            logToAI(`"${result.data}"`, '#d8b4fe');
        }
    }
}
    }
</script>