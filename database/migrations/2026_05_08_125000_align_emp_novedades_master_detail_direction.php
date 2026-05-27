<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $this->alignEmpNovedades();
        $this->alignEmpPermisos();
        $this->alignEmpIncapacidades();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function alignEmpNovedades(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_NOVEDADES')
            || ! $schema->hasColumn('EMP_NOVEDADES', 'id_tipo_novedad')
            || ! $schema->hasColumn('EMP_NOVEDADES', 'id_origen')) {
            return;
        }

        if (! $this->indexExists('emp_novedades_tipo_origen_idx')) {
            DB::connection(self::CONNECTION)->statement(
                'CREATE INDEX emp_novedades_tipo_origen_idx ON EMP_NOVEDADES (id_tipo_novedad, id_origen)'
            );
        }
    }

    private function alignEmpPermisos(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_PERMISOS') || ! $schema->hasColumn('EMP_PERMISOS', 'id_novedad')) {
            return;
        }

        $this->dropIndexIfExists('emp_permisos_id_novedad_idx');
        DB::connection(self::CONNECTION)->statement('ALTER TABLE EMP_PERMISOS DROP COLUMN id_novedad');
    }

    private function alignEmpIncapacidades(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_INCAPACIDADES') || ! $schema->hasColumn('EMP_INCAPACIDADES', 'id_novedad')) {
            return;
        }

        $this->dropIndexIfExists('emp_incapacidades_id_novedad_idx');
        DB::connection(self::CONNECTION)->statement('ALTER TABLE EMP_INCAPACIDADES DROP COLUMN id_novedad');
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
