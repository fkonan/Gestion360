<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);
        if (! $schema->hasTable('EMP_VACACIONES') || ! $schema->hasColumn('EMP_VACACIONES', 'id')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement(<<<SQL
ALTER TABLE EMP_VACACIONES
MODIFY (
    id DEFAULT LOWER(REGEXP_REPLACE(
        SYS_GUID(),
        '(.{8})(.{4})(.{4})(.{4})(.{12})',
        '\\1-\\2-\\3-\\4-\\5'
    ))
)
SQL);
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
