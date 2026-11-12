<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The production business schema is managed outside this repository.
        // These empty tables only let the local Docker preview load the dashboard.
        if (!app()->environment('local')) {
            return;
        }

        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('rut')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('box')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('providers')) {
            Schema::create('providers', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('rut')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('box')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table): void {
                $table->id();
                $table->string('invoice')->nullable();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('provider_id')->nullable()->index();
                $table->date('date')->nullable();
                $table->date('expiry')->nullable();
                $table->date('pay_date')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->unsignedTinyInteger('status')->default(0);
                $table->boolean('check')->default(false);
                $table->text('extra')->nullable();
                $table->longText('detail')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('clients');
    }
};
