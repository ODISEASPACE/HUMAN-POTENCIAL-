import os
from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base
from sqlalchemy.orm import sessionmaker

# EL TRUCO MAESTRO DEL ARQUITECTO
# Si el sistema operativo es Windows ('nt'), asume que estás en tu PC local usando el túnel SSH.
# Si es Linux ('posix'), asume que estás en el servidor EC2 de AWS.

if os.name == 'nt':
    # Entorno de Desarrollo (Local)
    SQLALCHEMY_DATABASE_URL = "postgresql://postgres:Limitless20xx@localhost:5432/postgres"
else:
    # Entorno de Producción (AWS)
    SQLALCHEMY_DATABASE_URL = "postgresql://postgres:Limitless20xx@aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com:5432/postgres"

engine = create_engine(SQLALCHEMY_DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()