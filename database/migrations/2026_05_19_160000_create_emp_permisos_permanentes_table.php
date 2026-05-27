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

        if ($schema->hasTable('EMP_PERMISOS_PERMANENTES')) {
            return;
        }

        $schema->create('EMP_PERMISOS_PERMANENTES', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('documento_persona', 100)->nullable();
            $table->string('documento_radica', 100)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('jornada', 20)->nullable();
            $table->unsignedSmallInteger('horario_fijo')->default(0);
            $table->string('hora_salida_j1', 10)->nullable();
            $table->string('hora_ingreso_j1', 10)->nullable();
            $table->string('hora_salida_j2', 10)->nullable();
            $table->string('hora_ingreso_j2', 10)->nullable();
            $table->text('observacion')->nullable();
            $table->string('estado', 80)->nullable();
            $table->string('origen', 80)->nullable();
            $table->string('ip_equipo', 120)->nullable();
            $table->string('sistema_origen', 120)->nullable();
            $table->string('documento_aprobador', 100)->nullable();
            $table->string('nombre_aprobador', 255)->nullable();
            $table->string('correo_aprobador', 255)->nullable();
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('estado', 'emp_perm_perm_estado_idx');
            $table->index('documento_persona', 'emp_perm_perm_doc_persona_idx');
            $table->index('documento_aprobador', 'emp_perm_perm_doc_aprobador_idx');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
