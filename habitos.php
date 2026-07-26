<?php
session_start();
// Simulación de usuario para la maqueta visual
$user = [
    'username' => 'Daniel',
    'profession' => 'Ingeniería de Sistemas',
    'profile_picture' => ''
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Hábitos | APH OS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --bg-base: #FAFAFC; --bg-panel: #FFFFFF; --text-main: #1A202C; --text-muted: #718096; 
            --accent: #805AD5; --accent-hover: #6B46C1; --accent-light: rgba(128, 90, 213, 0.1); 
            --border-color: #E2E8F0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        /* ---------------------------------------------------
           SIDEBAR
           --------------------------------------------------- */
        nav.sidebar { width: 240px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; flex-shrink: 0; z-index: 20; }
        .brand { text-align: center; margin-bottom: 40px; } .brand h2 { font-weight: 800; letter-spacing: 2px; font-size: 1.5rem; color: var(--accent); }
        .nav-links { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; font-size: 0.9rem; color: var(--text-muted); text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s;}
        .nav-link:hover, .nav-link.active { background: var(--accent-light); color: var(--accent); }
        
        /* Perfil de Usuario Mini */
        .user-mini { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-color); margin-top: auto; }
        .avatar-circle { width: 35px; height: 35px; border-radius: 50%; background: #48BB78; color: white; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; font-weight: bold; }
        .user-info-mini h4 { font-size: 0.85rem; margin-bottom: 2px; }
        .user-info-mini p { font-size: 0.7rem; color: var(--text-muted); }
        .btn-logout { margin-top: 10px; text-align: center; font-size: 0.8rem; color: #E53E3E; text-decoration: none; font-weight: 600; }

        /* ---------------------------------------------------
           CONTENIDO PRINCIPAL
           --------------------------------------------------- */
        main { flex: 1; display: flex; flex-direction: column; padding: 40px 50px; overflow-y: auto; }
        .header-dash { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-shrink: 0; }
        .header-dash h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 8px; color: var(--text-main); }
        .header-dash p { color: var(--text-muted); font-size: 1rem; }
        .btn-primary { background: var(--accent); color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; }
        .btn-primary:hover { background: var(--accent-hover); }

        /* FILTROS TIPO PILL */
        .filter-container { display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; overflow-x: auto; }
        .filter-btn { padding: 8px 18px; border: 1px solid transparent; background: transparent; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; border-radius: 8px; cursor: pointer; transition: 0.2s; white-space: nowrap; }
        .filter-btn:hover { color: var(--text-main); }
        .filter-btn.active { border-color: var(--border-color); background: var(--bg-panel); color: var(--accent); box-shadow: 0 2px 5px rgba(0,0,0,0.03); }

        /* ---------------------------------------------------
           GRID DE TARJETAS (REPLICA EXACTA)
           --------------------------------------------------- */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; padding-bottom: 40px; }
        
        .card { 
            background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; 
            padding: 25px; display: flex; flex-direction: column; cursor: pointer; transition: 0.2s; 
        }
        .card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.06); border-color: var(--accent); }
        
        .card-category { font-size: 0.75rem; font-weight: 800; color: var(--accent); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .card-title { font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 12px; line-height: 1.3; }
        .card-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; flex-grow: 1; margin-bottom: 25px; }
        
        .card-footer { border-top: 1px solid var(--border-color); padding-top: 15px; display: flex; justify-content: space-between; align-items: center; }
        
        /* Badges de Nivel */
        .badge { padding: 5px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .lvl-1 { background: #EBF4FF; color: #3182CE; } /* Vagabundo - Azul */
        .lvl-2 { background: #E6FFFA; color: #319795; } /* Soñador - Verde Agua */
        .lvl-3 { background: #FEFCBF; color: #D69E2E; } /* Soldado - Dorado */
        .lvl-4 { background: #FEEBC8; color: #DD6B20; } /* Ejecutor - Naranja */
        
        .card-date { font-size: 0.8rem; color: var(--text-muted); }

        /* ---------------------------------------------------
           MODAL DE DETALLES PROFUNDOS
           --------------------------------------------------- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; opacity: 0; transition: opacity 0.2s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background: #fff; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; border-radius: 16px; padding: 40px; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.2); transform: scale(0.95); transition: transform 0.2s; }
        .modal-overlay.active .modal-content { transform: scale(1); }
        .close-btn { position: absolute; top: 20px; right: 25px; font-size: 2rem; cursor: pointer; border: none; background: none; color: var(--text-muted); line-height: 1; }
        .close-btn:hover { color: var(--text-main); }
        
        .modal-header-tag { display: inline-block; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); margin-bottom: 10px; }
        .modal-title { font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin-bottom: 25px; line-height: 1.2; }
        
        .section-title { font-size: 0.85rem; font-weight: 800; color: var(--text-main); text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid var(--border-color); padding-bottom: 5px; display: flex; align-items: center; gap: 8px; }
        .modal-text { font-size: 0.95rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 25px; }
        
        .progression-box { background: #F7FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid var(--accent); }
        .progression-step { margin-bottom: 15px; }
        .progression-step:last-child { margin-bottom: 0; }
        .progression-step strong { color: var(--text-main); display: block; margin-bottom: 4px; }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- SIDEBAR -->
        <nav class="sidebar">
            <div class="brand"><h2>A P H</h2></div>
            <div class="nav-links">
                <a href="#" class="nav-link">⌂ Panel Central</a>
                <a href="#" class="nav-link">👤 Estado Humano</a>
                <a href="#" class="nav-link">⏱ Registro Diario</a>
                <a href="#" class="nav-link active">🚀 Biblioteca de Hábitos</a>
                <a href="#" class="nav-link">🌳 Árbol de Habilidades</a>
            </div>
            
            <div class="user-mini">
                <div class="avatar-circle">DS</div>
                <div class="user-info-mini">
                    <h4>DS32</h4>
                    <p>Ing Sistemas</p>
                </div>
            </div>
            <a href="#" class="btn-logout">Cerrar Sesión</a>
        </nav>

        <!-- MAIN -->
        <main>
            <div class="header-dash">
                <div>
                    <h1>Biblioteca de Hábitos</h1>
                    <p>Catálogo interactivo de las 24 rutas de desarrollo. Selecciona y domina tus rutinas.</p>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filter-container" id="filterContainer">
                <button class="filter-btn active" data-filter="all">Todos</button>
                <button class="filter-btn" data-filter="academico">Académico</button>
                <button class="filter-btn" data-filter="laboral">Competencia Laboral</button>
                <button class="filter-btn" data-filter="financiero">Financiero</button>
                <button class="filter-btn" data-filter="salud">Salud y Fisiología</button>
                <button class="filter-btn" data-filter="autonomo">Aprendizaje Autónomo</button>
                <button class="filter-btn" data-filter="creativo">Creativo / Social</button>
            </div>

            <!-- GRID DE TARJETAS -->
            <div class="cards-grid" id="cardsGrid">
                <!-- Se inyecta vía JS -->
            </div>
        </main>
    </div>

    <!-- MODAL DE DETALLE -->
    <div id="habitModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <div class="modal-header-tag" id="modalCategory">CATEGORÍA - NIVEL X</div>
            <h2 class="modal-title" id="modalTitle">Título de la Rutina</h2>
            
            <div class="section-title">⚙️ Ejecución de la Rutina</div>
            <p class="modal-text" id="modalExecution">Instrucciones de qué hacer y cuándo hacerlo.</p>
            
            <div class="section-title">🔬 Fundamento Científico</div>
            <p class="modal-text" id="modalScience">El porqué funciona esto según la ciencia.</p>
            
            <div class="section-title">📈 Proyección a 4 Semanas</div>
            <div class="progression-box" id="modalProgression">
                <!-- Se inyecta la progresión -->
            </div>
        </div>
    </div>

    <script>
        // ---------------------------------------------------------
        // BASE DE DATOS DE LAS 24 RUTAS COMPLETAS
        // ---------------------------------------------------------
        const habitosData = [
            // COMPETENCIA LABORAL
            {
                id: 'lab1', focus: 'laboral', focusLabel: 'Competencia Laboral', level: 1, levelStr: 'Vagabundo', badgeClass: 'lvl-1',
                title: 'Desinfección del Entorno',
                desc: 'Reduce la carga cognitiva pasiva limpiando tu espacio 5 minutos antes del turno.',
                execution: 'Justo antes de iniciar tu turno laboral (Ej. 01:50 AM), dedica 5 minutos cronometrados a despejar tu escritorio de tazas, papeles y cierra pestañas del navegador que no vayas a usar.',
                science: 'Teoría de las Ventanas Rotas (Kelling & Wilson): Un entorno caótico o desordenado induce subconscientemente comportamientos caóticos y micro-distracciones.',
                prog: [
                    { week: 'Semana 1-2', text: 'Vencer la pereza inicial. Crear la memoria muscular de "preparar el campo de batalla" sin pensar.' },
                    { week: 'Semana 3-4', text: 'La limpieza se vuelve automática. Al reducir la saturación visual, tu cerebro libera RAM mental, preparándote para mantener la atención prolongada que exige el Nivel 2.' }
                ]
            },
            {
                id: 'lab2', focus: 'laboral', focusLabel: 'Competencia Laboral', level: 2, levelStr: 'Soñador', badgeClass: 'lvl-2',
                title: 'Práctica Aislada de Código',
                desc: '30 minutos escribiendo código o leyendo documentación sin saltar de tarea.',
                execution: 'Fuera de tu horario de soporte, define un bloque rígido de 30 minutos. Abre el IDE (PHP, Python) y ejecuta un script o lee documentación. Cero distracciones.',
                science: 'Intenciones de Implementación (Gollwitzer): Predefinir el "cuándo" y "dónde" exactos aumenta la tasa de ejecución en un 70% frente a depender de la motivación cruda.',
                prog: [
                    { week: 'Semana 1-2', text: 'Adaptación a los límites de tiempo. Aprender a soportar la urgencia de revisar el teléfono a los 10 minutos.' },
                    { week: 'Semana 3-4', text: 'Desarrollo de tolerancia a la frustración técnica. Al no poder escapar del problema cambiando de pestaña, fuerzas a tu cerebro a resolverlo, cimentando la disciplina del Nivel 3.' }
                ]
            },
            {
                id: 'lab3', focus: 'laboral', focusLabel: 'Competencia Laboral', level: 3, levelStr: 'Soldado', badgeClass: 'lvl-3',
                title: 'Triage y Deep Work',
                desc: 'Categorización despiadada de tareas y ejecución ininterrumpida durante el turno.',
                execution: 'A las 01:45 AM, categoriza los requerimientos por prioridad. Desde las 02:00 AM, atiende los tickets secuencialmente. Prohibida la multitarea entre problemas.',
                science: 'Teoría de la Carga Cognitiva (Sweller): El cerebro tiene memoria de trabajo limitada; externalizar prioridades y evitar el "context switching" evita el colapso mental bajo presión.',
                prog: [
                    { week: 'Semana 1-2', text: 'Eliminación del estrés por caos. Al estructurar antes de operar, la ansiedad del turno disminuye radicalmente.' },
                    { week: 'Semana 3-4', text: 'Sistematización del rendimiento. Operar en Deep Work te hace más rápido y preciso, permitiendo que te fijes en la macro-arquitectura de los sistemas (Requisito para el Nivel 4).' }
                ]
            },
            {
                id: 'lab4', focus: 'laboral', focusLabel: 'Competencia Laboral', level: 4, levelStr: 'Ejecutor', badgeClass: 'lvl-4',
                title: 'Desarrollo de Arquitectura Core',
                desc: 'Construcción y despliegue de módulos complejos apuntando al liderazgo.',
                execution: 'Asume requerimientos pesados (Bases de datos, integraciones API). Define bloques de 2-4 horas de inmersión absoluta para construir soluciones a nivel estructural de la empresa.',
                science: 'Estado de Flow (Csikszentmihalyi): Operar en el límite exacto entre el desafío técnico y la habilidad actual, logrando producción de software de alto nivel.',
                prog: [
                    { week: 'Semana 1-4', text: 'Mantenimiento del sistema. El usuario ya no lucha contra la fricción; la fricción es el combustible. Posicionamiento directo para ascensos, roles de desarrollo senior y control total del entorno.' }
                ]
            },

            // SALUD Y FISIOLOGÍA
            {
                id: 'sal1', focus: 'salud', focusLabel: 'Salud y Fisiología', level: 1, levelStr: 'Vagabundo', badgeClass: 'lvl-1',
                title: 'Hidratación y Activación Mínima',
                desc: 'Beber agua y hacer 10 sentadillas al despertar para romper la inercia.',
                execution: 'Inmediatamente al despertar (Ej. 15:30), bebe 500ml de agua y realiza 10 sentadillas. No es un entrenamiento, es un interruptor biológico.',
                science: 'Habit Stacking (James Clear): Anclar un micro-hábito a una acción biológica inevitable (despertar) puentea la motivación y garantiza la retención.',
                prog: [
                    { week: 'Semana 1-2', text: 'Eliminar el "snooze" de la alarma. El cuerpo aprende que despertar significa movimiento inmediato.' },
                    { week: 'Semana 3-4', text: 'Incremento sostenido de energía diurna. La inercia del sueño desaparece más rápido, alistando el sistema nervioso para sesiones de esfuerzo real (Nivel 2).' }
                ]
            },
            {
                id: 'sal2', focus: 'salud', focusLabel: 'Salud y Fisiología', level: 2, levelStr: 'Soñador', badgeClass: 'lvl-2',
                title: 'Exposición al Esfuerzo (45m)',
                desc: 'Asistencia innegociable al entrenamiento físico 3 veces por semana.',
                execution: 'Agenda 3 días a la semana. Tienes que entrenar 45 minutos. Si estás cansado, entrena mal o con poco peso, pero entrena. Lo innegociable es la asistencia.',
                science: 'Neuroplasticidad Inversa: Forzar al cuerpo a la fricción física cuando no quiere hacerlo reconstruye los receptores de dopamina, elevando la disciplina general.',
                prog: [
                    { week: 'Semana 1-2', text: 'Sufrimiento adaptativo. El cerebro protesta por el gasto calórico, pero el hábito de "presentarse" se solidifica.' },
                    { week: 'Semana 3-4', text: 'La resistencia mental se transfiere a otras áreas. Al tolerar el dolor físico, tolerar el aburrimiento del código o el trabajo se vuelve fácil (Paso al Nivel 3).' }
                ]
            },
            {
                id: 'sal3', focus: 'salud', focusLabel: 'Salud y Fisiología', level: 3, levelStr: 'Soldado', badgeClass: 'lvl-3',
                title: 'Higiene Circadiana y Sobrecarga',
                desc: 'Bloqueo de luz post-turno y rutina periodizada con registro de cargas.',
                execution: '1. Usa gafas bloqueadoras de luz o evita pantallas 1h antes de dormir en la mañana. 2. Entrena llevando un Excel/Bitácora de los pesos levantados para obligar al progreso.',
                science: 'Biología Circadiana y Sobrecarga Progresiva: La irregularidad lumínica destruye la melatonina. A nivel muscular, el músculo solo crece si la carga matemática de hoy supera a la de ayer.',
                prog: [
                    { week: 'Semana 1-2', text: 'Estabilización hormonal. Al proteger tu sueño diurno, la fatiga crónica desaparece.' },
                    { week: 'Semana 3-4', text: 'Transformación física cuantificable. El usuario opera como una máquina calibrada. Listo para optimizar el combustible (Nivel 4).' }
                ]
            },
            {
                id: 'sal4', focus: 'salud', focusLabel: 'Salud y Fisiología', level: 4, levelStr: 'Ejecutor', badgeClass: 'lvl-4',
                title: 'Meal Prep y Biométrica',
                desc: 'Planificación nutricional semanal y seguimiento estricto del sueño.',
                execution: 'El domingo cocinas todas las comidas de la semana. Registras tus macros y monitoreas la calidad del sueño (REM, Profundo) ajustando tu entorno.',
                science: 'Psiquiatría Nutricional: Sistematizar la alimentación elimina la "fatiga de decisión" diaria y asegura el suministro estable de aminoácidos para la recuperación del SNC.',
                prog: [
                    { week: 'Semana 1-4', text: 'Automatización biológica absoluta. El cuerpo ya no es una preocupación, sino el motor blindado que soporta el desgaste extremo de las metas laborales y académicas.' }
                ]
            },

            // ACADÉMICO UNIVERSITARIO
            {
                id: 'aca1', focus: 'academico', focusLabel: 'Académico', level: 1, levelStr: 'Vagabundo', badgeClass: 'lvl-1',
                title: 'Contacto Térmico (10m)',
                desc: 'Lee los apuntes de tu clase más reciente durante 10 minutos sin obligación de memorizar.',
                execution: 'Toma el sílabo o tus apuntes de la universidad y léelos 10 minutos al día. No intentes aprenderlo, solo míralo para que tu cerebro sepa que existe.',
                science: 'Curva del Olvido (Ebbinghaus): Repasar información en las primeras 24 horas, aunque sea superficialmente, aplana drásticamente la curva de pérdida de memoria.',
                prog: [
                    { week: 'Semana 1-2', text: 'Reducción de la ansiedad académica. El material deja de ser "desconocido" y amenazante.' },
                    { week: 'Semana 3-4', text: 'Se crea la costumbre de interactuar con la academia diariamente. Preparando la mente para concentrarse por períodos más largos (Nivel 2).' }
                ]
            },
            {
                id: 'aca2', focus: 'academico', focusLabel: 'Académico', level: 2, levelStr: 'Soñador', badgeClass: 'lvl-2',
                title: 'Inmersión Limitada (Pomodoro)',
                desc: '45 minutos de estudio enfocado en la evaluación más próxima.',
                execution: 'Coloca un cronómetro de 45 minutos. Teléfono fuera de la habitación. Estudia exclusivamente para la materia más urgente. Al sonar, detente inmediatamente.',
                science: 'Modos de Pensamiento (Oakley): Alternar entre el modo "Enfocado" (45m de tensión) y "Difuso" (descanso) permite al cerebro consolidar redes neuronales complejas en segundo plano.',
                prog: [
                    { week: 'Semana 1-2', text: 'Aprender a ignorar las distracciones durante ventanas cortas. Mejora en la retención de datos.' },
                    { week: 'Semana 3-4', text: 'Aumento de la resistencia cognitiva. El estudiante deja de estudiar la noche anterior y empieza a asimilar conceptos lógicos (Listo para Nivel 3).' }
                ]
            },
            {
                id: 'aca3', focus: 'academico', focusLabel: 'Académico', level: 3, levelStr: 'Soldado', badgeClass: 'lvl-3',
                title: 'Ataque a Materias Filtro',
                desc: 'Bloques de 2 horas aisladas dedicadas exclusivamente a materias de alta complejidad.',
                execution: 'Aisla 2 horas para enfrentar problemas de matemáticas, lógica algorítmica o física. Resuelve ejercicios hasta llegar al fallo, sin buscar la respuesta inmediatamente.',
                science: 'Práctica Deliberada (Ericsson): El aprendizaje real solo ocurre al enfrentarse a problemas que están ligeramente por encima de tu competencia actual, forzando la adaptación.',
                prog: [
                    { week: 'Semana 1-2', text: 'Alta frustración inicial, seguida de desobstrucción de cuellos de botella académicos. Notas un salto en comprensión analítica.' },
                    { week: 'Semana 3-4', text: 'Dominio de la carrera. Las materias difíciles ya no asustan. Te vuelves capaz de desarmar cualquier problema (Evolución a Nivel 4).' }
                ]
            },
            {
                id: 'aca4', focus: 'academico', focusLabel: 'Académico', level: 4, levelStr: 'Ejecutor', badgeClass: 'lvl-4',
                title: 'Ingeniería Inversa del Sílabo',
                desc: 'Estudio adelantado al currículo universitario y creación de bases de conocimiento.',
                execution: 'No esperes a que el profesor dicte el tema. Lee el sílabo, investiga por tu cuenta, construye aplicaciones prácticas con esa teoría y usa Zettelkasten/Notion para interconectar conceptos.',
                science: 'Técnica Feynman y Constructivismo: La comprensión suprema se alcanza cuando desensamblas el conocimiento y lo conectas con proyectos reales, no solo para aprobar exámenes.',
                prog: [
                    { week: 'Semana 1-4', text: 'Operación en el top 1%. El sistema universitario se vuelve fácil porque tu ritmo de aprendizaje autónomo es muy superior al académico tradicional.' }
                ]
            },

            // DESARROLLO FINANCIERO (Condensados para el código, siguiendo el mismo patrón)
            {
                id: 'fin1', focus: 'financiero', focusLabel: 'Financiero', level: 1, levelStr: 'Vagabundo', badgeClass: 'lvl-1',
                title: 'Registro de Impacto Único', desc: 'Registrar un (1) solo gasto del día en tu aplicación o excel.',
                execution: 'Anota solo el gasto más grande o representativo del día. Toma 30 segundos.', science: 'Micro-hábitos (Fogg): Bajar la motivación requerida a casi cero asegura la ejecución en días de baja energía.', prog: [ { week: 'S1-S4', text: 'Construyes la memoria muscular de auditar tu dinero. Es el puente para crear presupuestos reales en el Nivel 2.' } ]
            },
            {
                id: 'fin2', focus: 'financiero', focusLabel: 'Financiero', level: 2, levelStr: 'Soñador', badgeClass: 'lvl-2',
                title: 'Auditoría de Flujo Semanal', desc: '15 minutos el domingo revisando los gastos de la semana contra un presupuesto.',
                execution: 'Compara lo que gastaste vs lo que planeaste. Ajusta tu comportamiento para la siguiente semana.', science: 'Ley de Parkinson: Los gastos se expanden hasta cubrir los ingresos. Presupuestar restringe esa expansión.', prog: [ { week: 'S1-S4', text: 'Tomas control de la fuga de capital, generando excedentes que se utilizarán en el Nivel 3.' } ]
            },
            {
                id: 'fin3', focus: 'financiero', focusLabel: 'Financiero', level: 3, levelStr: 'Soldado', badgeClass: 'lvl-3',
                title: 'Automatización de Escudo', desc: 'Débito automático hacia fondos de inversión/ahorro el día de pago.',
                execution: 'Configura el banco para que apenas entre el salario, el 10-20% desaparezca a otra cuenta.', science: 'Economía Conductual (Thaler): La arquitectura de decisiones automatizada vence al sesgo del presente.', prog: [ { week: 'S1-S4', text: 'Creación de riqueza pasiva garantizada. Ya no dependes de tu fuerza de voluntad para ahorrar.' } ]
            },
            {
                id: 'fin4', focus: 'financiero', focusLabel: 'Financiero', level: 4, levelStr: 'Ejecutor', badgeClass: 'lvl-4',
                title: 'Construcción de Asimetría', desc: 'Desarrollo de fuentes de ingresos escalables (ej. Tiendas online automatizadas).',
                execution: 'Dedica el fin de semana a integrar pasarelas de pago (Square) y catálogos en Firebase para proyectos e-commerce.', science: 'Efecto Compuesto: Los sistemas que funcionan independientemente del tiempo invertido generan curvas exponenciales.', prog: [ { week: 'S1-S4', text: 'Desacople del tiempo y el dinero. Operación empresarial.' } ]
            },

            // APRENDIZAJE AUTÓNOMO
            {
                id: 'aut1', focus: 'autonomo', focusLabel: 'Aprendizaje', level: 1, levelStr: 'Vagabundo', badgeClass: 'lvl-1',
                title: 'Consumo Pasivo Estratégico', desc: 'Escuchar 10m de podcast técnico/inglés mientras haces tareas mecánicas.',
                execution: 'Pon un podcast mientras vas en bus o cocinas. No tomes notas.', science: 'Teoría de la Inmersión (Krashen): Adquisición de estructuras de lenguaje/lógica mediante exposición de baja presión.', prog: [ { week: 'S1-S4', text: 'Acondicionamiento del oído y mente. Preparación para estudio activo en Nivel 2.' } ]
            },
            {
                id: 'aut2', focus: 'autonomo', focusLabel: 'Aprendizaje', level: 2, levelStr: 'Soñador', badgeClass: 'lvl-2',
                title: 'Replicación de Modelos', desc: 'Seguir un tutorial técnico (30m) replicando el código paso a paso.',
                execution: 'Abre el tutorial y tu editor. Escribe exactamente lo mismo que el instructor. Cero redes sociales.', science: 'Recuerdo Activo: Recuperar información para escribir genera conexiones más fuertes que leer.', prog: [ { week: 'S1-S4', text: 'Aprendes la sintaxis y estructura básica, perdiendo el miedo al lienzo en blanco.' } ]
            },
            {
                id: 'aut3', focus: 'autonomo', focusLabel: 'Aprendizaje', level: 3, levelStr: 'Soldado', badgeClass: 'lvl-3',
                title: 'Resolución sin Asistencia', desc: 'Solucionar lógica de programación (1h) sin ver tutoriales.',
                execution: 'Intenta construir la función o arreglar el bug por tu cuenta. Solo puedes leer documentación, no videos.', science: 'Dificultad Deseable (Bjork): A mayor esfuerzo para recuperar información, mayor retención.', prog: [ { week: 'S1-S4', text: 'Transición de "copiador de código" a "solucionador de problemas". Nivel técnico real adquirido.' } ]
            },
            {
                id: 'aut4', focus: 'autonomo', focusLabel: 'Aprendizaje', level: 4, levelStr: 'Ejecutor', badgeClass: 'lvl-4',
                title: 'Construcción de Sistemas', desc: 'Construir aplicaciones transversales desde cero.',
                execution: 'Diseña el backend, base de datos y frontend de una app (ej. APH) de forma autónoma.', science: 'Constructivismo (Piaget): El conocimiento se asimila al superar fallos sistémicos en entornos reales.', prog: [ { week: 'S1-S4', text: 'Dominio absoluto de la habilidad. Eres un creador independiente.' } ]
            },

            // DESARROLLO CREATIVO / SOCIAL
            {
                id: 'cre1', focus: 'creativo', focusLabel: 'Creativo / Social', level: 1, levelStr: 'Vagabundo', badgeClass: 'lvl-1',
                title: 'Interacción Deliberada', desc: '1 partida rápida de juego (Mobile Legends) estrictamente limitada, o 1 mensaje a familiares.',
                execution: 'Juega por diversión o comunícate, pero pon un temporizador. Al terminar, cierra la app.', science: 'Línea Base de Dopamina (Lembke): Limitar estímulos de alta recompensa previene el agotamiento de receptores.', prog: [ { week: 'S1-S4', text: 'Recuperas el control de tus descansos. El ocio deja de ser una fuga de tiempo incontrolable.' } ]
            },
            {
                id: 'cre2', focus: 'creativo', focusLabel: 'Creativo / Social', level: 2, levelStr: 'Soñador', badgeClass: 'lvl-2',
                title: 'Gestión de Comunidad / Hobby', desc: 'Dedicar 45 minutos ininterrumpidos a administrar proyectos pasionales (servidores, foros).',
                execution: 'Fin de semana: Entra a gestionar tus servidores multijugador o hobbies creativos con enfoque, sin mezclarlo con trabajo.', science: 'Autodeterminación (Deci & Ryan): La motivación intrínseca florece cuando fomentas autonomía en entornos no obligatorios.', prog: [ { week: 'S1-S4', text: 'Creas salidas creativas saludables que recargan tu energía mental para la semana laboral.' } ]
            },
            {
                id: 'cre3', focus: 'creativo', focusLabel: 'Creativo / Social', level: 3, levelStr: 'Soldado', badgeClass: 'lvl-3',
                title: 'Bloqueo Off-Screen', desc: 'Tiempo de calidad innegociable con familia/pareja. Teléfono lejos.',
                execution: 'Pasa tiempo con tu madre, hermana o novia. El celular se queda en modo avión en otro cuarto.', science: 'Línea Base Social (Coan): La proximidad física y atención plena con vínculos cercanos reduce mediblemente la carga alostática (estrés).', prog: [ { week: 'S1-S4', text: 'Reparación del tejido social. Evitas el "burnout" y aislamiento que produce el exceso de trabajo en sistemas.' } ]
            },
            {
                id: 'cre4', focus: 'creativo', focusLabel: 'Creativo / Social', level: 4, levelStr: 'Ejecutor', badgeClass: 'lvl-4',
                title: 'Producción de Alto Estándar', desc: 'Desarrollo de proyectos visuales/creativos combinando tecnología y pasión.',
                execution: 'Usa tus habilidades para crear algo valioso y estético (Ej. diseño de avatares 3D, retratos familiares estructurados).', science: 'Aprendizaje Basado en Proyectos (PBL): Integrar pasiones con estándares técnicos maximiza el crecimiento transversal.', prog: [ { week: 'S1-S4', text: 'Equilibrio perfecto. La creatividad se convierte en obras terminadas, no solo en ideas sueltas.' } ]
            }
        ];

        // ---------------------------------------------------------
        // RENDERIZADO Y LÓGICA DE FILTROS
        // ---------------------------------------------------------
        function renderCards(filter = 'all') {
            const grid = document.getElementById('cardsGrid');
            grid.innerHTML = '';
            
            const filteredData = filter === 'all' ? habitosData : habitosData.filter(h => h.focus === filter);
            
            filteredData.forEach(h => {
                // Formato de fecha simulada (mes/año actual)
                const dateStr = "Jul 2026";
                
                const cardHTML = `
                    <div class="card" onclick="openModal('${h.id}')">
                        <div class="card-category">${h.focusLabel}</div>
                        <h3 class="card-title">${h.title}</h3>
                        <p class="card-desc">${h.desc}</p>
                        <div class="card-footer">
                            <span class="badge ${h.badgeClass}">Nivel ${h.level}: ${h.levelStr}</span>
                            <span class="card-date">Activo &bull; ${dateStr}</span>
                        </div>
                    </div>
                `;
                grid.innerHTML += cardHTML;
            });
        }

        // Filtros Event Listeners
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                renderCards(e.target.dataset.filter);
            });
        });

        // ---------------------------------------------------------
        // LÓGICA DEL MODAL
        // ---------------------------------------------------------
        function openModal(id) {
            const h = habitosData.find(item => item.id === id);
            if(!h) return;

            document.getElementById('modalCategory').innerText = `${h.focusLabel} — NIVEL ${h.level} (${h.levelStr})`;
            document.getElementById('modalTitle').innerText = h.title;
            document.getElementById('modalExecution').innerText = h.execution;
            document.getElementById('modalScience').innerText = h.science;
            
            // Render Progreso
            let progHtml = '';
            h.prog.forEach(p => {
                progHtml += `<div class="progression-step"><strong>${p.week}</strong>${p.text}</div>`;
            });
            document.getElementById('modalProgression').innerHTML = progHtml;

            document.getElementById('habitModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('habitModal').classList.remove('active');
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', () => {
            renderCards('all');
        });
    </script>
</body>
</html>