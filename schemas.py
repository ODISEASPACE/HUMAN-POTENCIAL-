from pydantic import BaseModel, EmailStr, Field
from typing import Optional

# 1. Validación de Usuarios
class UserCreate(BaseModel):
    name: str = Field(..., min_length=2)
    email: EmailStr
    password: str = Field(..., min_length=8)

class UserResponse(BaseModel):
    id: int
    name: str
    email: str
    is_verified: bool

    model_config = {"from_attributes": True}
    
# 2. Validación de Resultados del Test (Máx 30 puntos por eje)
class TestResultCreate(BaseModel):
    score_x_discipline: int = Field(..., ge=0, le=30)
    score_y_purpose: int = Field(..., ge=0, le=30)
    archetype: str

# 3. Validación de Objetivos TrackTime
class TrackTimeGoalCreate(BaseModel):
    primary_goal: str
    secondary_goal_1: Optional[str] = None
    secondary_goal_2: Optional[str] = None
    custom_goal_text: Optional[str] = Field(None, max_length=30) # Límite de 30 caracteres

# 4. Validación de Feedback Alpha
class AlphaFeedbackCreate(BaseModel):
    identity_validation: str
    friction_detection: str
    cognitive_clarity: str
    tracktime_expectation: str

    # --- AÑADE ESTO AL FINAL DE TUS ESQUEMAS ---
class UserLogin(BaseModel):
    email: str
    password: str