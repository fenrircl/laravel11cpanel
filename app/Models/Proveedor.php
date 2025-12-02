<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    
    protected $table = 'providers'; // Nombre original de la tabla
    
    protected $fillable = [
        'name',
        'email', 
        'phone',
        'address',
        'rut',
        'box'
    ];
}
