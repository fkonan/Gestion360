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
        $this->alignEmpNovedadesTipo();
        $this->alignEmpPermisos();
        $this->alignEmpIncapacidades();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function alignEmpNovedadesTipo(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_NOVEDADES_TIPO')) {
            return;
        }

        $this->dropColumnIfExists('EMP_NOVEDADES_TIPO', 'justifica_tardanza');
        $this->dropColumnIfExists('EMP_NOVEDADES_TIPO', 'afecta_operacion');
    }

    private function alignEmpPermisos(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_PERMISOS')) {
            return;
        }

        $schema->table('EMP_PERMISOS', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_PERMISOS', 'documento_persona')) {
                $table->string('documento_persona', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_PERMISOS', 'nombre_persona')) {
                $table->string('nombre_persona', 255)->nullable();
            }

            if (! $schema->hasColumn('EMP_PERMISOS', 'estado')) {
                $table->string('estado', 80)->nullable();
            }
        });

        if ($schema->hasColumn('EMP_PERMISOS', 'estado_especifico')) {
            DB::connection(self::CONNECTION)->statement("
                UPDATE EMP_PERMISOS
                SET estado = estado_especifico
                WHERE estado IS NULL
            ");
        }

        $this->dropIndexIfExists('emp_permisos_estado_idx');
        $this->dropColumnIfExists('EMP_PERMISOS', 'estado_especifico');

        if (! $this->indexExists('emp_permisos_estado_idx')) {
            DB::connection(self::CONNECTION)->statement(
                'CREATE INDEX emp_permisos_estado_idx ON EMP_PERMISOS (estado)'
            );
        }
    }

    private function alignEmpIncapacidades(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_INCAPACIDADES')) {
            return;
        }

        $schema->table('EMP_INCAPACIDADES', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'documento_persona')) {
                $table->string('documento_persona', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'nombre_persona')) {
                $table->string('nombre_persona', 255)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'radicado_por_documento')) {
                $table->string('radicado_por_documento', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'radicado_por_nombre')) {
                $table->string('radicado_por_nombre', 255)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'estado')) {
                $table->string('estado', 80)->nullable();
            }
        });

        if ($schema->hasColumn('EMP_INCAPACIDADES', 'estado_especifico')) {
            DB::connection(self::CONNECTION)->statement("
                UPDATE EMP_INCAPACIDADES
                SET estado = estado_especifico
                WHERE estado IS NULL
            ");
        }

        $this->dropIndexIfExists('emp_incapacidades_estado_idx');
        $this->dropColumnIfExists('EMP_INCAPACIDADES', 'estado_especifico');

        if (! $this->indexExists('emp_incapacidades_estado_idx')) {
            DB::connection(self::CONNECTION)->statement(
                'CREATE INDEX emp_incapacidades_estado_idx ON EMP_INCAPACIDADES (estado)'
            );
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasColumn($table, $column)) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    private function indexExists(string $indexName): bool
    {
        $result = DB::connection(self::CONNECTION)->selectOne(
            'SELECT COUNT(*) AS total FROM user_indexes WHERE index_name = ?',
            [strtoupper($indexName)]
        );

        return (int) ($result->total ?? $result->TOTAL ?? 0) > 0;
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("DROP INDEX {$index}");
    }
};
