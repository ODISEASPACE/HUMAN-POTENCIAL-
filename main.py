from fastapi import FastAPI, HTTPException, Depends
from sqlalchemy.orm import Session
import models
import schemas
from database import engine, SessionLocal

# Esto crea las tablas en la base de datos si no existen
models.Base.metadata.create_all(bind=engine)

app = FastAPI(title="Odisea MVP API")

# Dependencia para obtener la sesión de BD en cada petición
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# Función temporal para simular encriptación de contraseñas
def fake_hash_password(password: str):
    return "hashed_" + password

@app.post("/api/register", response_model=schemas.UserResponse)
def register_user(user: schemas.UserCreate, db: Session = Depends(get_db)):
    db_user = db.query(models.User).filter(models.User.email == user.email).first()
    if db_user:
        raise HTTPException(status_code=400, detail="El correo ya está registrado.")
    
    hashed_password = fake_hash_password(user.password)
    new_user = models.User(name=user.name, email=user.email, hashed_password=hashed_password)
    db.add(new_user)
    db.commit()
    db.refresh(new_user)
    return new_user

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