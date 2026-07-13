<?php
session_start();
require '../db.php'; 

$current_skill = $_GET['skill'] ?? 'estudio';
$user_id = $_SESSION['user_id'] ?? 1;

// Consulta integrando la diferenciación de nodos del sistema vs personalizados
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.max_level, sn.is_default, 
           COALESCE(usn.current_level, 0) as current_level
    FROM specialization_nodes sn
    LEFT JOIN user_specialization_nodes usn ON sn.id = usn.node_id AND usn.user_id = ?
    WHERE sn.parent_skill_key = ? AND (sn.is_default = true OR sn.user_id = ?)
    ORDER BY sn.is_default DESC, sn.id ASC
");
$stmt->execute([$user_id, $current_skill, $user_id]);
$nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .mod-node-list { display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; }
    .mod-node-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-panel); transition: 0.3s; }
    
    .mod-node-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
    .badge-custom { font-size: 0.6rem; background: var(--border-color); padding: 3px 6px; border-radius: 4px; text-transform: uppercase; color: var(--text-muted); }
    .mod-node-info p { margin: 0; color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
    
    .level-text { color: var(--theme-color); display: inline-block; transition: 0.2s; }
    
    .mod-btn-invest { background: var(--theme-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1rem; transition: 0.2s; }
    .mod-btn-invest:hover:not(:disabled) { opacity: 0.8; transform: scale(1.05); }
    .mod-btn-invest:disabled { background: var(--border-color); color: var(--text-muted); cursor: not-allowed; transform: none; }
    
    .btn-delete { background: none; border: none; color: #E53E3E; cursor: pointer; font-size: 1.2rem; padding: 5px; opacity: 0.6; transition: 0.2s; }
    .btn-delete:hover { opacity: 1; transform: scale(1.1); }

    .custom-node-form { background: var(--bg-base); padding: 20px; border-radius: 8px; border: 1px dashed var(--border-color); display: flex; gap: 10px; flex-wrap: wrap; }
    .custom-node-form input { flex: 1; min-width: 150px; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-panel); color: var(--text-main); }
    .btn-add-node { background: var(--text-main); color: var(--bg-base); border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }

    /* Animación de subida de nivel */
    .anim-level-up { animation: levelUpPulse 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    @keyframes levelUpPulse {
        0% { transform: scale(1); filter: brightness(1); }
        40% { transform: scale(1.8); filter: brightness(1.5); text-shadow: 0 0 15px var(--theme-color); }
        100% { transform: scale(1); filter: brightness(1); }
    }
</style>

<div class="mod-node-list">
    <?php if (empty($nodos)): ?>
        <p style="text-align:center; color: var(--text-muted);">No hay nodos configurados.</p>
    <?php else: ?>
        <?php foreach ($nodos as $nodo): 
            $isMaxed = $nodo['current_level'] >= $nodo['max_level'];
        ?>
            <div class="mod-node-item" id="node-card-<?= $nodo['id'] ?>">
                <div class="mod-node-info">
                    <h3>
                        <?= htmlspecialchars($nodo['name']) ?>
                        <?php if(!$nodo['is_default']): ?> <span class="badge-custom">Propio</span> <?php endif; ?>
                    </h3>
                    <p>Nivel actual: 
                        <span class="level-text" id="lvl-<?= $nodo['id'] ?>"><b><?= $nodo['current_level'] ?></b></span> / <?= $nodo['max_level'] ?>
                    </p>
                </div>
                
                <div style="display:flex; gap:10px; align-items:center;">
                    <button class="mod-btn-invest" id="btn-invest-<?= $nodo['id'] ?>" onclick="invertirPuntos(<?= $nodo['id'] ?>, this)" <?= $isMaxed ? 'disabled' : '' ?>>
                        <?= $isMaxed ? 'Maxed' : '+ Invertir' ?>
                    </button>
                    
                    <?php if(!$nodo['is_default']): ?>
                        <button class="btn-delete" onclick="eliminarNodo(<?= $nodo['id'] ?>)" title="Eliminar">🗑️</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="custom-node-form">
    <input type="hidden" id="parentSkillKey" value="<?= htmlspecialchars($current_skill) ?>">
    <input type="text" id="newNodeName" placeholder="Nueva habilidad..." maxlength="50">
    <input type="number" id="newNodeMax" placeholder="Max (Ej: 10)" value="10" min="1" style="max-width: 100px;">
    <button class="btn-add-node" onclick="crearNodo(this)">+ Añadir</button>
</div>

<script>
function invertirPuntos(nodeId, btnElement) {
    btnElement.innerHTML = '...';
    btnElement.disabled = true;

    fetch('api_invertir_punto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ node_id: nodeId })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Notifica a la ventana maestra que hubo cambios en el progreso
            if (typeof actualizarMasterBar === "function") {
                actualizarMasterBar();
            }
            
            const spanNivel = document.getElementById('lvl-' + nodeId);
            spanNivel.innerHTML = '<b>' + data.new_level + '</b>';
            
            spanNivel.classList.remove('anim-level-up');
            void spanNivel.offsetWidth; // Reflow forzar animación
            spanNivel.classList.add('anim-level-up');

            if(data.is_maxed) {
                btnElement.innerHTML = 'Maxed';
            } else {
                btnElement.innerHTML = '+ Invertir';
                btnElement.disabled = false;
            }
        } else {
            alert('Error: ' + data.error);
            btnElement.innerHTML = '+ Invertir';
            btnElement.disabled = false;
        }
    })
    .catch(err => { 
        alert("Error de red."); 
        btnElement.innerHTML = '+ Invertir'; 
        btnElement.disabled = false; 
    });
}

function crearNodo(btnElement) {
    const nameInput = document.getElementById('newNodeName').value.trim();
    const maxInput = document.getElementById('newNodeMax').value;
    const parentKey = document.getElementById('parentSkillKey').value;

    if(!nameInput) return alert('Ingresa un nombre.');

    btnElement.innerText = '...';
    
    fetch('api_nodo_crud.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'create', parent_key: parentKey, name: nameInput, max_level: maxInput })
    }).then(res => res.json()).then(data => {
        if(data.success) openModal(`aumentar.php?skill=${parentKey}`, document.getElementById('modalTitle').innerText);
        else alert('Error: ' + data.error);
    });
}

function eliminarNodo(nodeId) {
    if(!confirm('¿Eliminar nodo? Se perderá el progreso.')) return;

    fetch('api_nodo_crud.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'delete', node_id: nodeId })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            const card = document.getElementById('node-card-' + nodeId);
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
            if (typeof actualizarMasterBar === "function") {
                actualizarMasterBar();
            }
        }
    });
}
</script>