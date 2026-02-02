el primer comado que se uso es:

```bash
composer require tymon/jwt-auth
```

luego el siguinte comando:

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

por ultimo se ejecuta el siguinete codigpo:

```php
php artisan jwt:secret
```

estos comandos vienen de la documentacion oficial de: https://jwt-auth.readthedocs.io/en/develop/laravel-installation/

y el archivo model/user.php se deja asi:

```bash
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

luego se crea el dentro de la migration user, se coloca la tabla de role:

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
```

y se agrega en models/user.php:

```bash
protected $fillable = [
        'name',
        'role',
        'email',
        'password',
    ];
```

luego se crean dos middlewares:

```bash
php artisan make:middleware isUserAuth
```

```bash
php artisan make:middleware isAdmin
```

luego se crea un modelo como:

```bash
php artisan make:model Product -mc
```

> con eset comando se crea tanto el modelo, la migracion y el controlador.

luego se crea otro controlador:

```bash
php artisan make:controller AuthController
```

y se crea toda la logica de esde controlador:

```bash
class AuthController extends Controller
{
    //Registro
    public function register(Request $request)
    {
        $validator = validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,user', // se agrega validacion para el rol
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        User::create([
            'name' => $request->get('name'),
            'role' => $request->get('role'), // se guarda el rol
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
            'expires_in' => config('jwt.ttl') * 60  // 60 min durara el token
        ], 200);
    }

    //Obtener usuario
    public function getUser(){
        $user = Auth::user();
        return response()->json($user, 200);
    }

    //Logout
    public function logout(){
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'User logged out successfully'], 200);
    }
}
```

luego se modica el middleware de /app/http/middleware/isUserAuth.php asi.

y se aplica lo siguiente:

```bash
public function handle(Request $request, Closure $next): Response
    {
        //si un usuario esta autenticado se aceptan las peticiones si no se rechazan
        if (auth('api')->user()) {
            return $next($request);
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }
```

y en isUserAdmin se valida si el usuario es admin asi:

```bash
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

luego en la carpeta /boostrad/app.php:

se coloca:

```bash
 ->withMiddleware(function (Middleware $middleware): void {
        isUserAuth::class;
        isAdmin::class;
    })
```

y se importa:

```bash
use App\Http\Middleware\isAdmin;
use App\Http\Middleware\isUserAuth;
```

luego se crean las rutas publicas en route/api.php:

```bash
// Rutas publicas
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rutas privadas
Route::middleware([isUserAuth::class])->group(function () {
    // la rutas privadas por lo general necesitan estas logueados
    Route::controller(AuthController::class)->group(function () {
        Route::post('logout', 'logout');
        Route::get('me', 'getUser');
    });

    Route::get('products', [ProductController::class, 'index']);

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

y se corre el proyecto y se habre es una extebnsion para ver la base de datos.

tambien con postman o Flashpost se testea la api ejemplo:

post: http://127.0.0.1:8000/api/register al dar en send deberia de mostrarte que los campos son necesarios.

para crear un usuario se envia en formrato json de la siguinete manera:

```bash
{
    "name": "Forlan Ordoñez",
    "role": "admin",
    "email": "foor@gmail.com",
    "password": "123456789"
}
```

cuando se envie te pedira confimar la contraseña ya que se agrego un campo para ello, entonces la manera correcta es:

```bash
{
    "name": "Forlan Ordoñez",
    "role": "admin",
    "email": "foor@gmail.com",
    "password": "123456789",
    "password_confirmation": "123456789"
}
```

y se creara el usuario corerctamenta en la base de datos.

una ves creada se puede loguar como:

POST http://127.0.0.1:8000/api/login

```bash
{
    "email": "foor@gmail.com",
    "password": "123456789"
}
```

y te dara un token de 60min asi:

```bash
"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzcwMDY0MjgxLCJleHAiOjE3NzAwNjc4ODEsIm5iZiI6MTc3MDA2NDI4MSwianRpIjoiSEtXSWhsSTl6NTZEWkFCVSIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.JCh4pzcwdDfU9pNuU_qmgou7loP7OXsRfHRXM88hGh4"
```

ese token lo copiaras y abriras otra ventanas de test y colocaras:

GET http://127.0.0.1:8000/api/me

te dira que se necesita autheticacion, ahi es donde usas el token como, te dirijes ah Auth o Authorizacion dentro del programa test de las api, entinces selecionas auth luego bearer Toekn y pegas el token alli y le das en send, y te deberia mostrar los dtaos del usuario logueado:

```bash
{
7 items
"id":1
"name":"Forlan Ordoñez"
"role":"admin"
"email":"foor@gmail.com"
"email_verified_at":NULL
"created_at":"2026-02-02T20:11:42.000000Z"
"updated_at":"2026-02-02T20:11:42.000000Z"
}
```

para desloguarse lo mismo, se dirige a la ruta

POST http://127.0.0.1:8000/api/logout

se pega el mismo token en auth y se envia y deberia salir:

```bash
{
1 items
"message":"User logged out successfully"...
}
```
