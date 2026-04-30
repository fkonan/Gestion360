<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_consultas', function (Blueprint $table): void {
            $table->json('contexto_operacion')->nullable()->after('coincidencias');
        });

        Schema::connection('mysql-sarlaft')->table('sarlaft_consultas_archivo', function (Blueprint $table): void {
            $table->json('contexto_operacion')->nullable()->after('coincidencias');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_consultas', function (Blueprint $table): void {
            $table->dropColumn('contexto_operacion');
        });

        Schema::connection('mysql-sarlaft')->table('sarlaft_consultas_archivo', function (Blueprint $table): void {
            $table->dropColumn('contexto_operacion');
        });
    }
};
