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
        $this->alignEmpPermisos();
        $this->alignEmpIncapacidades();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function alignEmpPermisos(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_PERMISOS')) {
            return;
        }

        $schema->table('EMP_PERMISOS', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_PERMISOS', 'documento_radica')) {
                $table->string('documento_radica', 100)->nullable();
            }
        });

        if ($schema->hasColumn('EMP_PERMISOS', 'radicado_por_documento')) {
            DB::connection(self::CONNECTION)->statement("
                UPDATE EMP_PERMISOS
                SET documento_radica = radicado_por_documento
                WHERE documento_radica IS NULL
            ");
        }

        $this->dropColumnIfExists('EMP_PERMISOS', 'radicado_por_documento');
        $this->dropColumnIfExists('EMP_PERMISOS', 'radicado_por_nombre');
        $this->dropColumnIfExists('EMP_PERMISOS', 'nombre_persona');
    }

    private function alignEmpIncapacidades(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_INCAPACIDADES')) {
            return;
        }

        $schema->table('EMP_INCAPACIDADES', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'documento_radica')) {
                $table->string('documento_radica', 100)->nullable();
            }
        });

        if ($schema->hasColumn('EMP_INCAPACIDADES', 'radicado_por_documento')) {
            DB::connection(self::CONNECTION)->statement("
                UPDATE EMP_INCAPACIDADES
                SET documento_radica = radicado_por_documento
                WHERE documento_radica IS NULL
            ");
        }

        $this->dropColumnIfExists('EMP_INCAPACIDADES', 'radicado_por_documento');
        $this->dropColumnIfExists('EMP_INCAPACIDADES', 'radicado_por_nombre');
        $this->dropColumnIfExists('EMP_INCAPACIDADES', 'nombre_persona');
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasColumn($table, $column)) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("ALTER TABLE {$table} DROP COLUMN {$column}");
    }
};
