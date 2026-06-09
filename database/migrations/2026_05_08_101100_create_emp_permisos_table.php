<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if ($schema->hasTable('EMP_PERMISOS')) {
            return;
        }

        $schema->create('EMP_PERMISOS', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('id_novedad', 100)->nullable();
            $table->string('motivo', 120)->nullable();
            $table->string('otro_motivo', 255)->nullable();
            $table->text('actividad')->nullable();
            $table->text('observacion')->nullable();
            $table->string('estado_especifico', 80)->nullable();
            $table->string('radicado_por_documento', 100)->nullable();
            $table->string('radicado_por_nombre', 255)->nullable();
            $table->string('origen', 80)->nullable();
            $table->string('ip_equipo', 120)->nullable();
            $table->string('sistema_origen', 120)->nullable();
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('id_novedad', 'emp_permisos_id_novedad_idx');
            $table->index('estado_especifico', 'emp_permisos_estado_idx');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
