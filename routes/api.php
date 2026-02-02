<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;  // se importa el controlador de productos
use App\Http\Middleware\isAdmin;
use App\Http\Middleware\isUserAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


// // GET
// Route::get('/products', [ProductController::class, 'index']);

// // POST
// Route::post('/products', [ProductController::class, 'store']);

// // POST FOR ID
// Route::get('/products/{id}', [ProductController::class, 'show']);

// // PUT
// Route::put('/products/{id}', [ProductController::class, 'update']);

// // PATCH
// Route::patch('/products/{id}', [ProductController::class, 'updatePartial']);

// // DELETE
// Route::delete('/products/{id}', [ProductController::class, 'destroy']);
