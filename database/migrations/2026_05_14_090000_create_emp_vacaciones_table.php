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

        if ($schema->hasTable('EMP_VACACIONES')) {
            return;
        }

        $schema->create('EMP_VACACIONES', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('documento_persona', 100)->nullable();
            $table->string('documento_radica', 100)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('observacion')->nullable();
            $table->string('estado', 80)->nullable();
            $table->string('origen', 80)->nullable();
            $table->string('ip_equipo', 120)->nullable();
            $table->string('sistema_origen', 120)->nullable();
            $table->string('tipo_aprobador', 30)->nullable();
            $table->string('documento_aprobador', 100)->nullable();
            $table->string('nombre_aprobador', 255)->nullable();
            $table->string('correo_aprobador', 255)->nullable();
            $table->unsignedSmallInteger('requiere_aprobacion_rrhh')->default(1);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('estado', 'emp_vacaciones_estado_idx');
            $table->index('documento_persona', 'emp_vacaciones_doc_persona_idx');
            $table->index('documento_aprobador', 'emp_vacaciones_doc_aprobador_idx');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
