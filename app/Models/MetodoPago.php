<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;
    
    protected $table = 'payment_methods';
    
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    // Relaciones
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'payment_method_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
