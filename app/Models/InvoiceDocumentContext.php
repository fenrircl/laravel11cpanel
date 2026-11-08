<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDocumentContext extends Model
{
    protected $fillable = [
        'invoice_id',
        'file_registry_id',
        'source_sha256',
        'markdown',
        'metadata',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'invoice_id');
    }
}
