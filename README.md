# Gestor de Tareas (Proyecto Fin de Curso)

API RESTful desarrollada para la gestión eficiente de tareas de usuarios, implementando patrones de arquitectura escalables, manejo de estado externalizado y estrategias de almacenamiento en caché para optimizar el rendimiento y reducir la carga de la base de datos.

## Tecnologías

* **PHP 8.4**
* **Laravel 11.x** (Framework Backend)
* **MySQL 8.0** (Base de Datos Relacional)
* **Redis 7** (Caché en Memoria y Sesiones)
* **Docker & Docker Compose** (Contenerización del entorno)
* **PHPUnit** (Pruebas Unitarias)

## Instalación

1. **Clona el repositorio:**
   ```bash
   git clone https://github.com/usuario/gestor-tareas-pfc.git
   cd gestor-tareas-pfc
   ```

2. **Copia las variables de entorno:**
   Copia el archivo de ejemplo para generar tus propias configuraciones locales.
   ```bash
   cp .env.example .env
   ```

3. **Levanta los contenedores (Docker):**
   Inicia los servicios de base de datos (MySQL) y caché (Redis) en segundo plano.
   ```bash
   docker compose up -d
   ```

4. **Instala dependencias y prepara la base de datos:**
   Ejecuta los siguientes comandos utilizando el contenedor de PHP provisto por Docker para no depender de instalaciones locales:
   ```bash
   # Instalar dependencias de Composer
   docker run --rm -v ${PWD}:/app -w /app php:8.4-cli composer install

   # Generar la llave de la aplicación
   docker run --rm -v ${PWD}:/app -w /app php:8.4-cli php artisan key:generate

   # Ejecutar migraciones y poblar la base de datos con datos de prueba
   docker run --rm -v ${PWD}:/app -w /app php:8.4-cli php artisan migrate --seed
   ```

5. **Ejecuta el servidor de desarrollo:**
   ```bash
   docker run --rm -v ${PWD}:/app -w /app -p 8000:8000 php:8.4-cli php artisan serve --host=0.0.0.0
   ```

La API estará disponible en: `http://localhost:8000/api/tasks`

## Pruebas

Para ejecutar la suite de pruebas unitarias (PHPUnit) y verificar la cobertura del repositorio:
```bash
docker run --rm -v ${PWD}:/app -w /app php:8.4-cli php artisan test
```
