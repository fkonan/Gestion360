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

        if ($schema->hasTable('EMP_INCAPACIDADES')) {
            return;
        }

        $schema->create('EMP_INCAPACIDADES', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('id_novedad', 100)->nullable();
            $table->string('id_origen_externo', 100)->nullable();
            $table->string('causa_codigo', 80)->nullable();
            $table->string('causa_descripcion', 255)->nullable();
            $table->string('diagnostico_codigo', 80)->nullable();
            $table->string('diagnostico_descripcion', 500)->nullable();
            $table->string('eps_codigo', 80)->nullable();
            $table->string('eps_nombre', 255)->nullable();
            $table->string('arl_codigo', 80)->nullable();
            $table->string('arl_nombre', 255)->nullable();
            $table->string('tipo_incapacidad', 80)->nullable();
            $table->string('estado_especifico', 80)->nullable();
            $table->text('observacion')->nullable();
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('id_novedad', 'emp_incapacidades_id_novedad_idx');
            $table->index('id_origen_externo', 'emp_incapacidades_origen_ext_idx');
            $table->index('estado_especifico', 'emp_incapacidades_estado_idx');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
