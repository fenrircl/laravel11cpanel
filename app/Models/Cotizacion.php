<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    // Tabla compatible con proyecto antiguo
    protected $table = 'quotations';

    protected $fillable = [
        'agent',
        'total', // almacenar CLP como entero
        'date',
        'client_id',
        'work',
    ];

    protected $casts = [
        'date' => 'date',
        'total' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(CotizacionItem::class, 'quotation_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }
}
