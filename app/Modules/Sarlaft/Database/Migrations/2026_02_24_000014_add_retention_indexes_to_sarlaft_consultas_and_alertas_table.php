<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_consultas', function (Blueprint $table) {
            $table->index('created_at', 'idx_consultas_created_at');
            $table->index(
                ['encontrado', 'presta_servicio', 'nivel_riesgo', 'created_at'],
                'idx_consultas_retencion'
            );
        });

        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table) {
            $table->index(['estado', 'nivel_riesgo', 'created_at'], 'idx_alertas_estado_riesgo_fecha');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_consultas', function (Blueprint $table) {
            $table->dropIndex('idx_consultas_created_at');
            $table->dropIndex('idx_consultas_retencion');
        });

        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table) {
            $table->dropIndex('idx_alertas_estado_riesgo_fecha');
        });
    }
};
