<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('document_ingestions')) {
            Schema::create('document_ingestions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('source', 32);
                $table->string('external_id', 255);
                $table->string('external_version', 128)->default('');
                $table->char('source_sha256', 64)->nullable();
                $table->string('filename', 255)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('status', 32)->default('discovered');
                $table->string('r2_key', 1024)->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedBigInteger('file_registry_id')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('lease_until')->nullable();
                $table->timestamps();
                $table->unique(['source', 'external_id', 'external_version'], 'document_ingestions_source_external_unique');
                $table->index(['status', 'updated_at']);
                $table->index('source_sha256');
            });
        }

        if (!Schema::hasTable('invoice_extractions')) {
            Schema::create('invoice_extractions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ingestion_id')->unique();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->unsignedBigInteger('file_registry_id')->nullable()->index();
                $table->string('request_id', 100)->nullable();
                $table->string('schema_version', 32)->nullable();
                $table->string('adapter_version', 64);
                $table->char('source_sha256', 64)->nullable();
                $table->string('document_type', 48)->nullable();
                $table->string('document_subtype', 80)->nullable();
                $table->json('payload');
                $table->json('normalized_data')->nullable();
                $table->longText('raw_text')->nullable();
                $table->decimal('confidence', 6, 5)->nullable();
                $table->json('warnings')->nullable();
                $table->string('validation_status', 32)->default('pending');
                $table->boolean('is_active')->default(false);
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                $table->index(['validation_status', 'processed_at']);
            });
        }

        if (!Schema::hasTable('invoice_line_items')) {
            Schema::create('invoice_line_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->unsignedBigInteger('extraction_id');
                $table->unsignedInteger('line_number');
                $table->string('description_original', 1000);
                $table->string('description_normalized', 700)->nullable();
                $table->string('supplier_sku', 191)->nullable();
                $table->decimal('quantity', 20, 6)->nullable();
                $table->string('unit_original', 32)->nullable();
                $table->string('unit_normalized', 32)->nullable();
                $table->decimal('unit_price', 20, 6)->nullable();
                $table->decimal('discount_amount', 20, 6)->nullable();
                $table->decimal('net_amount', 20, 6)->nullable();
                $table->decimal('tax_amount', 20, 6)->nullable();
                $table->decimal('total_amount', 20, 6)->nullable();
                $table->string('currency', 3)->nullable();
                $table->string('price_tax_basis', 24)->default('unknown');
                $table->json('source_line')->nullable();
                $table->timestamps();
                $table->unique(['extraction_id', 'line_number'], 'invoice_line_extraction_number_unique');
                $table->index(['supplier_sku', 'invoice_id']);
                $table->index(['description_normalized', 'invoice_id'], 'invoice_line_description_invoice_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoice_extractions');
        Schema::dropIfExists('document_ingestions');
    }
};
