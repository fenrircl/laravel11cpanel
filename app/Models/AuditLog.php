<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'action', 'module', 'entity_id', 'description',
        'ip_address', 'user_agent', 'url', 'method'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
