<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';
    private const TABLE = 'EMP_DESCARGOS_CITACIONES';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);
        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        if ($this->indexExists('EMP_DESCARGOS_DOC_RRHH_IDX')) {
            DB::connection(self::CONNECTION)->statement('DROP INDEX EMP_DESCARGOS_DOC_RRHH_IDX');
        }

        if ($schema->hasColumn(self::TABLE, 'documento_rrhh_cita')) {
            DB::connection(self::CONNECTION)->statement('ALTER TABLE '.self::TABLE.' DROP COLUMN DOCUMENTO_RRHH_CITA');
        }

        if ($schema->hasColumn(self::TABLE, 'motivo')) {
            DB::connection(self::CONNECTION)->statement('ALTER TABLE '.self::TABLE.' DROP COLUMN MOTIVO');
        }

        if ($schema->hasColumn(self::TABLE, 'creado_por_documento') && ! $schema->hasColumn(self::TABLE, 'id_creacion')) {
            DB::connection(self::CONNECTION)->statement('ALTER TABLE '.self::TABLE.' RENAME COLUMN CREADO_POR_DOCUMENTO TO ID_CREACION');
        }

        if ($schema->hasColumn(self::TABLE, 'actualizado_por_documento') && ! $schema->hasColumn(self::TABLE, 'id_modifica')) {
            DB::connection(self::CONNECTION)->statement('ALTER TABLE '.self::TABLE.' RENAME COLUMN ACTUALIZADO_POR_DOCUMENTO TO ID_MODIFICA');
        }
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function indexExists(string $indexName): bool
    {
        $rows = DB::connection(self::CONNECTION)->select(
            "SELECT COUNT(*) AS TOTAL FROM USER_INDEXES WHERE UPPER(INDEX_NAME) = ?",
            [strtoupper($indexName)]
        );

        $total = isset($rows[0]->total) ? (int) $rows[0]->total : 0;

        return $total > 0;
    }
};

