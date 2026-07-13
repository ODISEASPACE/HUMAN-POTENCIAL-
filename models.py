from sqlalchemy import Column, Integer, String, Boolean, ForeignKey, DateTime, Text
from sqlalchemy.orm import relationship
from datetime import datetime
from database import Base

class User(Base):
    __tablename__ = "users"
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False)
    email = Column(String(255), unique=True, index=True, nullable=False)
    hashed_password = Column(String(255), nullable=False)
    is_verified = Column(Boolean, default=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    # Relaciones 1:1
    test_result = relationship("TestResult", back_populates="owner", uselist=False)
    goals = relationship("TrackTimeGoal", back_populates="owner", uselist=False)
    feedback = relationship("AlphaFeedback", back_populates="owner", uselist=False)

class TestResult(Base):
    __tablename__ = "test_results"
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id", ondelete="CASCADE"), unique=True, nullable=False)
    # Guarda los puntajes exactos del Eje X y Y (máximo 30)
    score_x_discipline = Column(Integer, nullable=False)
    score_y_purpose = Column(Integer, nullable=False)
    archetype = Column(String(50), nullable=False) 
    created_at = Column(DateTime, default=datetime.utcnow)
    
    owner = relationship("User", back_populates="test_result")

class TrackTimeGoal(Base):
    __tablename__ = "tracktime_goals"
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id", ondelete="CASCADE"), unique=True, nullable=False)
    primary_goal = Column(String(100), nullable=False)
    secondary_goal_1 = Column(String(100), nullable=True)
    secondary_goal_2 = Column(String(100), nullable=True)
    custom_goal_text = Column(String(30), nullable=True) # Campo extra de máximo 30 caracteres
    created_at = Column(DateTime, default=datetime.utcnow)
    
    owner = relationship("User", back_populates="goals")

class AlphaFeedback(Base):
    __tablename__ = "alpha_feedback"
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id", ondelete="CASCADE"), unique=True, nullable=False)
    identity_validation = Column(Text, nullable=False)
    friction_detection = Column(Text, nullable=False)
    cognitive_clarity = Column(Text, nullable=False)
    tracktime_expectation = Column(Text, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)
    
    owner = relationship("User", back_populates="feedback")