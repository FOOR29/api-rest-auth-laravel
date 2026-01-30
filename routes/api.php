<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController; // se importa el controlador de productos

//GET
Route::get('/products', [ProductController::class, 'index']);

//POST
Route::post('/products', [ProductController::class, 'store']);

//POST FOR ID
Route::get('/products/{id}', [ProductController::class, 'show']);

//PUT
Route::put('/products/{id}', [ProductController::class, 'update']);

//PATCH
Route::patch('/products/{id}', [ProductController::class, 'updatePartial']);

//DELETE
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
