<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assistant_conversations')) {
            Schema::create('assistant_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 120)->default('Nueva conversación');
                $table->timestamps();
                $table->index(['user_id', 'updated_at']);
            });
        }

        if (!Schema::hasTable('assistant_messages')) {
            Schema::create('assistant_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
                $table->string('role', 16);
                $table->text('content');
                $table->timestamps();
                $table->index(['conversation_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_conversations');
    }
};
