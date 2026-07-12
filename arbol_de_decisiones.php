<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- API INTERNA PARA GUARDAR CONFIGURACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (isset($input['action']) && $input['action'] === 'save_avatar') {
        $configToSave = json_encode([
            'gender' => $input['gender'],
            'clothes' => $input['clothes'],
            'aura' => $input['aura']
        ]);
        
        $stmtSave = $pdo->prepare("UPDATE users SET avatar_config = ? WHERE id = ?");
        $success = $stmtSave->execute([$configToSave, $user_id]);
        
        echo json_encode(['success' => $success]);
        exit;
    }
}

// 1. DATOS DEL USUARIO
$stmtUser = $pdo->prepare("SELECT username, profile_picture, profession, avatar_config FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// Extraer configuración guardada o usar valores por defecto actualizados
$savedConfig = json_decode($user['avatar_config'] ?? '{}', true);
$defaultGender = $savedConfig['gender'] ?? 'Male';
$defaultClothes = $savedConfig['clothes'] ?? 'monochrome_casual_portrait'; // Fallback a un modelo existente
$defaultAura = $savedConfig['aura'] ?? 'none';

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
    <title>Identidad | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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
        
        main { flex: 1; padding: 40px; display: flex; flex-direction: column; overflow: hidden; }
        .header-dash { margin-bottom: 30px; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; }
        .header-dash h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .header-dash p { color: var(--text-muted); }
        
        .btn-save-sync { background: var(--bg-panel); border: 2px solid var(--accent); color: var(--accent); padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-save-sync:hover { background: var(--accent); color: white; }
        .btn-save-sync.saving { opacity: 0.7; cursor: wait; }

        .avatar-workspace { display: flex; gap: 30px; flex: 1; overflow: hidden; }
        
        .canvas-wrapper { flex: 2; position: relative; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: center; }
        #canvas-container { width: 100%; height: 100%; cursor: grab; outline: none; }
        #canvas-container:active { cursor: grabbing; }
        
        #loading-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; z-index: 5; backdrop-filter: blur(5px); flex-direction: column; gap: 10px; }
        .spinner { width: 40px; height: 40px; border: 4px solid var(--border-color); border-top: 4px solid var(--accent); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .camera-controls { position: absolute; top: 20px; left: 20px; display: flex; gap: 10px; z-index: 10; }
        .cam-btn { background: rgba(255,255,255,0.9); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: var(--text-muted); box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.2s; }
        .cam-btn.active { color: var(--accent); border-color: var(--accent); background: var(--accent-light); }

        .btn-tree-access { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); background: var(--accent); color: white; border: none; padding: 15px 30px; border-radius: 30px; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 10px 20px rgba(128, 90, 213, 0.3); transition: 0.3s; z-index: 10; }
        .btn-tree-access:hover { transform: translateX(-50%) translateY(-5px); box-shadow: 0 15px 25px rgba(128, 90, 213, 0.4); }

        .customization-panel { flex: 1; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; padding-right: 10px; }
        .custom-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .custom-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; }
        .custom-card h3 span { font-size: 0.8rem; color: var(--accent); font-weight: 600; background: var(--accent-light); padding: 4px 10px; border-radius: 12px; }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .options-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .option-btn { background: var(--bg-base); border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; transition: 0.2s; }
        .option-btn:hover { border-color: var(--accent); color: var(--text-main); }
        .option-btn.active { background: var(--accent-light); border-color: var(--accent); color: var(--accent); }
        
        .slider-container { margin-top: 15px; }
        .slider-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: flex; justify-content: space-between; margin-bottom: 8px; }
        .slider-input { width: 100%; cursor: pointer; accent-color: var(--accent); }

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
            <div>
                <h1>Identidad y Proyección</h1>
                <p>Configura tu representación virtual antes de adentrarte en el sistema de competencias.</p>
            </div>
            <button class="btn-save-sync" id="btnSaveConfig" onclick="saveConfiguration()">
                <span>Guardar Sincronización</span>
            </button>
        </div>

        <div class="avatar-workspace">
            
            <div class="canvas-wrapper">
                <div class="camera-controls">
                    <button class="cam-btn active" id="btnCam3D" onclick="setCameraMode('3d')">Exploración 3D</button>
                    <button class="cam-btn" id="btnCam2D" onclick="setCameraMode('2d')">Vista Frontal 2D</button>
                </div>

                <div id="loading-overlay">
                    <div class="spinner"></div>
                    <span style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Sincronizando Malla...</span>
                </div>
                
                <div id="canvas-container"></div>
                <button class="btn-tree-access" onclick="window.location.href='habilidades.php'">Acceder al Árbol de Habilidades</button>
            </div>

            <div class="customization-panel">
                
                <div class="custom-card">
                    <h3>Atributos Base <span>Genética</span></h3>
                    <div class="options-grid">
                        <button class="option-btn <?= $defaultGender === 'Male' ? 'active' : '' ?>" onclick="selectOption('gender', 'Male', this)">Cuerpo A (Hombre)</button>
                        <button class="option-btn <?= $defaultGender === 'Female' ? 'active' : '' ?>" onclick="selectOption('gender', 'Female', this)">Cuerpo B (Mujer)</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Ajustes de Rigging <span>Esqueleto</span></h3>
                    <div class="slider-container">
                        <div class="slider-label">
                            <span>Rotación de Brazos</span>
                            <span><span id="arm-rot-val">90</span>°</span>
                        </div>
                        <input type="range" class="slider-input" id="armRotationSlider" min="0" max="180" value="90" oninput="updateArmRotation(this.value)">
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Modelos Masculinos <span>Distribución</span></h3>
                    <div class="options-grid cols-2">
                        <button class="option-btn <?= $defaultClothes === 'blue_workwear_messenger' ? 'active' : '' ?>" onclick="selectOption('clothes', 'blue_workwear_messenger', this)">Mensajero Workwear</button>
                        <button class="option-btn <?= $defaultClothes === 'cooper' ? 'active' : '' ?>" onclick="selectOption('clothes', 'cooper', this)">Cooper</button>
                        <button class="option-btn <?= $defaultClothes === 'young_guy_keeps_his_hands_in_pockets' ? 'active' : '' ?>" onclick="selectOption('clothes', 'young_guy_keeps_his_hands_in_pockets', this)">Chico Casual</button>
                        <button class="option-btn <?= $defaultClothes === 'navy_minimalist_gentleman' ? 'active' : '' ?>" onclick="selectOption('clothes', 'navy_minimalist_gentleman', this)">Minimalista Navy</button>
                        <button class="option-btn <?= $defaultClothes === 'executive_in_a_navy_suit' ? 'active' : '' ?>" onclick="selectOption('clothes', 'executive_in_a_navy_suit', this)">Ejecutivo Navy</button>
                        <button class="option-btn <?= $defaultClothes === 'noir_ensemble' ? 'active' : '' ?>" onclick="selectOption('clothes', 'noir_ensemble', this)">Conjunto Noir</button>
                        <button class="option-btn <?= $defaultClothes === 'confident_executive_in_a_navy_suit' ? 'active' : '' ?>" onclick="selectOption('clothes', 'confident_executive_in_a_navy_suit', this)">Ejecutivo Confiado</button>
                        <button class="option-btn <?= $defaultClothes === 'rigged_t-pose_human_male_w_50_face_blendshapes' ? 'active' : '' ?>" onclick="selectOption('clothes', 'rigged_t-pose_human_male_w_50_face_blendshapes', this)">Base T-Pose</button>
                        <button class="option-btn <?= $defaultClothes === 'berserk_guts_black_swordsman.glb' ? 'active' : '' ?>" onclick="selectOption('clothes', 'berserk_guts_black_swordsman.glb', this)">Guts (Berserk)</button>
                        <button class="option-btn <?= $defaultClothes === 'monochrome_casual_portrait' ? 'active' : '' ?>" onclick="selectOption('clothes', 'monochrome_casual_portrait', this)">Retrato Monocromo</button>
                        <button class="option-btn <?= $defaultClothes === 'hooded_figure_3d_model_free' ? 'active' : '' ?>" onclick="selectOption('clothes', 'hooded_figure_3d_model_free', this)">Túnica Capucha</button>
                        <button class="option-btn <?= $defaultClothes === 'midnight_outlaw_shadow_character_3d_model_free' ? 'active' : '' ?>" onclick="selectOption('clothes', 'midnight_outlaw_shadow_character_3d_model_free', this)">Sombra Forajido</button>
                        <button class="option-btn <?= $defaultClothes === 'casual_in_gray.glb' ? 'active' : '' ?>" onclick="selectOption('clothes', 'casual_in_gray.glb', this)">Casual en Gris</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Modelos Femeninos <span>Distribución</span></h3>
                    <div class="options-grid cols-2">
                        <button class="option-btn <?= $defaultClothes === 'casual_cropped_hoodie_portrait' ? 'active' : '' ?>" onclick="selectOption('clothes', 'casual_cropped_hoodie_portrait', this)">Sudadera Casual</button>
                        <button class="option-btn <?= $defaultClothes === 'casual_confidence' ? 'active' : '' ?>" onclick="selectOption('clothes', 'casual_confidence', this)">Confianza Casual</button>
                        <button class="option-btn <?= $defaultClothes === 'midnight_casual.glb' ? 'active' : '' ?>" onclick="selectOption('clothes', 'midnight_casual.glb', this)">Casual Medianoche</button>
                        <button class="option-btn <?= $defaultClothes === 'dark_astronaut' ? 'active' : '' ?>" onclick="selectOption('clothes', 'dark_astronaut', this)">Astronauta Oscuro</button>
                        <button class="option-btn <?= $defaultClothes === 'girl_speedsculpt' ? 'active' : '' ?>" onclick="selectOption('clothes', 'girl_speedsculpt', this)">Chica Speedsculpt</button>
                        <button class="option-btn <?= $defaultClothes === 'little_witch_academia' ? 'active' : '' ?>" onclick="selectOption('clothes', 'little_witch_academia', this)">Little Witch</button>
                        <button class="option-btn <?= $defaultClothes === 'matilda' ? 'active' : '' ?>" onclick="selectOption('clothes', 'matilda', this)">Matilda</button>
                        <button class="option-btn <?= $defaultClothes === 'carol_tennis_player_girl_animated_3d_character.glb' ? 'active' : '' ?>" onclick="selectOption('clothes', 'carol_tennis_player_girl_animated_3d_character.glb', this)">Carol (Tenis)</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Entorno Artístico <span>Aura</span></h3>
                    <div class="options-grid cols-3">
                        <button class="option-btn <?= $defaultAura === 'none' ? 'active' : '' ?>" onclick="changeAuraColor('none', this)">Fondo Neutro</button>
                        <button class="option-btn <?= $defaultAura === '0x805AD5' ? 'active' : '' ?>" onclick="changeAuraColor('0x805AD5', this)">Psique</button>
                        <button class="option-btn <?= $defaultAura === '0x38A169' ? 'active' : '' ?>" onclick="changeAuraColor('0x38A169', this)">Soma</button>
                        <button class="option-btn <?= $defaultAura === '0x3182CE' ? 'active' : '' ?>" onclick="changeAuraColor('0x3182CE', this)">Pneuma</button>
                        <button class="option-btn <?= $defaultAura === '0xE53E3E' ? 'active' : '' ?>" onclick="changeAuraColor('0xE53E3E', this)">Pathos</button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    
    <script>
        // --- ESTADO INICIAL DESDE PHP ---
        let currentGender = '<?= $defaultGender ?>'; 
        let currentOutfit = '<?= $defaultClothes ?>';
        let currentAura = '<?= $defaultAura ?>';
        
        // --- VARIABLES DE RIGGING DINÁMICO ---
        let armBonesLeft = [];
        let armBonesRight = [];
        let currentArmRotation = parseInt(document.getElementById('armRotationSlider').value);

        // --- 1. CONFIGURACIÓN BÁSICA ---
        const container = document.getElementById('canvas-container');
        const loadingOverlay = document.getElementById('loading-overlay');
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0xFAFAFC); 

        const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 100);
        camera.position.set(0, 1, 4); 

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        container.appendChild(renderer.domElement);

        // --- 2. CONTROLES DE CÁMARA ---
        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;
        controls.minDistance = 1.5; 
        controls.maxDistance = 7;
        controls.target.set(0, 0, 0); 

        // --- 3. ILUMINACIÓN ---
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.9);
        scene.add(ambientLight);
        const dirLight = new THREE.DirectionalLight(0xffffff, 0.6);
        dirLight.position.set(2, 5, 5);
        dirLight.castShadow = true;
        scene.add(dirLight);

        // --- 4. ENTORNOS ARTÍSTICOS ---
        const backgroundGroup = new THREE.Group();
        scene.add(backgroundGroup);

        function generateArtisticBackground(type) {
            while(backgroundGroup.children.length > 0){ 
                backgroundGroup.remove(backgroundGroup.children[0]); 
            }
            scene.background = new THREE.Color(0xFAFAFC); 

            if (type === '0x805AD5') {
                scene.background = new THREE.Color(0xF5F0FA); 
                const geo = new THREE.BufferGeometry();
                const vertices = [];
                for (let i = 0; i < 500; i++) {
                    vertices.push((Math.random() - 0.5) * 10, (Math.random() - 0.5) * 10, (Math.random() - 0.5) * 10);
                }
                geo.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
                const mat = new THREE.PointsMaterial({ color: 0x805AD5, size: 0.05 });
                const points = new THREE.Points(geo, mat);
                points.userData = { animationType: 'rotate_slow' };
                backgroundGroup.add(points);
            } 
            else if (type === '0x38A169') {
                scene.background = new THREE.Color(0xF0FAF5);
                for (let i = 1; i <= 3; i++) {
                    const geo = new THREE.TorusGeometry(1.5 + (i * 0.5), 0.01, 16, 100);
                    const mat = new THREE.MeshBasicMaterial({ color: 0x38A169, wireframe: true, transparent: true, opacity: 0.3 });
                    const torus = new THREE.Mesh(geo, mat);
                    torus.rotation.x = Math.random() * Math.PI;
                    torus.userData = { animationType: 'spin', speed: i * 0.002 };
                    backgroundGroup.add(torus);
                }
            }
            else if (type === '0x3182CE') {
                scene.background = new THREE.Color(0xF0F7FA);
                for (let i = 0; i < 5; i++) {
                    const geo = new THREE.IcosahedronGeometry(0.3, 0);
                    const mat = new THREE.MeshBasicMaterial({ color: 0x3182CE, wireframe: true, transparent: true, opacity: 0.5 });
                    const mesh = new THREE.Mesh(geo, mat);
                    mesh.position.set((Math.random() - 0.5) * 5, (Math.random() - 0.5) * 5, -2);
                    mesh.userData = { animationType: 'float', startY: mesh.position.y, speed: Math.random() * 0.02 };
                    backgroundGroup.add(mesh);
                }
            }
            else if (type === '0xE53E3E') {
                scene.background = new THREE.Color(0xFAF0F0);
                const geo = new THREE.TorusKnotGeometry(2, 0.2, 100, 16);
                const mat = new THREE.MeshBasicMaterial({ color: 0xE53E3E, wireframe: true, transparent: true, opacity: 0.1 });
                const knot = new THREE.Mesh(geo, mat);
                knot.userData = { animationType: 'pulse' };
                backgroundGroup.add(knot);
            }
        }

        // --- 5. LECTURA DE ARCHIVOS Y CORRECCIÓN DE POSE ---
        const loader = new THREE.GLTFLoader();
        const avatarGroup = new THREE.Group();
        scene.add(avatarGroup);
        let loadedParts = []; 

        function assembleAvatar() {
            loadingOverlay.style.display = 'flex';
            loadedParts.forEach(part => avatarGroup.remove(part));
            loadedParts = [];
            
            // Limpiamos los huesos en memoria antes de cargar el nuevo modelo
            armBonesLeft = [];
            armBonesRight = [];

            let path = `assets/3d/avatar/${currentOutfit}`;
            if (!currentOutfit.endsWith('.glb')) {
                path += '/scene.gltf';
            }

            loader.load(path, (gltf) => {
                let model = gltf.scene;

                // --- NORMALIZADOR AUTOMÁTICO DE ESCALA ---
                const box = new THREE.Box3().setFromObject(model);
                const size = box.getSize(new THREE.Vector3());
                
                if (size.y > 15) {
                    model.scale.set(0.01, 0.01, 0.01);
                } else if (size.y < 0.5) {
                    model.scale.set(5, 5, 5); 
                }
                
                const newBox = new THREE.Box3().setFromObject(model);
                const center = newBox.getCenter(new THREE.Vector3());
                model.position.y = -center.y; 
                model.position.x = -center.x;
                model.position.z = -center.z;
                
                model.traverse((node) => {
                    if (node.isMesh) {
                        node.castShadow = true;
                        node.receiveShadow = true;
                        if(node.material) node.material.depthWrite = true; 
                    }
                    
                    // --- RECOLECCIÓN DE HUESOS PARA ROTACIÓN ---
                    if (node.isBone) {
                        const bName = node.name.toLowerCase();
                        let rad = currentArmRotation * (Math.PI / 180);
                        
                        if (bName.includes('leftarm') || bName.includes('upperarm_l') || bName.includes('shoulder_l') || bName.includes('arm.l')) {
                            armBonesLeft.push(node);
                            node.rotation.z = rad; 
                        }
                        if (bName.includes('rightarm') || bName.includes('upperarm_r') || bName.includes('shoulder_r') || bName.includes('arm.r')) {
                            armBonesRight.push(node);
                            node.rotation.z = -rad; 
                        }
                    }
                });

                avatarGroup.add(model);
                loadedParts.push(model);
                
                generateArtisticBackground(currentAura);
                loadingOverlay.style.display = 'none';

            }, undefined, (error) => {
                console.error(`No se encontró: ${path}`);
                loadingOverlay.style.display = 'none';
            });
        }

        assembleAvatar();

        // --- FUNCIONES DE INTERFAZ Y ROTACIÓN ---
        function updateArmRotation(degrees) {
            document.getElementById('arm-rot-val').innerText = degrees;
            currentArmRotation = parseInt(degrees);
            
            let rad = currentArmRotation * (Math.PI / 180);
            
            // Actualiza en tiempo real los huesos mapeados
            armBonesLeft.forEach(bone => bone.rotation.z = rad);
            armBonesRight.forEach(bone => bone.rotation.z = -rad);
        }

        function setCameraMode(mode) {
            document.getElementById('btnCam3D').classList.remove('active');
            document.getElementById('btnCam2D').classList.remove('active');
            
            if (mode === '2d') {
                document.getElementById('btnCam2D').classList.add('active');
                camera.position.set(0, 0, 3.5);
                controls.target.set(0, 0, 0);
                controls.enableRotate = false;
            } else {
                document.getElementById('btnCam3D').classList.add('active');
                controls.enableRotate = true; 
            }
            controls.update();
        }

        function selectOption(category, value, btnElement) {
            if (category === 'clothes') {
                document.querySelectorAll('.option-btn[onclick^="selectOption(\'clothes\'"]').forEach(btn => btn.classList.remove('active'));
            } else {
                btnElement.parentElement.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('active'));
            }
            btnElement.classList.add('active');
            if (category === 'gender') currentGender = value;
            if (category === 'clothes') currentOutfit = value;
            assembleAvatar();
        }

        function changeAuraColor(type, btnElement) {
            btnElement.parentElement.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
            currentAura = type;
            generateArtisticBackground(currentAura);
        }

        // --- 7. GUARDADO ASÍNCRONO ---
        function saveConfiguration() {
            const btn = document.getElementById('btnSaveConfig');
            btn.classList.add('saving');
            btn.innerHTML = '<span>Guardando...</span>';

            const payload = {
                action: 'save_avatar',
                gender: currentGender,
                clothes: currentOutfit, 
                aura: currentAura
            };

            fetch('arbol_de_decisiones.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    btn.innerHTML = '<span>✅ Sincronizado</span>';
                    setTimeout(() => {
                        btn.innerHTML = '<span>Guardar Sincronización</span>';
                        btn.classList.remove('saving');
                    }, 2000);
                }
            })
        }

        // --- 8. BUCLE DE ANIMACIÓN ---
        function animate() {
            requestAnimationFrame(animate);
            controls.update();
            
            // Animación de respiración del avatar (Opcional, puede eliminarse si interfiere con rotaciones complejas)
            avatarGroup.position.y = -0.5 + Math.sin(Date.now() * 0.002) * 0.01;

            backgroundGroup.children.forEach(child => {
                if(child.userData && child.userData.animationType) {
                    const type = child.userData.animationType;
                    if(type === 'rotate_slow') {
                        child.rotation.y += 0.001;
                        child.rotation.x += 0.0005;
                    } else if (type === 'spin') {
                        child.rotation.y += child.userData.speed;
                        child.rotation.z += child.userData.speed * 0.5;
                    } else if (type === 'float') {
                        child.rotation.x += child.userData.speed;
                        child.position.y = child.userData.startY + Math.sin(Date.now() * 0.001) * 0.5;
                    } else if (type === 'pulse') {
                        child.rotation.y -= 0.002;
                        const scale = 1 + Math.sin(Date.now() * 0.002) * 0.05;
                        child.scale.set(scale, scale, scale);
                    }
                }
            });

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