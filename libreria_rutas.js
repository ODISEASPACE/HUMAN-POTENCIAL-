/**
 * LIBRERÍA CENTRAL DE 24 RUTAS (APH OS)
 * Estructura: objetivo -> nivel -> array de bloques diarios
 */
const LibreriaRutas = {
    // ---------------------------------------------------------
    // 1. COMPETENCIA LABORAL
    // ---------------------------------------------------------
    laboral: {
        1: [ // VAGABUNDO
            {
                time: "07:50",
                title: "Desinfección del Entorno",
                desc: "5 minutos para limpiar el escritorio físico y cerrar pestañas irrelevantes antes de empezar. Reduce la carga cognitiva pasiva.",
                ciencia: "Teoría de las Ventanas Rotas (Kelling & Wilson): Un entorno caótico induce comportamientos caóticos y micro-distracciones."
            }
        ],
        2: [ // SOÑADOR
            {
                time: "08:00",
                title: "Práctica Aislada (Código)",
                desc: "30 minutos escribiendo código (ej. PHP, Python) o estudiando documentación técnica de la empresa, sin saltar de tarea.",
                ciencia: "Intenciones de Implementación (Gollwitzer): Predefinir el 'cuándo' y 'dónde' aumenta la tasa de ejecución en un 70% frente a la motivación cruda."
            }
        ],
        3: [ // SOLDADO
            {
                time: "01:45",
                title: "Triage de Turno / Tickets",
                desc: "Categorización despiadada de tareas urgentes antes de iniciar un turno de alta exigencia. Cero multitarea.",
                ciencia: "Teoría de la Carga Cognitiva (Sweller): El cerebro tiene memoria de trabajo limitada; externalizar las prioridades evita el colapso mental bajo presión."
            },
            {
                time: "02:00",
                title: "Bloque Operativo Profundo",
                desc: "Ejecución continua de responsabilidades laborales (ej. soporte, análisis) utilizando bloques de tiempo ininterrumpidos.",
                ciencia: "Deep Work (Cal Newport): La capacidad de concentrarse sin distracciones empuja las habilidades cognitivas a su límite, generando valor real."
            }
        ],
        4: [ // EJECUTOR
            {
                time: "09:00",
                title: "Desarrollo de Arquitectura Core",
                desc: "Construcción y despliegue de módulos complejos (bases de datos relacionales, integraciones). Posicionamiento para liderazgo.",
                ciencia: "Estado de Flow (Csikszentmihalyi): Operar en el límite exacto entre el desafío técnico y la habilidad actual, anulando la noción del tiempo."
            }
        ]
    },

    // ---------------------------------------------------------
    // 2. SALUD Y FISIOLOGÍA
    // ---------------------------------------------------------
    salud: {
        1: [
            {
                time: "AM",
                title: "Hidratación + Activación Mínima",
                desc: "Beber 500ml de agua inmediatamente al despertar y hacer 10 sentadillas. Se trata de romper la inercia del sueño, no de entrenar.",
                ciencia: "Habit Stacking (James Clear): Anclar un micro-hábito a una acción biológica inevitable (despertar) garantiza la retención del hábito."
            }
        ],
        2: [
            {
                time: "18:00",
                title: "Exposición al Esfuerzo (45m)",
                desc: "Asistencia innegociable al gimnasio o entrenamiento en casa 3 veces por semana. El objetivo es presentarse, incluso si el rendimiento es bajo.",
                ciencia: "Neuroplasticidad Inversa: Forzar al cuerpo a la fricción física reconstruye los receptores de dopamina, elevando la tolerancia al aburrimiento."
            }
        ],
        3: [
            {
                time: "AM/PM",
                title: "Higiene del Ritmo Circadiano",
                desc: "Horarios de sueño estrictos (bloqueo de luz azul 1h antes de dormir). Vital para evitar la fatiga crónica si se opera en turnos de madrugada (ej. 2:00 AM a 8:00 AM).",
                ciencia: "Biología Circadiana (Huberman Lab): La irregularidad en la exposición a la luz destruye la producción de melatonina y cortisol, destrozando la energía del día siguiente."
            },
            {
                time: "17:00",
                title: "Entrenamiento Estructurado",
                desc: "Rutina de hipertrofia o resistencia con registro de cargas.",
                ciencia: "Sobrecarga Progresiva: El músculo solo se adapta si el estímulo supera su capacidad actual de forma matemática y medible."
            }
        ],
        4: [
            {
                time: "DOMINGO",
                title: "Meal Prep y Biométrica",
                desc: "Planificación nutricional semanal en tuppers. Seguimiento de macronutrientes y calidad de sueño.",
                ciencia: "Psiquiatría Nutricional: El intestino produce el 90% de la serotonina del cuerpo. Sistematizar la comida elimina la fatiga de decisión diaria."
            }
        ]
    },

    // ---------------------------------------------------------
    // 3. ACADÉMICO UNIVERSITARIO
    // ---------------------------------------------------------
    academico: {
        1: [
            {
                time: "PM",
                title: "Contacto Térmico (10m)",
                desc: "Leer los apuntes de la clase más reciente durante 10 minutos. No se requiere memorizar, solo exponer al cerebro a la información.",
                ciencia: "Curva del Olvido (Ebbinghaus): Repasar información en las primeras 24 horas aplana drásticamente la curva de pérdida de memoria."
            }
        ],
        2: [
            {
                time: "15:00",
                title: "Inmersión Limitada (Pomodoro)",
                desc: "45 minutos de estudio enfocado estrictamente en la materia más próxima a evaluación. Cero teléfono.",
                ciencia: "Modos de Pensamiento (Oakley): Alternar entre el modo 'Enfocado' (45m) y 'Difuso' (descanso) permite consolidar redes neuronales complejas."
            }
        ],
        3: [
            {
                time: "14:00",
                title: "Ataque a Materias Filtro",
                desc: "2 horas aisladas dedicadas exclusivamente a materias de alta complejidad (lógica algorítmica, matemáticas aplicadas).",
                ciencia: "Práctica Deliberada (Ericsson): El aprendizaje real solo ocurre al enfrentarse a problemas que están ligeramente por encima de la competencia actual."
            }
        ],
        4: [
            {
                time: "ANY",
                title: "Ingeniería Inversa del Sílabo",
                desc: "Estudio adelantado al currículo universitario. Creación de una base de conocimiento interconectada (Zettelkasten).",
                ciencia: "Técnica Feynman: La comprensión suprema se alcanza cuando puedes desensamblar un concepto complejo y explicarlo en términos simples."
            }
        ]
    },

    // ---------------------------------------------------------
    // 4. DESARROLLO FINANCIERO
    // ---------------------------------------------------------
    financiero: {
        1: [
            {
                time: "ANY",
                title: "Registro de Impacto Único",
                desc: "Registrar un (1) solo gasto del día en tu sistema. Se busca generar la memoria muscular de auditar el dinero.",
                ciencia: "Teoría de los Micro-hábitos (Fogg): Bajar la motivación requerida a casi cero asegura la ejecución en los días de menor energía."
            }
        ],
        2: [
            {
                time: "DOMINGO",
                title: "Auditoría de Flujo (15m)",
                desc: "Revisar los gastos de la semana contra un presupuesto rígido. Ajustar desviaciones inmediatamente.",
                ciencia: "Ley de Parkinson: Los gastos se expanden hasta cubrir todos los ingresos disponibles. Presupuestar restringe esa expansión artificialmente."
            }
        ],
        3: [
            {
                time: "DÍA DE PAGO",
                title: "Automatización de Escudo",
                desc: "Configuración bancaria para debitar automáticamente un % del salario hacia ahorros o fondos de emergencia antes de verlo.",
                ciencia: "Economía Conductual (Thaler): La 'arquitectura de decisiones' automatizada vence al sesgo del presente y la debilidad de voluntad."
            }
        ],
        4: [
            {
                time: "FIN DE SEMANA",
                title: "Construcción de Asimetría",
                desc: "Desarrollo de fuentes de ingresos escalables (ej. integración de pasarelas de pago y proveedores para e-commerce automatizado).",
                ciencia: "Efecto Compuesto: Los sistemas que funcionan independientemente del tiempo invertido (software/tiendas) generan curvas de crecimiento exponenciales."
            }
        ]
    },

    // ---------------------------------------------------------
    // 5. APRENDIZAJE AUTÓNOMO
    // ---------------------------------------------------------
    autonomo: {
        1: [
            {
                time: "ANY",
                title: "Consumo Pasivo Estratégico",
                desc: "Escuchar 10 minutos de un podcast técnico o en inglés mientras se hace una tarea mecánica.",
                ciencia: "Teoría de la Inmersión (Krashen): La adquisición inconsciente de estructuras (idiomas/lógica) ocurre mediante la exposición constante y de baja presión."
            }
        ],
        2: [
            {
                time: "19:00",
                title: "Replicación de Modelos",
                desc: "30 minutos siguiendo un tutorial o documentación técnica sin abrir redes sociales, replicando el código exacto.",
                ciencia: "Recuerdo Activo (Active Recall): Recuperar información para escribir el código genera conexiones neuronales más fuertes que la simple lectura pasiva."
            }
        ],
        3: [
            {
                time: "20:00",
                title: "Resolución sin Asistencia",
                desc: "1 hora solucionando un problema de lógica de programación o diseño estructurado sin ver tutoriales. Midiento los errores.",
                ciencia: "Dificultad Deseable (Bjork): Cuanto más esfuerzo cognitivo requiere recuperar o estructurar información, mayor es la retención a largo plazo."
            }
        ],
        4: [
            {
                time: "ANY",
                title: "Construcción de Sistemas Completos",
                desc: "Lectura directa de documentación oficial para construir aplicaciones transversales (front-end a base de datos) desde cero.",
                ciencia: "Constructivismo (Piaget): El conocimiento no se absorbe; se construye activamente al interactuar con el entorno y superar fallos sistémicos."
            }
        ]
    },

    // ---------------------------------------------------------
    // 6. DESARROLLO CREATIVO / SOCIAL
    // ---------------------------------------------------------
    creativo: {
        1: [
            {
                time: "PM",
                title: "Interacción Deliberada",
                desc: "Enviar un mensaje a un círculo cercano o jugar 1 partida rápida (ej. MOBA/Estrategia) estrictamente limitada por tiempo.",
                ciencia: "Línea Base de Dopamina (Lembke): Limitar temporalmente los estímulos de alta recompensa (videojuegos/redes) previene el agotamiento de los receptores dopaminérgicos."
            }
        ],
        2: [
            {
                time: "FIN DE SEMANA",
                title: "Gestión de Comunidad / Hobby",
                desc: "Dedicar 45 minutos ininterrumpidos a administrar proyectos pasionales (servidores, foros) o diseño.",
                ciencia: "Teoría de la Autodeterminación (Deci & Ryan): La motivación intrínseca florece cuando se fomenta la autonomía y la competencia en entornos no obligatorios."
            }
        ],
        3: [
            {
                time: "FIN DE SEMANA",
                title: "Bloqueo Off-Screen",
                desc: "Tiempo de calidad innegociable con familiares o pareja. Teléfono en modo avión en otra habitación.",
                ciencia: "Teoría de la Línea Base Social (Coan): La proximidad física y atención plena con vínculos cercanos reduce mediblemente la carga alostática (estrés acumulado)."
            }
        ],
        4: [
            {
                time: "ANY",
                title: "Producción Creativa de Alto Estándar",
                desc: "Desarrollo de proyectos visuales o creativos (diseño de avatares 3D, retratos) integrando habilidades técnicas con pasión pura.",
                ciencia: "Aprendizaje Basado en Proyectos (PBL): La integración de disciplinas complejas bajo un objetivo estético/creativo maximiza la retención de habilidades transversales."
            }
        ]
    }
};