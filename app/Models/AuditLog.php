<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'action', 'module', 'entity_id', 'description',
        'ip_address', 'user_agent', 'url', 'method',
        'model', 'data_before', 'data_after', 'changes', 'reversible'
    ];

    protected $casts = [
        'reversible' => 'boolean',
        'data_before' => 'array',
        'data_after' => 'array',
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
