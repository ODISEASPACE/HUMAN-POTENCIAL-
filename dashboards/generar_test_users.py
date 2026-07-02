from sqlalchemy import create_engine, text
from passlib.context import CryptContext

# Tu URL de base de datos
DATABASE_URL = "postgresql://Producción:Limitless20xx@aph-database.cy78m00i65y5.us-east-1.rds.amazonaws.com:5432/postgres"
engine = create_engine(DATABASE_URL)
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

# Roles y Usuarios a crear
roles = [
    {"nombre": "admin", "desc": "Administrador Global del Sistema"},
    {"nombre": "developer", "desc": "Desarrollador y tester API"},
    {"nombre": "user", "desc": "Usuario Estándar"}
]

usuarios_prueba = [
    {"email": "admin@aph.os", "nombre": "Admin Daniel", "rol_nombre": "admin", "pwd": "123"},
    {"email": "dev@aph.os", "nombre": "Developer Ops", "rol_nombre": "developer", "pwd": "123"},
    {"email": "user@aph.os", "nombre": "Test User", "rol_nombre": "user", "pwd": "123"}
]

def poblar_bd():
    with engine.connect() as conn:
        print("Conectado a AWS RDS...")
        
        # 1. Crear los Roles
        for rol in roles:
            # Verifica si existe
            res = conn.execute(text(f"SELECT id FROM roles WHERE nombre = '{rol['nombre']}'")).fetchone()
            if not res:
                conn.execute(text(f"INSERT INTO roles (nombre, descripcion) VALUES ('{rol['nombre']}', '{rol['desc']}')"))
                print(f"Rol '{rol['nombre']}' creado.")
        
        # 2. Crear los Usuarios
        for u in usuarios_prueba:
            # Buscar el ID del rol recién creado
            rol_id = conn.execute(text(f"SELECT id FROM roles WHERE nombre = '{u['rol_nombre']}'")).scalar()
            
            # Validar si el usuario ya existe
            res = conn.execute(text(f"SELECT id FROM usuarios WHERE email = '{u['email']}'")).fetchone()
            if not res:
                hashed_pwd = pwd_context.hash(u['pwd'])
                query = text("""
                    INSERT INTO usuarios (rol_id, nombre_completo, email, password_hash, activo) 
                    VALUES (:rol_id, :nombre, :email, :pwd, true)
                """)
                conn.execute(query, {
                    "rol_id": rol_id,
                    "nombre": u['nombre'],
                    "email": u['email'],
                    "pwd": hashed_pwd
                })
                print(f"Usuario {u['email']} creado exitosamente.")
            else:
                print(f"El usuario {u['email']} ya existía.")
                
        conn.commit()
        print("\n¡Base de datos lista! Ya puedes iniciar sesión con admin@aph.os, dev@aph.os o user@aph.os (La contraseña es 123 para todos).")

if __name__ == "__main__":
    poblar_bd()