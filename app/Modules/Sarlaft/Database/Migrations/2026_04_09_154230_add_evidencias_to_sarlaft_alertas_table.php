<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table): void {
            $table->json('evidencias')->nullable()->after('contexto_operacion');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table): void {
            $table->dropColumn('evidencias');
        });
    }
};
