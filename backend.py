import os
from fastapi import FastAPI, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy import create_engine, text
import sqlalchemy.orm
from dotenv import load_dotenv
from pydantic import BaseModel
from passlib.context import CryptContext

# 1. Carga estricta de variables de entorno (Seguridad)
load_dotenv()
DATABASE_URL = os.getenv("DATABASE_URL")

if not DATABASE_URL:
    raise ValueError("ERROR CRÍTICO: La variable DATABASE_URL no está definida en el archivo .env")

# 2. Configuración del Motor de Base de Datos (SQLAlchemy)
engine = create_engine(DATABASE_URL, pool_pre_ping=True)
SessionLocal = sqlalchemy.orm.sessionmaker(autocommit=False, autoflush=False, bind=engine)

# 3. Inicialización de la aplicación FastAPI
app = FastAPI(title="APH OS Core API", version="1.0")

# =========================================================================
# CONFIGURACIÓN DE CORS (Vital para enlazar el Frontend PHP con Python)
# =========================================================================
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Permite peticiones desde cualquier origen local/remoto
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# =========================================================================
# SEGURIDAD Y HASHING DE CONTRASEÑAS (Manejo de password_hash)
# =========================================================================
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

def verify_password(plain_password, hashed_password):
    return pwd_context.verify(plain_password, hashed_password)

def get_password_hash(password):
    return pwd_context.hash(password)

# =========================================================================
# MODELOS PYDANTIC (Estructura de entrada para JSON del Frontend)
# =========================================================================
class UserLogin(BaseModel):
    email: str
    password: str

class UserRegister(BaseModel):
    nombre_completo: str
    email: str
    password: str

# 4. Dependencia de Inyección para manejar el ciclo de vida de la BD
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# 5. Endpoint de prueba de conexión
@app.get("/")
def read_root():
    return {"status": "online", "system": "APH OS Core System"}

@app.get("/api/v1/health/db")
def check_db_connection(db: sqlalchemy.orm.Session = Depends(get_db)):
    try:
        result = db.execute(text("SELECT 1")).scalar()
        if result == 1:
            return {"status": "success", "message": "Conexión a AWS RDS PostgreSQL establecida correctamente."}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Fallo de conexión a la base de datos: {str(e)}") 

# =========================================================================
# ENDPOINTS DE AUTENTICACIÓN (LOGIN & REGISTRO)
# =========================================================================

@app.post("/api/v1/auth/register")
def register_user(user: UserRegister, db: sqlalchemy.orm.Session = Depends(get_db)):
    try:
        # 1. Validar si el email ya existe en la base de datos
        check_query = text("SELECT id FROM usuarios WHERE email = :email")
        existing_user = db.execute(check_query, {"email": user.email}).fetchone()
        
        if existing_user:
            raise HTTPException(status_code=400, detail="El correo electrónico ya está registrado")

        # 2. Obtener el ID del rol predeterminado ('Usuario_Estandar')
        role_query = text("SELECT id FROM roles WHERE nombre = 'Usuario_Estandar' LIMIT 1")
        role = db.execute(role_query).fetchone()
        if not role:
            raise HTTPException(status_code=500, detail="El rol predeterminado 'Usuario_Estandar' no se encuentra configurado.")
        
        rol_id = role[0]
        hashed_pwd = get_password_hash(user.password)

        # 3. Registrar el usuario en la tabla 'usuarios'
        insert_user_query = text("""
            INSERT INTO usuarios (rol_id, nombre_completo, email, password_hash, activo) 
            VALUES (:rol_id, :nombre, :email, :pwd, true)
            RETURNING id
        """)
        new_user_id = db.execute(insert_user_query, {
            "rol_id": rol_id,
            "nombre": user.nombre_completo,
            "email": user.email,
            "pwd": hashed_pwd
        }).scalar()

        # 4. Crear automáticamente su registro de extensión en la tabla 'perfiles'
        insert_profile_query = text("""
            INSERT INTO perfiles (usuario_id, biografia, zona_horaria) 
            VALUES (:usuario_id, '¡Hola! Estoy usando APH OS.', 'America/Bogota')
        """)
        db.execute(insert_profile_query, {"usuario_id": new_user_id})

        # Registrar log en la base de datos
        log_query = text("""
            INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, detalles) 
            VALUES (:usuario_id, 'REGISTRO_EXITOSO', 'usuarios', '{"mensaje": "Usuario creado desde prod_space"}')
        """)
        db.execute(log_query, {"usuario_id": new_user_id})

        db.commit()
        return {"status": "success", "message": "Usuario e inicialización de perfil creados correctamente"}

    except HTTPException as http_ex:
        db.rollback()
        raise http_ex
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"Error en el servidor: {str(e)}")

@app.post("/api/v1/auth/login")
def login_user(user: UserLogin, db: sqlalchemy.orm.Session = Depends(get_db)):
    # Buscar el usuario vinculando su ID de rol con el nombre real en la tabla roles
    query = text("""
        SELECT u.id, u.nombre_completo, u.password_hash, r.nombre as rol_nombre 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE u.email = :email AND u.activo = true
    """)
    db_user = db.execute(query, {"email": user.email}).fetchone()

    # Si no se encuentra el usuario o la contraseña es errónea
    if not db_user or not verify_password(user.password, db_user.password_hash):
        # Registrar intento fallido si el usuario existía de forma parcial
        if db_user:
            log_fail = text("INSERT INTO logs_sistema (usuario_id, accion, detalles) VALUES (:uid, 'FALLO_AUTENTICACION', '{\"razon\": \"Contraseña inválida\"}')")
            db.execute(log_fail, {"uid": db_user.id})
            db.commit()
        raise HTTPException(status_code=401, detail="Credenciales de acceso inválidas")

    # Registrar log de ingreso correcto
    log_success = text("INSERT INTO logs_sistema (usuario_id, accion, detalles) VALUES (:uid, 'LOGIN_EXITOSO', '{\"origen\": \"Web Frontend\"}')")
    db.execute(log_success, {"uid": db_user.id})
    db.commit()

    return {
        "status": "success",
        "user_id": db_user.id,
        "nombre": db_user.nombre_completo,
        "rol": db_user.rol_nombre  # Retorna: 'Administrador', 'Desarrollador' o 'Usuario_Estandar'
    }