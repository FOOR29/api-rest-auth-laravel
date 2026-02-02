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

    //Login
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
