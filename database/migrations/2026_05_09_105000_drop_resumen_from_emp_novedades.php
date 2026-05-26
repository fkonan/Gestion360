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

        if (! $schema->hasTable('EMP_NOVEDADES') || ! $schema->hasColumn('EMP_NOVEDADES', 'resumen')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement('ALTER TABLE EMP_NOVEDADES DROP COLUMN resumen');
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
