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

        if (! $schema->hasTable('EMP_INCAPACIDADES') || ! $schema->hasColumn('EMP_INCAPACIDADES', 'id_origen_externo')) {
            return;
        }

        $schema->table('EMP_INCAPACIDADES', function (Blueprint $table) {
            $table->dropColumn('id_origen_externo');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
