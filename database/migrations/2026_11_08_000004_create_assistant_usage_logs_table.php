<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assistant_usage_logs')) {
            return;
        }

        Schema::create('assistant_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->mediumText('user_message');
            $table->mediumText('assistant_message')->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedSmallInteger('tool_calls')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 16)->default('success');
            $table->string('error_type', 100)->nullable();
            $table->timestamps();
            $table->index(['created_at', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_usage_logs');
    }
};
