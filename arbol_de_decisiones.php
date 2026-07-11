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

// Extraer configuración guardada o usar valores por defecto
$savedConfig = json_decode($user['avatar_config'] ?? '{}', true);
$defaultGender = $savedConfig['gender'] ?? 'Male';
$defaultClothes = $savedConfig['clothes'] ?? 'Peasant';
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
                        <button class="option-btn <?= $defaultGender === 'Male' ? 'active' : '' ?>" onclick="selectOption('gender', 'Male', this)">Cuerpo A</button>
                        <button class="option-btn <?= $defaultGender === 'Female' ? 'active' : '' ?>" onclick="selectOption('gender', 'Female', this)">Cuerpo B</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Proyección <span>Estado</span></h3>
                    <div class="options-grid cols-3">
                        <button class="option-btn <?= $defaultClothes === 'Peasant' ? 'active' : '' ?>" onclick="selectOption('clothes', 'Peasant', this)">Base Sintética</button>
                        <button class="option-btn <?= $defaultClothes === 'Ranger' ? 'active' : '' ?>" onclick="selectOption('clothes', 'Ranger', this)">Explorador</button>
                        <button class="option-btn <?= $defaultClothes === 'Superhero' ? 'active' : '' ?>" onclick="selectOption('clothes', 'Superhero', this)">Superhéroe</button>
                    </div>
                </div>

                <div class="custom-card">
                    <h3>Entorno Artístico <span>Aura</span></h3>
                    <div class="options-grid cols-3">
                        <button class="option-btn <?= $defaultAura === 'none' ? 'active' : '' ?>" onclick="changeAuraColor('none', this)">Fondo Neutro</button>
                        <button class="option-btn <?= $defaultAura === '0x805AD5' ? 'active' : '' ?>" onclick="changeAuraColor('0x805AD5', this)">Psique (Polvo Estelar)</button>
                        <button class="option-btn <?= $defaultAura === '0x38A169' ? 'active' : '' ?>" onclick="changeAuraColor('0x38A169', this)">Soma (Anillos)</button>
                        <button class="option-btn <?= $defaultAura === '0x3182CE' ? 'active' : '' ?>" onclick="changeAuraColor('0x3182CE', this)">Pneuma (Geometría)</button>
                        <button class="option-btn <?= $defaultAura === '0xE53E3E' ? 'active' : '' ?>" onclick="changeAuraColor('0xE53E3E', this)">Pathos (Nudo Caótico)</button>
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
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
        scene.add(ambientLight);
        const dirLight = new THREE.DirectionalLight(0xffffff, 0.6);
        dirLight.position.set(2, 5, 5);
        dirLight.castShadow = true;
        scene.add(dirLight);

        // --- 4. ENTORNOS ARTÍSTICOS (BACKGROUNDS) ---
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

        // --- 5. LECTURA ESTRICTA DE ARCHIVOS GLTF Y CORRECCIÓN DE POSE ---
        const loader = new THREE.GLTFLoader();
        const avatarGroup = new THREE.Group();
        avatarGroup.position.y = -1; 
        scene.add(avatarGroup);
        let loadedParts = []; 

        function assembleAvatar() {
            loadingOverlay.style.display = 'flex';
            loadedParts.forEach(part => avatarGroup.remove(part));
            loadedParts = [];

            let partsToLoad = [];
            let prefix = `${currentGender}_${currentOutfit}`;
            
            // Asignación de partes según el atuendo
            if (currentOutfit === 'Peasant') {
                partsToLoad = ['_Arms', '_Body', '_Feet', '_Legs'];
            } else if (currentOutfit === 'Ranger') {
                if (currentGender === 'Male') {
                    partsToLoad = ['_Acc_Pauldron', '_Arms', '_Body', '_Feet_Boots', '_Head_Hood', '_Legs']; 
                } else {
                    partsToLoad = ['_Acc_Pauldrons', '_Arms', '_Body', '_Feet', '_Head_Hood', '_Legs']; 
                }
            } else if (currentOutfit === 'Superhero') {
                // Altera el prefijo para buscar Superhero_Male_FullBody.gltf o Superhero_Female_FullBody.gltf
                prefix = `Superhero_${currentGender}`;
                partsToLoad = ['_FullBody'];
            }

            let loadPromises = partsToLoad.map(part => {
                return new Promise((resolve) => {
                    let filename = `${prefix}${part}.gltf`;
                    let path = `assets/3d/avatar/${filename}`; 

                    loader.load(path, (gltf) => {
                        let model = gltf.scene;
                        
                        model.traverse((node) => {
                            if (node.isMesh) {
                                node.castShadow = true;
                                node.receiveShadow = true;
                            }
                            
                            // Corrección de pose a A-Pose para todos los modelos
                            if (node.isBone) {
                                const boneName = node.name.toLowerCase();
                                
                                if (boneName.includes('leftarm') || boneName.includes('upperarm_l') || (boneName.includes('shoulder') && boneName.includes('l'))) {
                                    node.rotation.z += 1.0; 
                                }
                                if (boneName.includes('rightarm') || boneName.includes('upperarm_r') || (boneName.includes('shoulder') && boneName.includes('r'))) {
                                    node.rotation.z -= 1.0; 
                                }
                            }
                        });
                        resolve(model);
                    }, undefined, (error) => {
                        console.error(`No se encontró: ${filename}`);
                        resolve(null); 
                    });
                });
            });

            Promise.all(loadPromises).then(models => {
                models.forEach(model => {
                    if(model) {
                        avatarGroup.add(model);
                        loadedParts.push(model);
                    }
                });
                
                generateArtisticBackground(currentAura);
                loadingOverlay.style.display = 'none';
            });
        }

        assembleAvatar();

        // --- 6. FUNCIONES DE INTERFAZ Y CÁMARA ---
        function setCameraMode(mode) {
            document.getElementById('btnCam3D').classList.remove('active');
            document.getElementById('btnCam2D').classList.remove('active');
            
            if (mode === '2d') {
                document.getElementById('btnCam2D').classList.add('active');
                camera.position.set(0, 1, 4);
                controls.target.set(0, 0, 0);
                controls.enableRotate = false;
            } else {
                document.getElementById('btnCam3D').classList.add('active');
                controls.enableRotate = true; 
            }
            controls.update();
        }

        function selectOption(category, value, btnElement) {
            const grid = btnElement.parentElement;
            grid.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
            
            if (category === 'gender') currentGender = value;
            if (category === 'clothes') currentOutfit = value;
            assembleAvatar();
        }

        function changeAuraColor(type, btnElement) {
            const grid = btnElement.parentElement;
            grid.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('active'));
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
            
            // Animación de respiración del avatar
            avatarGroup.position.y = -1 + Math.sin(Date.now() * 0.002) * 0.02;

            // Animar los fondos artísticos
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