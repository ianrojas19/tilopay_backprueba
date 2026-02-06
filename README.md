# By: Ian Rojas Sequeira
# Laravel Ticket Module - Prueba Técnica 

Módulo de tickets en Laravel 11 con CRUD, validación, control de acceso (IDOR), y telemetría.

---

## Inicio Rápido

Abra la carpeta del proyecto

```bash
cd /ruta/del/proyecto
```

Y luego ejecutar:

```bash
composer install
cp .env.example .env
```

Edite el archivo .env con su configuración de MySQL:

```
DB_CONNECTION=mysql
DB_HOST=su_host
DB_PORT=su_puerto
DB_DATABASE=su_base_de_datos
DB_USERNAME=su_usuario
DB_PASSWORD=su_contraseña
```
Y luego ejecute las migraciones y semillas (DB):

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```


Luego pruebe con:
```bash
curl -X GET http://localhost:8000/api/tickets -H "Accept: application/json" -H "X-User-Id: 1"
```

---

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8.0+ (o SQLite para pruebas rápidas)

---

## Instalación Paso a Paso

### Paso 1: Obtener el proyecto

**Opción A: Clonar desde GitHub**

```bash
git clone https://github.com/ianrojas19/tilopay_backprueba.git
cd tilopay_backprueba
```

**Opción B: Descargar como ZIP**

1. Descargue el archivo ZIP desde el correo enviado
2. Descomprima el archivo
3. Abra una terminal en la carpeta del proyecto:

```bash
cd tilopay_backprueba-main
```

### Paso 2: Instalar dependencias

```bash
composer install
```

### Paso 3: Configurar entorno

```bash
cp .env.example .env
```

### Paso 4: Configurar base de datos

Edite el archivo `.env` con su configuración de MySQL:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tickets_db
DB_USERNAME=root
DB_PASSWORD=su_contraseña
```

> **Nota:** Por defecto Laravel usa SQLite. Si prefiere SQLite, no necesita cambiar nada.

### Paso 5: Generar clave de aplicación

```bash
php artisan key:generate
```

### Paso 6: Ejecutar migraciones

```bash
php artisan migrate
```

Esto crea la tabla `tickets` con los campos:
- `id` - Identificador único
- `subject` - Asunto (máx. 120 caracteres)
- `body` - Contenido del ticket
- `status` - Estado (open/closed)
- `user_id` - ID del usuario propietario
- `created_at`, `updated_at` - Timestamps

### Paso 7: Sembrar datos de prueba

```bash
php artisan db:seed
```

Esto crea **5,000 tickets** distribuidos entre **10 usuarios** (user_id 1-10).

> **Nota:** El seeder cumple el requisito de "mínimo 2 user_id distintos y 10 tickets".

### Paso 8: Iniciar servidor

```bash
php artisan serve
```

El servidor estará disponible en: `http://localhost:8000`

> **Nota:** El puerto `8000` es el predeterminado. Si está ocupado, Laravel usará otro puerto (ej: 8001). Verifique el mensaje en la terminal al iniciar el servidor.

---

## Guía de Uso

### Headers Requeridos

 **IMPORTANTE:** Todas las peticiones a la API requieren el header `X-User-Id` para identificar al usuario. Si no se envía, recibirá un error 401.

| Header | Descripción | Obligatorio |
|--------|-------------|-------------|
| `X-User-Id` | ID del usuario (entero). **Requerido en TODAS las peticiones.** | Sí |
| `X-Correlation-Id` | UUID para rastreo. Si no lo envía, se genera automáticamente. | No |
| `Accept` | `application/json` | Recomendado |
| `Content-Type` | `application/json` | Para POST/PUT |

### Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tickets` | Listar mis tickets (paginado, 10 por página) |
| POST | `/api/tickets` | Crear un nuevo ticket |
| GET | `/api/tickets/{id}` | Ver un ticket específico |
| PUT | `/api/tickets/{id}` | Actualizar un ticket |
| DELETE | `/api/tickets/{id}` | Eliminar un ticket |

---

## Ejemplos con curl

> **Notas importantes para los ejemplos:**
> - La URL `http://localhost:8000` puede variar según el puerto que use su servidor.
> - Los IDs de tickets (ej: `5001`) son ejemplos. Use el ID real que le devuelva la API al crear un ticket.
> - El `X-User-Id: 1` es un ejemplo. Puede usar cualquier número entero para simular diferentes usuarios.
> - Los datos del seeder generan tickets con IDs y user_ids variados.
>
> Las pruebas de estos endpoints pueden realizarse usando **Postman** o cualquier cliente REST **local** (para mejor visualización de los headers y respuestas), simplemente copie y pegue los ejemplos de curl en el cliente REST y ejecute la petición. 

---

### 1. ❌ Sin X-User-Id (Error 401)

Este ejemplo muestra qué pasa si **no** envía el header `X-User-Id`:

```bash
curl -X GET http://localhost:8000/api/tickets \
  -H "Accept: application/json"
```

**Respuesta (401 Unauthorized):**
```json
{
  "error": "Unauthorized",
  "message": "X-User-Id es requerido"
}
```

---

### 2. Listar mis tickets

Lista todos los tickets del usuario autenticado (paginado, 10 por página):

```bash
curl -X GET http://localhost:8000/api/tickets \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

---

### 3. Listar con paginación

Para ver otras páginas, use el parámetro `page`:

```bash
curl -X GET "http://localhost:8000/api/tickets?page=2" \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

---

### 4. Crear un ticket

Cree un nuevo ticket con subject y body:

```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-User-Id: 1" \
  -H "X-Correlation-Id: mi-correlacion-123" \
  -d '{"subject":"Error en el sistema","body":"No puedo acceder al módulo de reportes"}'
```

**Respuesta (201 Created):**
```json
{
  "id": 5001,
  "subject": "Error en el sistema",
  "body": "No puedo acceder al módulo de reportes",
  "status": "open",
  "user_id": 1,
  "created_at": "2026-02-06T05:00:00.000000Z",
  "updated_at": "2026-02-06T05:00:00.000000Z"
}
```

---

### 5. Ver un ticket específico

Obtenga los detalles de un ticket por su ID:

```bash
curl -X GET http://localhost:8000/api/tickets/5001 \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

---

### 6. Actualizar un ticket

Puede actualizar subject, body y/o status (open/closed):

```bash
curl -X PUT http://localhost:8000/api/tickets/5001 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-User-Id: 1" \
  -d '{"subject":"Problema resuelto","body":"Ya funciona correctamente","status":"closed"}'
```

---

### 7. Eliminar un ticket

```bash
curl -X DELETE http://localhost:8000/api/tickets/5001 \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

**Respuesta:**
```json
{
  "message": "Ticket deleted successfully"
}
```

---

### 8. Prueba de seguridad IDOR (Error 404)

Si intenta acceder a un ticket de **otro usuario**, recibirá 404:

```bash
# Usuario 999 intenta acceder al ticket 1 (que pertenece a otro usuario)
curl -X GET http://localhost:8000/api/tickets/1 \
  -H "Accept: application/json" \
  -H "X-User-Id: 999"
```

**Respuesta (404 Not Found):**
```json
{
  "error": "Not Found"
}
```

> Esto protege contra ataques IDOR (Insecure Direct Object Reference).

---

### 9. Validación fallida (Error 422)

Si envía datos inválidos, recibirá errores de validación:

```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-User-Id: 1" \
  -d '{"subject":""}'
```

**Respuesta (422 Unprocessable Entity):**
```json
{
  "error": "Validation Failed",
  "messages": {
    "subject": ["The subject field is required."],
    "body": ["The body field is required."]
  }
}
```

---

## Pruebas con Postman

También puede probar la API usando Postman. Aquí está la configuración básica:

### Configuración de Headers

En cada request, vaya a la pestaña **Headers** y agregue:

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |
| `X-User-Id` | `1` (o cualquier número entero) |
| `X-Correlation-Id` | `mi-id-opcional` (opcional) |

### Ejemplos de Requests

**GET - Listar tickets:**
- Method: `GET`
- URL: `http://localhost:8000/api/tickets`
- Headers: `Accept`, `X-User-Id`

**POST - Crear ticket:**
- Method: `POST`
- URL: `http://localhost:8000/api/tickets`
- Headers: `Accept`, `Content-Type`, `X-User-Id`
- Body → raw → JSON:
```json
{
    "subject": "Mi nuevo ticket",
    "body": "Descripción del problema"
}
```

**PUT - Actualizar ticket:**
- Method: `PUT`
- URL: `http://localhost:8000/api/tickets/{id}`
- Headers: `Accept`, `Content-Type`, `X-User-Id`
- Body → raw → JSON:
```json
{
    "subject": "Título actualizado",
    "status": "closed"
}
```

**DELETE - Eliminar ticket:**
- Method: `DELETE`
- URL: `http://localhost:8000/api/tickets/{id}`
- Headers: `Accept`, `X-User-Id`

---

## Telemetría

### Correlation ID

El sistema de telemetría funciona así:

1. Si envía el header `X-Correlation-Id`, se usa ese valor
2. Si no lo envía, se genera un UUID automáticamente
3. El valor siempre se devuelve en el header de respuesta `X-Correlation-Id`

### Logs

Todas las acciones se registran en `storage/logs/laravel.log` con el siguiente formato:

**Ejemplo de log de acción normal:**
```
[2026-02-06 05:00:00] local.INFO: Telemetry: tickets.create {"correlation_id":"mi-correlacion-123","user_id":1,"action":"tickets.create","ticket_id":5001}
```

**Ejemplo de log de intento IDOR bloqueado:**
```
[2026-02-06 05:00:00] local.WARNING: Telemetry: security.idor_blocked {"correlation_id":"abc-456","user_id":999,"action":"security.idor_blocked","ticket_id":1}
```

### Ver logs en tiempo real

```bash
tail -f storage/logs/laravel.log
```

---

## Seeders

El seeder crea **5,000 tickets** distribuidos entre **10 usuarios** (user_id 1-10).

```bash
# Ejecutar solo el seeder de tickets
php artisan db:seed --class=TicketSeeder

# Resetear base de datos y volver a sembrar
php artisan migrate:fresh --seed
```

---

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── TicketController.php    # CRUD con protección IDOR y telemetría
│   ├── Middleware/
│   │   ├── CorrelationId.php       # Manejo de X-Correlation-Id
│   │   └── UserIdentity.php        # Validación de X-User-Id
│   └── Requests/
│       ├── StoreTicketRequest.php  # Validación para crear ticket
│       └── UpdateTicketRequest.php # Validación para actualizar ticket
├── Models/
│   └── Ticket.php                  # Modelo con scope forUser()
└── Services/
    └── TelemetryLogger.php         # Servicio de logging estructurado

database/
├── factories/
│   └── TicketFactory.php           # Factory para tests
├── migrations/
│   └── xxxx_create_tickets_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── TicketSeeder.php            # Genera 5,000 tickets de prueba

routes/
└── api.php                         # Rutas API con middleware
```

---

## Reglas de Validación

| Campo | Reglas |
|-------|--------|
| `subject` | Requerido, máximo 120 caracteres |
| `body` | Requerido |
| `status` | Solo `open` o `closed` (en actualización) |


## Notas
Por convención del propio framework, coherencia con el ejercicio y buenas practicas, codigo en general, comentarios, funciones, tablas y atributos están escritos en inglés.

