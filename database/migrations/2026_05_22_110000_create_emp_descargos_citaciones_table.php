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

        if ($schema->hasTable('EMP_DESCARGOS_CITACIONES')) {
            return;
        }

        $schema->create('EMP_DESCARGOS_CITACIONES', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('documento_persona', 100);
            $table->string('documento_rrhh_cita', 100);
            $table->dateTime('fecha_citacion');
            $table->text('motivo')->nullable();
            $table->text('observacion')->nullable();
            $table->string('estado', 80)->default('CITADO_DESCARGOS');
            $table->dateTime('fecha_notificacion')->nullable();
            $table->string('origen', 80)->nullable();
            $table->string('ip_equipo', 120)->nullable();
            $table->string('sistema_origen', 120)->nullable();
            $table->string('creado_por_documento', 100)->nullable();
            $table->string('actualizado_por_documento', 100)->nullable();
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('documento_persona', 'emp_descargos_doc_persona_idx');
            $table->index('documento_rrhh_cita', 'emp_descargos_doc_rrhh_idx');
            $table->index('estado', 'emp_descargos_estado_idx');
            $table->index('fecha_citacion', 'emp_descargos_fecha_citacion_idx');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};

