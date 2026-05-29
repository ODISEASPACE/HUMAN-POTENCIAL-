from fastapi import FastAPI, Depends
from fastapi.responses import RedirectResponse
from sqlalchemy import create_engine, text, Column, Integer, String
from sqlalchemy.orm import declarative_base, sessionmaker, Session
from sqlalchemy.exc import OperationalError

# 1. Configuración de la Base de Datos
DATABASE_URL = "postgresql://postgres:Daniel_2419@aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com:5432/postgres"

engine = create_engine(DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

# 2. Definición de las Tablas (Modelos de Datos)
class Modulo(Base):
    __tablename__ = "modulos"
    
    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String, index=True)
    estado = Column(String)
    tipo = Column(String)

# ¡Magia! Esta línea crea las tablas en AWS RDS automáticamente si no existen
Base.metadata.create_all(bind=engine)

# 3. Inicialización de la API
app = FastAPI(
    title="Core API - Implementation of Anthropotechnology",
    description="Motor lógico central para la expansión del potencial humano y evolución cognitiva.",
    version="1.3.0",
    docs_url="/api/docs",
    openapi_url="/api/openapi.json"
)

# Dependencia para abrir y cerrar la conexión a la base de datos en cada petición
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# 4. Endpoints
@app.get("/api")
@app.get("/api/")
def root():
    return RedirectResponse(url="/api/docs")

@app.get("/api/status")
def get_status():
    db_status = "Desconectada"
    try:
        with engine.connect() as connection:
            connection.execute(text("SELECT 1"))
            db_status = "Conectada y Sincronizada"
    except OperationalError:
        db_status = "Fallo de conexión a RDS"

    return {
        "sistema": "Anthropotechnology (APH)",
        "estado_servidor": "Óptimo",
        "estado_memoria": db_status,
        "fase_actual": "Migración a Base de Datos Relacional",
        "mensaje": "Tablas de memoria persistente generadas exitosamente."
    }

@app.get("/api/modulos")
def get_modulos(db: Session = Depends(get_db)):
    # Buscamos todos los módulos en la tabla de la base de datos real
    modulos = db.query(Modulo).all()
    
    # Si la tabla está vacía (primer arranque), inyectamos los datos fundacionales
    if not modulos:
        modulos_iniciales = [
            Modulo(nombre="Sistemas de Vida", estado="Activo", tipo="Algorítmico"),
            Modulo(nombre="Neuroplasticidad Activa", estado="En calibración", tipo="Cognitivo"),
            Modulo(nombre="Meta-Aprendizaje", estado="En desarrollo", tipo="Sistémico")
        ]
        db.add_all(modulos_iniciales)
        db.commit() # Guardamos los cambios en PostgreSQL
        modulos = db.query(Modulo).all() # Volvemos a leer

    return modulos