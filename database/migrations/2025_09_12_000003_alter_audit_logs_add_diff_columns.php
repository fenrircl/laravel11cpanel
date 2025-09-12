<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // En SQLite/MySQL usaremos longText para JSON
            $table->longText('data_before')->nullable()->after('description');
            $table->longText('data_after')->nullable()->after('data_before');
            $table->longText('changes')->nullable()->after('data_after');
            $table->string('model', 191)->nullable()->after('module');
            $table->boolean('reversible')->default(false)->after('changes');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['data_before','data_after','changes','model','reversible']);
        });
    }
};
