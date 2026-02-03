# API REST con Laravel 12 - CRUD y Autenticación por Roles

Documentación completa de la implementación de una API RESTful en Laravel 12 con sistema de autenticación JWT y control de acceso basado en roles (admin/user).

---

## 📋 Tabla de Contenidos

- [Instalación de JWT Auth](#instalación-de-jwt-auth)
- [Configuración del Modelo User](#configuración-del-modelo-user)
- [Migración de Base de Datos](#migración-de-base-de-datos)
- [Creación de Middlewares](#creación-de-middlewares)
- [Creación de Modelos y Controladores](#creación-de-modelos-y-controladores)
- [Controlador de Autenticación](#controlador-de-autenticación)
- [Configuración de Middlewares](#configuración-de-middlewares)
- [Definición de Rutas](#definición-de-rutas)
- [Pruebas con Postman/FlashPost](#pruebas-con-postmanflashpost)

---

## 🔧 Instalación de JWT Auth

### Paso 1: Instalar el paquete JWT Auth

Ejecuta el siguiente comando para instalar la dependencia de autenticación JWT:

```bash
composer require tymon/jwt-auth
```

### Paso 2: Publicar la configuración

Publica el archivo de configuración del proveedor de servicios:

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### Paso 3: Generar la clave secreta JWT

Genera la clave secreta que se utilizará para firmar los tokens:

```bash
php artisan jwt:secret
```

> **Nota:** Estos comandos provienen de la [documentación oficial de JWT Auth](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)

---

## 👤 Configuración del Modelo User

Modifica el archivo `app/Models/User.php` para implementar la interfaz `JWTSubject`:

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

---

## 🗄️ Migración de Base de Datos

### Agregar el campo `role` a la tabla users

En la migración de usuarios (`database/migrations/xxxx_create_users_table.php`), agrega el campo `role`:

```php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('role', 20);
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```

### Actualizar el modelo User

Agrega el campo `role` al array `$fillable` en `app/Models/User.php`:

```php
protected $fillable = [
    'name',
    'role',
    'email',
    'password',
];
```

---

## 🛡️ Creación de Middlewares

### Middleware para usuarios autenticados

Crea un middleware para verificar que el usuario esté autenticado:

```bash
php artisan make:middleware isUserAuth
```

### Middleware para administradores

Crea un middleware para verificar que el usuario sea administrador:

```bash
php artisan make:middleware isAdmin
```

---

## 📦 Creación de Modelos y Controladores

### Crear el modelo Product con migración y controlador

Ejecuta el siguiente comando para crear el modelo, migración y controlador en un solo paso:

```bash
php artisan make:model Product -mc
```

> **Nota:** Con este comando se crean automáticamente el modelo, la migración y el controlador.

### Crear el controlador de autenticación

```bash
php artisan make:controller AuthController
```

---

## 🔐 Controlador de Autenticación

Implementa toda la lógica de autenticación en `app/Http/Controllers/AuthController.php`:

```php
class AuthController extends Controller
{
    // Registro
    public function register(Request $request)
    {
        $validator = validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,user', // Validación para el rol
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        User::create([
            'name' => $request->get('name'),
            'role' => $request->get('role'), // Se guarda el rol
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    // Login
    function login(Request $request)
    {
        $validator = validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid Credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60  // El token durará 60 minutos
        ], 200);
    }

    // Obtener usuario autenticado
    public function getUser(){
        $user = Auth::user();
        return response()->json($user, 200);
    }

    // Logout
    public function logout(){
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'User logged out successfully'], 200);
    }
}
```

---

## ⚙️ Configuración de Middlewares

### Middleware isUserAuth

Modifica el archivo `app/Http/Middleware/isUserAuth.php`:

```php
public function handle(Request $request, Closure $next): Response
{
    // Si un usuario está autenticado se aceptan las peticiones, si no se rechazan
    if (auth('api')->user()) {
        return $next($request);
    } else {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
```

### Middleware isAdmin

Modifica el archivo `app/Http/Middleware/isAdmin.php` para validar si el usuario es administrador:

```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth('api')->user();

    if ($user && $user->role === 'admin') {
        return $next($request);
    } else {
        return response()->json(['message' => 'You are not an admin'], 403);
    }
}
```

### Registrar middlewares en bootstrap/app.php

En el archivo `bootstrap/app.php`, registra los middlewares:

```php
->withMiddleware(function (Middleware $middleware): void {
    isUserAuth::class;
    isAdmin::class;
})
```

Y agrega las importaciones necesarias:

```php
use App\Http\Middleware\isAdmin;
use App\Http\Middleware\isUserAuth;
```

---

## 🛣️ Definición de Rutas

Configura las rutas públicas y privadas en `routes/api.php`:

```php
// Rutas públicas
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rutas privadas
Route::middleware([isUserAuth::class])->group(function () {
    // Las rutas privadas requieren que el usuario esté autenticado
    Route::controller(AuthController::class)->group(function () {
        Route::post('logout', 'logout');
        Route::get('me', 'getUser');
    });

    Route::get('products', [ProductController::class, 'index']);

    // Rutas exclusivas para administradores
    Route::middleware([isAdmin::class])->group(function () {
        Route::controller(ProductController::class)->group(function () {
            Route::get('products', 'index');
            Route::post('products', 'store');
            Route::get('/products/{id}', 'show');
            Route::put('/products/{id}', 'update');
            Route::patch('/products/{id}', 'updatePartial');
            Route::delete('/products/{id}', 'destroy');
        });
    });
});
```

---

## 🧪 Pruebas con Postman/FlashPost

### Configuración inicial

1. Ejecuta el proyecto Laravel
2. Abre tu cliente de base de datos preferido para visualizar los registros
3. Utiliza Postman o FlashPost para probar la API

---

### 1️⃣ Registro de Usuario

**Endpoint:** `POST http://127.0.0.1:8000/api/register`

#### Primera prueba (validación de campos)

Al enviar la petición sin datos, deberías recibir un error indicando que los campos son obligatorios.

#### Intento de registro sin confirmación de contraseña

```json
{
    "name": "Forlan Ordoñez",
    "role": "admin",
    "email": "foor@gmail.com",
    "password": "123456789"
}
```

Este envío solicitará la confirmación de contraseña.

#### Registro correcto

```json
{
    "name": "Forlan Ordoñez",
    "role": "admin",
    "email": "foor@gmail.com",
    "password": "123456789",
    "password_confirmation": "123456789"
}
```

**Respuesta esperada:**

```json
{
    "message": "User registered successfully"
}
```

El usuario se creará correctamente en la base de datos.

---

### 2️⃣ Inicio de Sesión (Login)

**Endpoint:** `POST http://127.0.0.1:8000/api/login`

**Body (JSON):**

```json
{
    "email": "foor@gmail.com",
    "password": "123456789"
}
```

**Respuesta esperada:**

```json
{
    "message": "Login successful",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzcwMDY0MjgxLCJleHAiOjE3NzAwNjc4ODEsIm5iZiI6MTc3MDA2NDI4MSwianRpIjoiSEtXSWhsSTl6NTZEWkFCVSIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.JCh4pzcwdDfU9pNuU_qmgou7loP7OXsRfHRXM88hGh4",
    "token_type": "bearer",
    "expires_in": 3600
}
```

> **Importante:** Copia el token generado, ya que lo necesitarás para las siguientes peticiones autenticadas. El token tiene una duración de 60 minutos.

---

### 3️⃣ Obtener Datos del Usuario Autenticado

**Endpoint:** `GET http://127.0.0.1:8000/api/me`

#### Configuración de autenticación:

1. Abre una nueva pestaña en Postman/FlashPost
2. Ve a la sección **Auth** o **Authorization**
3. Selecciona el tipo **Bearer Token**
4. Pega el token obtenido en el login
5. Envía la petición

**Respuesta esperada:**

```json
{
    "id": 1,
    "name": "Forlan Ordoñez",
    "role": "admin",
    "email": "foor@gmail.com",
    "email_verified_at": null,
    "created_at": "2026-02-02T20:11:42.000000Z",
    "updated_at": "2026-02-02T20:11:42.000000Z"
}
```

---

### 4️⃣ Cerrar Sesión (Logout)

**Endpoint:** `POST http://127.0.0.1:8000/api/logout`

#### Configuración de autenticación:

1. Ve a la sección **Auth** o **Authorization**
2. Selecciona el tipo **Bearer Token**
3. Pega el mismo token utilizado anteriormente
4. Envía la petición

**Respuesta esperada:**

```json
{
    "message": "User logged out successfully"
}
```

Después del logout, el token será invalidado y no podrá utilizarse nuevamente.

---

## 📝 Notas Adicionales

- Todos los endpoints privados requieren el token de autenticación en el header `Authorization: Bearer {token}`
- Los usuarios con rol `admin` tienen acceso completo al CRUD de productos
- Los usuarios con rol `user` solo pueden listar productos
- La duración del token es de 60 minutos (configurable en `config/jwt.php`)

---

## 🔗 Referencias

- [Documentación oficial de JWT Auth](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)
- [Laravel 12 Documentation](https://laravel.com/docs)

---

**Desarrollado con ❤️ usando Laravel 12**
