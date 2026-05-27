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

        $this->createEmpCausasIncapacidad($schema);
        $this->createEmpDiagnosticos($schema);
        $this->createEmpEps($schema);
        $this->createEmpArl($schema);
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function createEmpCausasIncapacidad($schema): void
    {
        if ($schema->hasTable('EMP_CAUSAS_INCAPACIDAD')) {
            return;
        }

        $schema->create('EMP_CAUSAS_INCAPACIDAD', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('id_origen_externo', 100)->nullable();
            $table->string('codigo', 80)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->string('descripcion', 500)->nullable();
            $table->unsignedSmallInteger('activo')->default(1);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('id_origen_externo', 'emp_causas_incapacidad_origen_idx');
            $table->index('codigo', 'emp_causas_incapacidad_codigo_idx');
        });
    }

    private function createEmpDiagnosticos($schema): void
    {
        if ($schema->hasTable('EMP_DIAGNOSTICOS')) {
            return;
        }

        $schema->create('EMP_DIAGNOSTICOS', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('id_origen_externo', 100)->nullable();
            $table->string('codigo', 80)->nullable();
            $table->string('descripcion', 1000)->nullable();
            $table->unsignedSmallInteger('activo')->default(1);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('id_origen_externo', 'emp_diagnosticos_origen_idx');
            $table->index('codigo', 'emp_diagnosticos_codigo_idx');
        });
    }

    private function createEmpEps($schema): void
    {
        if ($schema->hasTable('EMP_EPS')) {
            return;
        }

        $schema->create('EMP_EPS', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('id_origen_externo', 100)->nullable();
            $table->string('codigo', 80)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->unsignedSmallInteger('activo')->default(1);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('id_origen_externo', 'emp_eps_origen_idx');
            $table->index('codigo', 'emp_eps_codigo_idx');
        });
    }

    private function createEmpArl($schema): void
    {
        if ($schema->hasTable('EMP_ARL')) {
            return;
        }

        $schema->create('EMP_ARL', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('id_origen_externo', 100)->nullable();
            $table->string('codigo', 80)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->unsignedSmallInteger('activo')->default(1);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_modifica')->nullable();

            $table->index('id_origen_externo', 'emp_arl_origen_idx');
            $table->index('codigo', 'emp_arl_codigo_idx');
        });
    }
};
