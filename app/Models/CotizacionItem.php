<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    use HasFactory;

    protected $table = 'quotation_items';

    protected $fillable = [
      'amount',
      'description',
      'price',
      'total',
      'quotation_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'price' => 'integer',
        'total' => 'integer',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'quotation_id');
    }
}
