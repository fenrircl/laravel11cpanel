<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_document_contexts')) {
            return;
        }

        Schema::create('invoice_document_contexts', function (Blueprint $table) {
            $table->id();
            // invoices and files_registry are legacy tables not owned by Laravel migrations.
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('file_registry_id')->unique();
            $table->char('source_sha256', 64)->nullable();
            $table->longText('markdown');
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'processed_at']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('invoice_document_contexts', function (Blueprint $table) {
                $table->fullText('markdown');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_document_contexts');
    }
};
