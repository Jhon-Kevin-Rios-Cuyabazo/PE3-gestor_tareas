# Gestor de Tareas (Proyecto Fin de Curso)

API RESTful para la gestión de tareas de usuarios, implementando patrones de arquitectura escalable, acceso eficiente a datos mediante ORM y caché con Redis bajo el patrón Cache-Aside.

## Tecnologías

* **PHP 8.4** con **Laravel 11.x** (Framework Backend)
* **MySQL 8.0** (Base de Datos Relacional)
* **Redis 7** (Caché en Memoria con soporte de Tags)
* **Docker & Docker Compose** (Orquestación del entorno)
* **PHPUnit** (Pruebas Unitarias)

## Instalación

1. **Clona el repositorio:**
   ```bash
   git clone https://github.com/Jhon-Kevin-Rios-Cuyabazo/PE3-gestor_tareas.git
   cd PE3-gestor_tareas
   ```

2. **Copia las variables de entorno:**
   ```bash
   cp .env.example .env
   ```

3. **Levanta los servicios (MySQL + Redis + App):**
   ```bash
   docker compose up -d
   ```

4. **Instala dependencias e inicializa la aplicación:**
   ```bash
   # Instalar dependencias de PHP
   docker compose exec app composer install

   # Generar clave de aplicación
   docker compose exec app php artisan key:generate

   # Ejecutar migraciones y poblar la base de datos (≥50 registros)
   docker compose exec app php artisan migrate --seed
   ```

La API estará disponible en: `http://localhost:8000/api/tasks`

La demo frontend estará disponible en: `http://localhost:8000` (abre DevTools → Network para ver el flujo completo)

## Endpoints disponibles

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/tasks` | Listar tareas con filtros y paginación |
| `POST` | `/api/tasks` | Crear tarea |
| `GET` | `/api/tasks/{id}` | Ver tarea por ID |
| `PUT` | `/api/tasks/{id}` | Actualizar tarea |
| `DELETE` | `/api/tasks/{id}` | Eliminar tarea |

**Parámetros opcionales de `GET /api/tasks`:**

| Parámetro | Ejemplo | Descripción |
|---|---|---|
| `search` | `search=reunión` | Busca en título y descripción |
| `status` | `status=pending` | Filtra por estado |
| `sort_by` | `sort_by=title` | Campo de orden (`id`, `title`, `status`, `created_at`) |
| `sort_dir` | `sort_dir=asc` | Dirección del orden (`asc`, `desc`) |
| `page` | `page=2` | Página de resultados |

## Pruebas

```bash
# Ejecutar suite completa de pruebas unitarias
docker compose exec app php artisan test
```

## Benchmark de Caché

El proyecto incluye un comando Artisan para medir el speedup del patrón Cache-Aside automáticamente (10 iteraciones sin caché vs con caché):

```bash
docker compose exec app php artisan benchmark:cache
```

Salida esperada (ejemplo):
```
+------------+----------------+---------------+
| Iteración  | Sin caché (ms) | Con caché (ms)|
+------------+----------------+---------------+
| 1          | 42.3           | 1.2           |
| ...        | ...            | ...           |
+------------+----------------+---------------+
| Speedup (S = T_sin / T_con) | ~41x          |
+-----------------------------+---------------+
```
