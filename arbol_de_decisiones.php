<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
// Simulación de datos de personalización (esto vendría de tu DB)
$personalizacion = ['color_aura' => '#805AD5', 'nivel_bio' => 5];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sincronización de Avatar | APH OS</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;600&display=swap" rel="stylesheet">
    <style>
        :root { --glow: #805AD5; --panel: rgba(10, 10, 15, 0.9); }
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #050505; font-family: 'Inter', sans-serif; color: white; }
        
        #canvas-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; cursor: pointer; }
        
        /* UI Overlay */
        .overlay { position: relative; z-index: 10; pointer-events: none; height: 100%; width: 100%; display: flex; justify-content: space-between; padding: 40px; box-sizing: border-box; }
        .side-panel { pointer-events: auto; background: var(--panel); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 25px; backdrop-filter: blur(10px); width: 300px; box-shadow: 0 0 30px rgba(0,0,0,0.5); }
        
        .title-area h1 { font-family: 'Orbitron', sans-serif; font-size: 1.5rem; letter-spacing: 4px; color: var(--glow); text-shadow: 0 0 15px var(--glow); }
        
        /* Botones de Personalización */
        .custom-group { margin-bottom: 25px; }
        .custom-group label { display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; color: #718096; }
        .btn-custom { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 10px; width: 100%; border-radius: 8px; cursor: pointer; margin-bottom: 8px; transition: 0.3s; font-size: 0.8rem; }
        .btn-custom:hover { background: var(--glow); border-color: white; box-shadow: 0 0 15px var(--glow); }

        .center-msg { position: absolute; bottom: 50px; left: 50%; transform: translateX(-50%); text-align: center; }
        .btn-enter { pointer-events: auto; background: var(--glow); color: white; border: none; padding: 15px 40px; border-radius: 50px; font-family: 'Orbitron'; font-weight: bold; cursor: pointer; animation: pulse 2s infinite; }
        
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(128, 90, 213, 0.7); } 70% { box-shadow: 0 0 0 20px rgba(128, 90, 213, 0); } 100% { box-shadow: 0 0 0 0 rgba(128, 90, 213, 0); } }
    </style>
</head>
<body>

    <div id="canvas-container"></div>

    <div class="overlay">
        <div class="side-panel left">
            <div class="title-area">
                <h1>IDENTIDAD<br>APH-OS</h1>
                <p style="font-size: 0.8rem; color: #a0aec0;">Sincronización de Red Neuronal v2.4</p>
            </div>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 0;">
            <div class="stats">
                <div style="margin-bottom: 15px;">
                    <span style="font-size: 0.7rem;">ESTABILIDAD SOMA</span>
                    <div style="background: #1a202c; height: 6px; border-radius: 3px; margin-top: 5px;">
                        <div style="background: var(--glow); width: 75%; height: 100%; border-radius: 3px; box-shadow: 0 0 10px var(--glow);"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="side-panel right">
            <div class="custom-group">
                <label>Malla de Energía (Aura)</label>
                <button class="btn-custom" onclick="updateAura('#805AD5')">Violeta (Psique)</button>
                <button class="btn-custom" onclick="updateAura('#38A169')">Esmeralda (Soma)</button>
                <button class="btn-custom" onclick="updateAura('#E53E3E')">Carmesí (Pathos)</button>
            </div>
            <div class="custom-group">
                <label>Visualización</label>
                <button class="btn-custom" onclick="setWireframe(true)">Modo Estructura</button>
                <button class="btn-custom" onclick="setWireframe(false)">Modo Sólido</button>
            </div>
        </div>
    </div>

    <div class="center-msg">
        <p style="margin-bottom: 20px; font-size: 0.9rem; letter-spacing: 2px;">PRESIONA EL AVATAR PARA EXPANDIR CONOCIMIENTO</p>
        <button class="btn-enter" onclick="window.location.href='habilidades.php'">ABRIR ÁRBOL DE HABILIDADES</button>
    </div>

    <script>
        let scene, camera, renderer, avatar, aura;

        function init() {
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            document.getElementById('canvas-container').appendChild(renderer.domElement);

            // Luz
            const light = new THREE.PointLight(0x805AD5, 2, 100);
            light.position.set(0, 10, 10);
            scene.add(light);
            scene.add(new THREE.AmbientLight(0x404040));

            // Representación de Avatar (Simplificada para el ejemplo)
            // Aquí cargarías un modelo GLTF con GLTFLoader
            const geometry = new THREE.ConeGeometry(2, 5, 32);
            const material = new THREE.MeshPhongMaterial({ color: 0x1a202c, shininess: 100 });
            avatar = new THREE.Mesh(geometry, material);
            scene.add(avatar);

            // Aura (Glow effect)
            const auraGeo = new THREE.SphereGeometry(3.5, 32, 32);
            const auraMat = new THREE.MeshBasicMaterial({ color: 0x805AD5, wireframe: true, transparent: true, opacity: 0.2 });
            aura = new THREE.Mesh(auraGeo, auraMat);
            scene.add(aura);

            camera.position.z = 10;
        }

        function updateAura(color) {
            aura.material.color.set(color);
        }

        function setWireframe(val) {
            avatar.material.wireframe = val;
        }

        function animate() {
            requestAnimationFrame(animate);
            avatar.rotation.y += 0.01;
            aura.rotation.y -= 0.005;
            renderer.render(scene, camera);
        }

        init();
        animate();

        window.addEventListener('resize', () => {
            renderer.setSize(window.innerWidth, window.innerHeight);
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
        });
    </script>
</body>
</html>