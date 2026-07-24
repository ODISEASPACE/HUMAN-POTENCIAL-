from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base
from sqlalchemy.orm import sessionmaker

# Cambia los datos de conexión según tu configuración de PostgreSQL
SQLALCHEMY_DATABASE_URL = "postgresql://postgres:Limitless20xx@localhost:5432/postgres"

engine = create_engine(SQLALCHEMY_DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()