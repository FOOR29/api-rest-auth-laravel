<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // se crea este fillabe para ver que campos pueden ser alterdos
    protected $fillable = [
        'name',
        'description',
        'price',
    ];
}
