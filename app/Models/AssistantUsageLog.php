<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantUsageLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'conversation_id', 'user_message', 'assistant_message',
        'model', 'input_tokens', 'output_tokens', 'total_tokens', 'tool_calls',
        'duration_ms', 'status', 'error_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
