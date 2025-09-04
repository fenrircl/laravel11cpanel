<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 50);
            $table->string('module', 100)->nullable();
            $table->string('entity_id', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'module', 'action']);
            $table->index(['created_at']);
        });
        // Nota: No se agrega FK porque users.id es INT y puede estar en BD externa con diferencias
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
