import os
from fastapi import FastAPI, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker, Session
from dotenv import load_dotenv
from pydantic import BaseModel
from passlib.context import CryptContext
from fastapi import FastAPI, HTTPException, Depends, Header
import secrets
from datetime import datetime, timedelta, timezone

# 1. Carga estricta de variables de entorno
load_dotenv()
DATABASE_URL = os.getenv("DATABASE_URL")
if not DATABASE_URL:
    DATABASE_URL="postgresql://postgres:Daniel_2419@aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com:5432/postgres?sslmode=require"

# 2. Configuración del Motor de Base de Datos (SQLAlchemy)
# 2. Configuración del Motor de Base de Datos (SQLAlchemy)
engine = create_engine(DATABASE_URL, pool_pre_ping=True)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# 3. Inicialización de la aplicación FastAPI
app = FastAPI(title="APH OS Core API", version="1.0")

# =========================================================================
# CONFIGURACIÓN DE CORS (Crucial para que el navegador no bloquee)
# =========================================================================
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# =========================================================================
# SEGURIDAD Y HASHING DE CONTRASEÑAS
# =========================================================================
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

def verify_password(plain_password, hashed_password):
    return pwd_context.verify(plain_password, hashed_password)

def get_password_hash(password):
    return pwd_context.hash(password)

# =========================================================================
# MODELOS PYDANTIC
# =========================================================================
class UserLogin(BaseModel):
    email: str
    password: str

class UserRegister(BaseModel):
    nombre_completo: str
    email: str
    password: str

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# =========================================================================
# ENDPOINTS EXACTOS A LOS QUE LLAMA EL FRONTEND (/api/v1/auth/...)
# =========================================================================

@app.get("/")
def read_root():
    return {"status": "online", "system": "APH OS Core System"}

@app.post("/api/v1/auth/register")
def register_user(user: UserRegister, db: Session = Depends(get_db)):
    try:
        check_query = text("SELECT id FROM usuarios WHERE email = :email")
        existing_user = db.execute(check_query, {"email": user.email}).fetchone()
        
        if existing_user:
            raise HTTPException(status_code=400, detail="El correo electrónico ya está registrado")

        role_query = text("SELECT id FROM roles WHERE nombre = 'Usuario_Estandar' LIMIT 1")
        role = db.execute(role_query).fetchone()
        if not role:
            raise HTTPException(status_code=500, detail="El rol predeterminado 'Usuario_Estandar' no se encuentra configurado.")
        
        rol_id = role[0]
        hashed_pwd = get_password_hash(user.password)

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

        insert_profile_query = text("""
            INSERT INTO perfiles (usuario_id, biografia, zona_horaria) 
            VALUES (:usuario_id, '¡Hola! Estoy usando APH OS.', 'America/Bogota')
        """)
        db.execute(insert_profile_query, {"usuario_id": new_user_id})

        db.commit()
        return {"status": "success", "message": "Usuario e inicialización de perfil creados correctamente"}

    except HTTPException as http_ex:
        db.rollback()
        raise http_ex
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"Error en el servidor: {str(e)}")


@app.post("/api/v1/auth/login")
def login_user(user: UserLogin, db: Session = Depends(get_db)):
    query = text("""
        SELECT u.id, u.nombre_completo, u.password_hash, r.nombre as rol_nombre 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE u.email = :email AND u.activo = true
    """)
    db_user = db.execute(query, {"email": user.email}).fetchone()
    if not db_user or not verify_password(user.password, db_user.password_hash):
        raise HTTPException(status_code=401, detail="Credenciales de acceso inválidas")

    token = secrets.token_hex(32)
    expira = datetime.now(timezone.utc) + timedelta(hours=8)
    db.execute(text("""
        INSERT INTO sesiones (usuario_id, token_sesion, expira_en)
        VALUES (:usuario_id, :token, :expira)
    """), {"usuario_id": db_user.id, "token": token, "expira": expira})
    db.commit()

    return {
        "status": "success",
        "user_id": db_user.id,
        "nombre": db_user.nombre_completo,
        "rol": db_user.rol_nombre,
        "token": token
    }
@app.get("/api/v1/auth/verify")
def verify_session(authorization: str = Header(None), db: Session = Depends(get_db)):
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Falta el token de sesión")
    token = authorization.split(" ", 1)[1]

    query = text("""
        SELECT u.id, u.nombre_completo, r.nombre as rol_nombre, s.expira_en
        FROM sesiones s
        JOIN usuarios u ON s.usuario_id = u.id
        JOIN roles r ON u.rol_id = r.id
        WHERE s.token_sesion = :token
    """)
    session = db.execute(query, {"token": token}).fetchone()
    if not session or session.expira_en < datetime.now(timezone.utc):
        raise HTTPException(status_code=401, detail="Sesión inválida o expirada")

    return {"user_id": session.id, "nombre": session.nombre_completo, "rol": session.rol_nombre}
