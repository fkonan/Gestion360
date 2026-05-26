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

        if (! $schema->hasTable('EMP_CAUSAS_INCAPACIDAD')) {
            return;
        }

        $schema->table('EMP_CAUSAS_INCAPACIDAD', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_CAUSAS_INCAPACIDAD', 'causa')) {
                $table->string('causa', 255)->nullable();
            }
        });

        if ($schema->hasColumn('EMP_CAUSAS_INCAPACIDAD', 'ParNom')) {
            DB::connection(self::CONNECTION)->statement("
                UPDATE EMP_CAUSAS_INCAPACIDAD
                SET causa = ParNom
                WHERE causa IS NULL
            ");
        }

        $this->dropIndexIfExists('emp_causas_incapacidad_idparam_idx');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'IdParametro');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'ParNomGru');
        $this->dropColumnIfExists('EMP_CAUSAS_INCAPACIDAD', 'ParNom');
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
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
