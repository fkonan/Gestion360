<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $this->alignEmpEps($schema);
        $this->alignEmpArl($schema);
        $this->alignEmpDiagnosticos($schema);
        $this->alignEmpCausasIncapacidad($schema);
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function alignEmpEps($schema): void
    {
        if (! $schema->hasTable('EMP_EPS')) {
            return;
        }

        $schema->table('EMP_EPS', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_EPS', 'IdEPS')) {
                $table->unsignedInteger('IdEPS')->nullable();
            }

            if (! $schema->hasColumn('EMP_EPS', 'EPSNombre')) {
                $table->string('EPSNombre', 255)->nullable();
            }
        });

        $this->dropIndexIfExists('emp_eps_origen_idx');
        $this->dropIndexIfExists('emp_eps_codigo_idx');
        $this->dropColumnIfExists('EMP_EPS', 'id_origen_externo');
        $this->dropColumnIfExists('EMP_EPS', 'codigo');
        $this->dropColumnIfExists('EMP_EPS', 'nombre');
        $this->dropColumnIfExists('EMP_EPS', 'activo');
        $this->dropColumnIfExists('EMP_EPS', 'fecha_creacion');
        $this->dropColumnIfExists('EMP_EPS', 'fecha_modifica');

        $schema->table('EMP_EPS', function (Blueprint $table) {
            $table->index('IdEPS', 'emp_eps_ideps_idx');
        });
    }

    private function alignEmpArl($schema): void
    {
        if (! $schema->hasTable('EMP_ARL')) {
            return;
        }

        $schema->table('EMP_ARL', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_ARL', 'IdARL')) {
                $table->unsignedInteger('IdARL')->nullable();
            }

            if (! $schema->hasColumn('EMP_ARL', 'ARLNombre')) {
                $table->string('ARLNombre', 255)->nullable();
            }
        });

        $this->dropIndexIfExists('emp_arl_origen_idx');
        $this->dropIndexIfExists('emp_arl_codigo_idx');
        $this->dropColumnIfExists('EMP_ARL', 'id_origen_externo');
        $this->dropColumnIfExists('EMP_ARL', 'codigo');
        $this->dropColumnIfExists('EMP_ARL', 'nombre');
        $this->dropColumnIfExists('EMP_ARL', 'activo');
        $this->dropColumnIfExists('EMP_ARL', 'fecha_creacion');
        $this->dropColumnIfExists('EMP_ARL', 'fecha_modifica');

        $schema->table('EMP_ARL', function (Blueprint $table) {
            $table->index('IdARL', 'emp_arl_idarl_idx');
        });
    }

    private function alignEmpDiagnosticos($schema): void
    {
        if (! $schema->hasTable('EMP_DIAGNOSTICOS')) {
            return;
        }

        $schema->table('EMP_DIAGNOSTICOS', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_DIAGNOSTICOS', 'IdEnfermedad')) {
                $table->unsignedInteger('IdEnfermedad')->nullable();
            }

            if (! $schema->hasColumn('EMP_DIAGNOSTICOS', 'CodigoCie')) {
                $table->string('CodigoCie', 80)->nullable();
            }

            if (! $schema->hasColumn('EMP_DIAGNOSTICOS', 'DescCie')) {
                $table->string('DescCie', 1000)->nullable();
            }
        });

        $this->dropIndexIfExists('emp_diagnosticos_origen_idx');
        $this->dropIndexIfExists('emp_diagnosticos_codigo_idx');
        $this->dropColumnIfExists('EMP_DIAGNOSTICOS', 'id_origen_externo');
        $this->dropColumnIfExists('EMP_DIAGNOSTICOS', 'codigo');
        $this->dropColumnIfExists('EMP_DIAGNOSTICOS', 'descripcion');
        $this->dropColumnIfExists('EMP_DIAGNOSTICOS', 'activo');
        $this->dropColumnIfExists('EMP_DIAGNOSTICOS', 'fecha_creacion');
        $this->dropColumnIfExists('EMP_DIAGNOSTICOS', 'fecha_modifica');

        $schema->table('EMP_DIAGNOSTICOS', function (Blueprint $table) {
            $table->index('IdEnfermedad', 'emp_diagnosticos_idenfermedad_idx');
        });
    }

    private function alignEmpCausasIncapacidad($schema): void
    {
        if (! $schema->hasTable('EMP_CAUSAS_INCAPACIDAD')) {
            return;
        }

        $schema->table('EMP_CAUSAS_INCAPACIDAD', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_CAUSAS_INCAPACIDAD', 'IdParametro')) {
                $table->unsignedInteger('IdParametro')->nullable();
            }

            if (! $schema->hasColumn('EMP_CAUSAS_INCAPACIDAD', 'ParNomGru')) {
                $table->string('ParNomGru', 120)->nullable();
            }

            if (! $schema->hasColumn('EMP_CAUSAS_INCAPACIDAD', 'ParNom')) {
                $table->string('ParNom', 255)->nullable();
            }
        });

        $this->dropIndexIfExists('emp_causas_incapacidad_origen_idx');
        $this->dropIndexIfExists('emp_causas_incapacidad_codigo_idx');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'id_origen_externo');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'codigo');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'nombre');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'descripcion');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'activo');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'fecha_creacion');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'fecha_modifica');

        $schema->table('EMP_CAUSAS_INCAPACIDAD', function (Blueprint $table) {
            $table->index('IdParametro', 'emp_causas_incapacidad_idparam_idx');
        });
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasColumn($table, $column)) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    private function dropIndexIfExists(string $index): void
    {
        try {
            DB::connection(self::CONNECTION)->statement("DROP INDEX {$index}");
        } catch (\Throwable $exception) {
            // El indice puede no existir segun el estado previo del esquema.
        }
    }
};
