# Ticket Manager — Prueba Técnica
**Ian Rojas Sequeira**

Módulo de tickets en Laravel 11 con CRUD, protección IDOR, validación y telemetría.

---

## Requisitos

| Requisito | Versión |
|-----------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Node.js | 18+ |
| npm | 9+ |

---

## Instalación

Siga estos pasos **en orden** para levantar el proyecto:

### 1. Instalar dependencias PHP

```bash
composer install
```

### 2. Configurar entorno

```bash
cp .env.example .env
```

Abra el archivo `.env` y configure su base de datos MySQL:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tickets_db
DB_USERNAME=root
DB_PASSWORD=su_contraseña
```

> Asegúrese de que la base de datos `tickets_db` (o el nombre que elija) ya exista en MySQL antes de continuar.

### 3. Generar clave de aplicación, migrar y sembrar datos

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
```

El seeder crea **5,000 tickets** distribuidos entre **10 usuarios** (user_id 1 al 10).

### 4. Instalar dependencias del frontend

```bash
npm install
```

### 5. Levantar los servidores

Necesita **dos terminales** abiertas en la carpeta del proyecto:

**Terminal 1** — Servidor Laravel:
```bash
php artisan serve
```

**Terminal 2** — Servidor Vite (assets del frontend):
```bash
npm run dev
```

### 6. Abrir en el navegador

```
http://localhost:8000
```

La aplicación redirige automáticamente a la interfaz de tickets.

> Si el puerto `8000` está ocupado, Laravel le indicará el puerto alternativo en la terminal.

---

## Interfaz Web

La interfaz web permite probar todas las funcionalidades sin necesidad de curl o Postman.

| Funcionalidad | Descripción |
|---------------|-------------|
| **Simulación de usuario** | Campo para ingresar el `X-User-Id` (1–10) y simular diferentes usuarios |
| **Listar tickets** | Tabla paginada con los tickets del usuario seleccionado |
| **Crear ticket** | Formulario para crear nuevos tickets |
| **Ver detalle** | Vista con la información completa de un ticket |
| **Editar ticket** | Formulario para modificar subject, body y status |
| **Eliminar ticket** | Confirmación antes de eliminar |
| **Prueba IDOR** | Sección para verificar la protección contra acceso no autorizado |

### Cómo usar

1. Ingrese un **User ID** (1–10) en el campo de identificación
2. Presione **"Cargar Tickets"** para ver los tickets de ese usuario
3. Use los botones de acción en cada fila para ver, editar o eliminar
4. Cambie el User ID para simular otro usuario y verificar que no accede a tickets ajenos

---

## API REST (Terminal / Postman)

### Headers

| Header | Valor | Obligatorio |
|--------|-------|-------------|
| `X-User-Id` | Entero (ej: `1`) | Sí |
| `X-Correlation-Id` | UUID para rastreo | No (se genera automáticamente) |
| `Accept` | `application/json` | Recomendado |
| `Content-Type` | `application/json` | Para POST/PUT |

> **Sin `X-User-Id` todas las peticiones devuelven 401 Unauthorized.**

### Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tickets` | Listar tickets del usuario (paginado, 10/página) |
| POST | `/api/tickets` | Crear ticket |
| GET | `/api/tickets/{id}` | Ver ticket |
| PUT | `/api/tickets/{id}` | Actualizar ticket |
| DELETE | `/api/tickets/{id}` | Eliminar ticket |

### Ejemplos con curl

**Listar tickets:**
```bash
curl -X GET http://localhost:8000/api/tickets \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

**Crear ticket:**
```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-User-Id: 1" \
  -d '{"subject":"Error en el sistema","body":"No puedo acceder al módulo de reportes"}'
```

**Ver ticket:**
```bash
curl -X GET http://localhost:8000/api/tickets/5001 \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

**Actualizar ticket:**
```bash
curl -X PUT http://localhost:8000/api/tickets/5001 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-User-Id: 1" \
  -d '{"subject":"Problema resuelto","status":"closed"}'
```

**Eliminar ticket:**
```bash
curl -X DELETE http://localhost:8000/api/tickets/5001 \
  -H "Accept: application/json" \
  -H "X-User-Id: 1"
```

> Reemplace `5001` por el ID real que devuelva la API al crear un ticket.

---

## Seguridad — Protección IDOR

El sistema protege contra ataques IDOR (Insecure Direct Object Reference). Si un usuario intenta acceder a un ticket que no le pertenece, la API responde con **404 Not Found**:

```bash
# Usuario 999 intenta acceder al ticket 1 (pertenece a otro usuario)
curl -X GET http://localhost:8000/api/tickets/1 \
  -H "Accept: application/json" \
  -H "X-User-Id: 999"
```

```json
{"error": "Not Found"}
```

El intento queda registrado en los logs como `security.idor_blocked`.

---

## Validación

| Campo | Reglas |
|-------|--------|
| `subject` | Requerido, máximo 120 caracteres |
| `body` | Requerido |
| `status` | Solo `open` o `closed` (en actualización) |

Si envía datos inválidos, la API responde con **422 Unprocessable Entity**:

```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-User-Id: 1" \
  -d '{"subject":""}'
```

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

## Telemetría

### Correlation ID

1. Si envía `X-Correlation-Id`, se usa ese valor
2. Si no lo envía, se genera un UUID automáticamente
3. El valor siempre se devuelve en el header de respuesta `X-Correlation-Id`

### Logs

Todas las acciones se registran en `storage/logs/laravel.log`:

```
[2026-02-06 05:00:00] local.INFO: Telemetry: tickets.create {"correlation_id":"abc-123","user_id":1,"action":"tickets.create","ticket_id":5001}
```

```
[2026-02-06 05:00:00] local.WARNING: Telemetry: security.idor_blocked {"correlation_id":"abc-456","user_id":999,"action":"security.idor_blocked","ticket_id":1}
```

Para ver los logs en tiempo real:
```bash
tail -f storage/logs/laravel.log
```

---

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── TicketController.php        # CRUD con protección IDOR
│   ├── Middleware/
│   │   ├── CorrelationId.php           # Manejo de X-Correlation-Id
│   │   └── UserIdentity.php            # Validación de X-User-Id
│   └── Requests/
│       ├── StoreTicketRequest.php      # Validación para crear
│       └── UpdateTicketRequest.php     # Validación para actualizar
├── Models/
│   └── Ticket.php                      # Modelo con scope forUser()
└── Services/
    └── TelemetryLogger.php             # Logging de telemetría

database/
├── factories/
│   └── TicketFactory.php
├── migrations/
│   └── xxxx_create_tickets_table.php
└── seeders/
    └── TicketSeeder.php                # 5,000 tickets de prueba

resources/
├── js/
│   └── tickets.js                      # Lógica del frontend
└── views/
    ├── layouts/
    │   └── app.blade.php               # Layout base
    └── tickets/
        └── index.blade.php             # Interfaz de gestión

routes/
├── api.php                             # Rutas API con middleware
└── web.php                             # Ruta de la interfaz web
```

---
# Notas
Por convención del propio framework, coherencia con el ejercicio y buenas practicas, codigo en general, comentarios, funciones, tablas y atributos están escritos en inglés.