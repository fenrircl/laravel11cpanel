<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;
    
    protected $table = 'invoices'; // Nombre original de la tabla
    
    protected $fillable = [
        'invoice',
        'client_id',
        'provider_id', 
        'date',
        'expiry',
        'pay_date',
        'amount',
        'check',
        'payment_method_id',
        'detail',
        'status'
    ];
    
    protected $casts = [
        'date' => 'date',
        'expiry' => 'date',
        'pay_date' => 'date',
        'amount' => 'decimal:2',
        'status' => 'integer'
    ];
    
    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }
    
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'provider_id');
    }
    
    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'payment_method_id');
    }
    
    // Scopes
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }
    
    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }
    
    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->status === 1 ? 'Pagado' : 'Pendiente';
    }
    
    public function getTipoAttribute()
    {
        return $this->client_id ? 'cliente' : 'proveedor';
    }
    
    public function getEntidadNombreAttribute()
    {
        return $this->client_id ? $this->cliente?->name : $this->proveedor?->name;
    }
}
