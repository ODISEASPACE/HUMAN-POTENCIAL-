<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. DATOS DEL USUARIO
$stmtUser = $pdo->prepare("SELECT username, profile_picture, profession FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

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
    <title>Árbol de Decisiones | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root { 
            --bg-base: #FAFAFC; 
            --bg-panel: #FFFFFF; 
            --text-main: #1A202C; 
            --text-muted: #718096; 
            --accent: #805AD5; 
            --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar (Mantenido intacto) */
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
        
        /* Layout Principal del Avatar */
        main { flex: 1; padding: 40px; display: flex; flex-direction: column; overflow: hidden; }
        .header-dash { margin-bottom: 30px; flex-shrink: 0; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        .avatar-workspace { display: flex; gap: 30px; flex: 1; overflow: hidden; }
        
        /* Contenedor 3D */
        .canvas-wrapper { flex: 2; position: relative; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: center; }
        #canvas-container { width: 100%; height: 100%; cursor: grab; }
        #canvas-container:active { cursor: grabbing; }
        
        /* Botón Flotante para ir al Árbol */
        .btn-tree-access { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); background: var(--accent); color: white; border: none; padding: 15px 30px; border-radius: 30px; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 10px 20px rgba(128, 90, 213, 0.3); transition: 0.3s; z-index: 10; }
        .btn-tree-access:hover { transform: translateX(-50%) translateY(-5px); box-shadow: 0 15px 25px rgba(128, 90, 213, 0.4); }

        /* Panel Lateral Derecho (Personalización) */
        .customization-panel { flex: 1; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; padding-right: 10px; }
        .custom-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .custom-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; }
        .custom-card h3 span { font-size: 0.8rem; color: var(--accent); font-weight: 600; background: var(--accent-light); padding: 4px 10px; border-radius: 12px; }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .option-btn { background: var(--bg-base); border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; transition: 0.2s; }
        .option-btn:hover { border-color: var(--accent); color: var(--text-main); }
        .option-btn.active { background: var(--accent-light); border-color: var(--accent); color: var(--accent); }

        /* Scrollbar personalizado para el panel derecho */
        .customization-panel::-webkit-scrollbar { width: 6px; }
        .customization-panel::-webkit-scrollbar-thumb { background: #CBD5E0; border-radius: 10px; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand"><h2>A P H</h2></div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">⌂ Panel Central</a>
            <a href="estado-humano.php" class="nav-link">👤 Estado Humano</a>
            <a href="registro-diario.php" class="nav-link">⏱ Registro Diario</a>
            <a href="proyectos.php" class="nav-link">🚀 Proyectos</a>
            <a href="arbol_de_decisiones.php" class="nav-link active">🌳 Árbol de Habilidades</a>
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
            <h1>Identidad y Proyección</h1>
            <p>Configura tu representación virtual antes de adentrarte en el sistema de competencias.</p>
        </div>

        <div class="avatar-workspace">
            
            <div class="canvas-wrapper">
                <div id="canvas-container"></div>
                <button class="btn-tree-access" onclick="window.location.href='habilidades.php'">Acceder al Árbol de Habilidades</button>
            </div>

            <div class="customization-panel">
                
                <div class="custom-card">
                    <h3>Atributos Base <span>Genética</span></h3>
                    <div class="options-grid">
                        <button class="option-btn active" onclick="selectOption('gender', 'm', this)">Cuerpo A</button>
                        <button class="option-btn" onclick="selectOption('gender', 'f', this)">Cuerpo B</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Cabello <span>Estilo</span></h3>
                    <div class="options-grid">
                        <button class="option-btn active" onclick="selectOption('hair', 'style1', this)">Corto</button>
                        <button class="option-btn" onclick="selectOption('hair', 'style2', this)">Largo</button>
                        <button class="option-btn" onclick="selectOption('hair', 'style3', this)">Recogido</button>
                        <button class="option-btn" onclick="selectOption('hair', 'none', this)">Rapado</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Indumentaria <span>Equipamiento</span></h3>
                    <div class="options-grid">
                        <button class="option-btn active" onclick="selectOption('clothes', 'casual', this)">Casual</button>
                        <button class="option-btn" onclick="selectOption('clothes', 'tech', this)">Techwear</button>
                        <button class="option-btn" onclick="selectOption('clothes', 'suit', this)">Formal</button>
                        <button class="option-btn" onclick="selectOption('clothes', 'sport', this)">Deportivo</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Aura del Sistema <span>Energía</span></h3>
                    <div class="options-grid">
                        <button class="option-btn active" onclick="changeAuraColor(0x805AD5, this)">Psique (Violeta)</button>
                        <button class="option-btn" onclick="changeAuraColor(0x38A169, this)">Soma (Verde)</button>
                        <button class="option-btn" onclick="changeAuraColor(0x3182CE, this)">Pneuma (Azul)</button>
                        <button class="option-btn" onclick="changeAuraColor(0xE53E3E, this)">Pathos (Rojo)</button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script>
        // --- 1. CONFIGURACIÓN BÁSICA DE THREE.JS ---
const container = document.getElementById('canvas-container');
const scene = new THREE.Scene();
scene.background = new THREE.Color(0xFFFFFF); 

const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 100);
camera.position.set(0, 1.2, 4); // Ajustado para ver el cuerpo completo

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(container.clientWidth, container.clientHeight);
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
container.appendChild(renderer.domElement);

// --- 2. ILUMINACIÓN ---
const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
scene.add(ambientLight);

const dirLight = new THREE.DirectionalLight(0xffffff, 0.6);
dirLight.position.set(2, 5, 5);
dirLight.castShadow = true;
scene.add(dirLight);

let auraLight = new THREE.PointLight(0x805AD5, 2, 10);
auraLight.position.set(0, 1.5, -1.5);
scene.add(auraLight);

// --- 3. SISTEMA DE ENSAMBLAJE MODULAR ---
const loader = new THREE.GLTFLoader();
const avatarGroup = new THREE.Group();
scene.add(avatarGroup);

// Estado actual del avatar
let currentGender = 'Male'; 
let currentOutfit = 'Peasant';
let loadedParts = []; // Aquí guardaremos las mallas cargadas para borrarlas al cambiar

function assembleAvatar() {
    // 1. Limpiar las partes cargadas anteriormente
    loadedParts.forEach(part => avatarGroup.remove(part));
    loadedParts = [];

    // 2. Definir qué piezas componen el traje actual
    let partsToLoad = [];
    
    if (currentOutfit === 'Peasant') {
        partsToLoad = ['_Body', '_Arms', '_Legs', '_Feet'];
    } else if (currentOutfit === 'Ranger') {
        // Nota: En tus archivos, Female tiene 'Pauldrons' (plural) y Male 'Pauldron' (singular)
        let pauldronSuffix = (currentGender === 'Female') ? '_Acc_Pauldrons' : '_Acc_Pauldron';
        partsToLoad = ['_Body', '_Arms', '_Legs', '_Feet_Boots', '_Head_Hood', pauldronSuffix];
    }

    // 3. Cargar cada pieza dinámicamente
    partsToLoad.forEach(part => {
        let filename = `${currentGender}_${currentOutfit}${part}.gltf`;
        let path = `assets/3d/avatar/${filename}`;

        loader.load(path, function(gltf) {
            let model = gltf.scene;
            
            // Habilitar sombras en todas las mallas de la pieza
            model.traverse((node) => {
                if (node.isMesh) {
                    node.castShadow = true;
                    node.receiveShadow = true;
                }
            });

            // Ajustar posición si es necesario (depende de cómo se exportó el 3D)
            model.position.y = -1; 
            
            avatarGroup.add(model);
            loadedParts.push(model); // Guardar referencia
        }, undefined, function(error) {
            console.error(`Error cargando la pieza: ${filename}`, error);
        });
    });
}

// Cargar el avatar por defecto al iniciar
assembleAvatar();

// --- 4. INTERACCIÓN CON LA INTERFAZ ---
function selectOption(category, value, btnElement) {
    const grid = btnElement.parentElement;
    grid.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('active'));
    btnElement.classList.add('active');
    
    if (category === 'gender') {
        currentGender = (value === 'm') ? 'Male' : 'Female';
        assembleAvatar();
    } 
    else if (category === 'clothes') {
        // Mapeamos los botones a los sets que tienes en tu carpeta
        currentOutfit = (value === 'casual') ? 'Peasant' : 'Ranger';
        assembleAvatar();
    }
}

function changeAuraColor(hexColor, btnElement) {
    const grid = btnElement.parentElement;
    grid.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('active'));
    btnElement.classList.add('active');
    auraLight.color.setHex(hexColor);
}

// --- 5. CONTROLES Y ANIMACIÓN ---
let isDragging = false;
let previousMousePosition = { x: 0 };

container.addEventListener('mousedown', () => isDragging = true);
window.addEventListener('mouseup', () => isDragging = false);
window.addEventListener('mousemove', (e) => {
    if (isDragging) {
        avatarGroup.rotation.y += (e.offsetX - previousMousePosition.x) * 0.01;
    }
    previousMousePosition = { x: e.offsetX };
});

function animate() {
    requestAnimationFrame(animate);
    avatarGroup.position.y = Math.sin(Date.now() * 0.002) * 0.02; // Animación de respiración
    renderer.render(scene, camera);
}

animate();

window.addEventListener('resize', () => {
    if(container) {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }
});
    </script>
    
</body>
</html>