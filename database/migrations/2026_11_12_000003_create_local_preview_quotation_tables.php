<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        if (!Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->string('agent', 100)->nullable();
                $table->date('date')->nullable();
                $table->unsignedBigInteger('total')->default(0);
                $table->string('work', 1000)->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quotation_items')) {
            Schema::create('quotation_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('quotation_id')->index();
                $table->string('description', 1000);
                $table->unsignedInteger('amount')->default(1);
                $table->unsignedBigInteger('price')->default(0);
                $table->unsignedBigInteger('total')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
