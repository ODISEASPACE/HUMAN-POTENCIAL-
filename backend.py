import os
from fastapi import FastAPI, HTTPException, Depends
from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker, Session
from dotenv import load_dotenv

# 1. Carga estricta de variables de entorno (Seguridad)
load_dotenv()
DATABASE_URL = os.getenv("DATABASE_URL")

if not DATABASE_URL:
    raise ValueError("ERROR CRÍTICO: La variable DATABASE_URL no está definida en el archivo .env")

# 2. Configuración del Motor de Base de Datos (SQLAlchemy)
# pool_pre_ping verifica que la conexión esté viva antes de usarla, vital en AWS RDS para evitar desconexiones fantasma.
engine = create_engine(DATABASE_URL, pool_pre_ping=True)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# 3. Inicialización de la aplicación FastAPI
app = FastAPI(title="APH OS Core API", version="1.0")

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
def check_db_connection(db: Session = Depends(get_db)):
    """
    Ruta para verificar que Nginx, FastAPI y PostgreSQL se están comunicando correctamente.
    """
    try:
        # Ejecuta una consulta simple para comprobar latencia y conexión
        result = db.execute(text("SELECT 1")).scalar()
        if result == 1:
            return {"status": "success", "message": "Conexión a AWS RDS PostgreSQL establecida correctamente."}
    except Exception as e:
        # Registra el error internamente y devuelve un 500 al cliente
        raise HTTPException(status_code=500, detail=f"Fallo de conexión a la base de datos: {str(e)}") 