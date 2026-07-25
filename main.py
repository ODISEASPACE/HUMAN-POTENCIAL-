from sqlalchemy import text
from sqlalchemy.exc import OperationalError
import bcrypt
from fastapi import FastAPI, HTTPException, Depends
from sqlalchemy.orm import Session
from fastapi.middleware.cors import CORSMiddleware
import models
import schemas
from database import engine, SessionLocal

# Esto crea las tablas en la base de datos si no existen
models.Base.metadata.create_all(bind=engine)

app = FastAPI(title="Odisea MVP API")

# Configuramos al guardia de seguridad (CORS) correctamente
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],      
    allow_credentials=False,  
    allow_methods=["*"],      
    allow_headers=["*"],
)

# Dependencia para obtener la sesión de BD en cada petición
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# ==========================================
# MOTOR DE CRIPTOGRAFÍA (BCRYPT PURO)
# ==========================================

def get_password_hash(password: str) -> str:
    # Convertimos la contraseña a bytes, generamos la sal matemática y la encriptamos
    pwd_bytes = password.encode('utf-8')
    salt = bcrypt.gensalt()
    hashed_password = bcrypt.hashpw(pwd_bytes, salt)
    return hashed_password.decode('utf-8') # Lo devolvemos a texto para guardarlo en la base de datos

def verify_password(plain_password: str, hashed_password: str) -> bool:
    # Comparamos los bytes de lo que escribió el usuario con los bytes de la base de datos
    password_byte_enc = plain_password.encode('utf-8')
    hashed_password_byte_enc = hashed_password.encode('utf-8')
    return bcrypt.checkpw(password_byte_enc, hashed_password_byte_enc)
# ==========================================
# RUTAS (ENDPOINTS)
# ==========================================

# 1. REGISTRO
@app.post("/api/register", response_model=schemas.UserResponse)
def register_user(user: schemas.UserCreate, db: Session = Depends(get_db)):
    db_user = db.query(models.User).filter(models.User.email == user.email).first()
    if db_user:
        raise HTTPException(status_code=400, detail="El correo ya está registrado.")
    
    # Encriptamos la contraseña con Bcrypt antes de guardarla
    hashed_pwd = get_password_hash(user.password)
    
    new_user = models.User(name=user.name, email=user.email, hashed_password=hashed_pwd)
    db.add(new_user)
    db.commit()
    db.refresh(new_user)
    return new_user

# ==========================================
# HEALTH CHECK (DIAGNÓSTICO DEL SISTEMA)
# ==========================================
@app.get("/api/status")
def get_status():
    db_status = "Desconectada"
    try:
        with engine.connect() as connection:
            connection.execute(text("SELECT 1"))
            db_status = "Conectada y Sincronizada"
    except OperationalError:
        db_status = "Fallo de conexión a la Base de Datos (Revisa el Túnel SSH o AWS)"

    return {
        "sistema": "Odisea MVP",
        "estado_servidor": "Óptimo",
        "estado_base_de_datos": db_status
    }

# 2. LOGIN
@app.post("/api/login")
def login_user(user: schemas.UserLogin, db: Session = Depends(get_db)):
    # 1. Buscamos si el correo existe
    db_user = db.query(models.User).filter(models.User.email == user.email).first()
    if not db_user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado. ¿Seguro que ya te registraste?")
    
    # 2. Verificamos la contraseña usando la matemática de Bcrypt
    if not verify_password(user.password, db_user.hashed_password):
        raise HTTPException(status_code=401, detail="Contraseña incorrecta.")
    
    # 3. Verificamos si este usuario ya tiene un test guardado en la base de datos
    test_exists = db.query(models.TestResult).filter(models.TestResult.user_id == db_user.id).first()
    has_completed_test = True if test_exists else False
    
    return {
        "message": "Login exitoso", 
        "user_id": db_user.id, 
        "name": db_user.name,
        "has_completed_test": has_completed_test  # Le enviamos esta bandera al Frontend
    }

# 3. TEST VOC
@app.post("/api/test-voc/{user_id}")
def save_test_result(user_id: int, result: schemas.TestResultCreate, db: Session = Depends(get_db)):
    db_user = db.query(models.User).filter(models.User.id == user_id).first()
    if not db_user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")
        
    new_result = models.TestResult(
        user_id=user_id,
        score_x_discipline=result.score_x_discipline,
        score_y_purpose=result.score_y_purpose,
        archetype=result.archetype
    )
    db.add(new_result)
    db.commit()
    return {"message": "Resultados guardados", "arquetipo": result.archetype}

# 4. OBJETIVOS TRACKTIME
@app.post("/api/tracktime-goals/{user_id}")
def save_goals(user_id: int, goals: schemas.TrackTimeGoalCreate, db: Session = Depends(get_db)):
    new_goals = models.TrackTimeGoal(
        user_id=user_id,
        primary_goal=goals.primary_goal,
        secondary_goal_1=goals.secondary_goal_1,
        secondary_goal_2=goals.secondary_goal_2,
        custom_goal_text=goals.custom_goal_text
    )
    db.add(new_goals)
    db.commit()
    return {"message": "Objetivos TrackTime guardados"}

# 5. FEEDBACK ALPHA
@app.post("/api/alpha-feedback/{user_id}")
def save_feedback(user_id: int, feedback: schemas.AlphaFeedbackCreate, db: Session = Depends(get_db)):
    new_feedback = models.AlphaFeedback(
        user_id=user_id,
        identity_validation=feedback.identity_validation,
        friction_detection=feedback.friction_detection,
        cognitive_clarity=feedback.cognitive_clarity,
        tracktime_expectation=feedback.tracktime_expectation
    )
    db.add(new_feedback)
    db.commit()
    return {"message": "Feedback Alpha recibido. ¡Gracias por participar!"}