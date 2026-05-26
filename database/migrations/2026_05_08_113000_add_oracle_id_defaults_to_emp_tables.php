<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    /**
     * @var array<int, string>
     */
    private array $tables = [
        'EMP_NOVEDADES',
        'EMP_NOVEDADES_TIPO',
        'EMP_NOVEDADES_HISTORIAL',
        'EMP_NOVEDADES_DOCUMENTOS',
        'EMP_TIPOS_DOCUMENTOS_NOVEDAD',
        'EMP_PERMISOS',
        'EMP_INCAPACIDADES',
    ];

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        foreach ($this->tables as $table) {
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, 'id')) {
                continue;
            }

            DB::connection(self::CONNECTION)->statement($this->buildDefaultIdStatement($table));
        }
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function buildDefaultIdStatement(string $table): string
    {
        return <<<SQL
ALTER TABLE {$table}
MODIFY (
    id DEFAULT LOWER(REGEXP_REPLACE(
        SYS_GUID(),
        '(.{8})(.{4})(.{4})(.{4})(.{12})',
        '\\1-\\2-\\3-\\4-\\5'
    ))
)
SQL;
    }
};
