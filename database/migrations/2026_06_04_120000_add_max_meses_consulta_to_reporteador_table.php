<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'mysql-gestion-admin';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('reporteador') || $schema->hasColumn('reporteador', 'max_meses_consulta')) {
            return;
        }

        $schema->table('reporteador', function (Blueprint $table) {
            // null = usa el default global, 0 = sin limite, N = maximo en meses
            $table->unsignedSmallInteger('max_meses_consulta')
                ->nullable()
                ->after('parametros');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('reporteador') || ! $schema->hasColumn('reporteador', 'max_meses_consulta')) {
            return;
        }

        $schema->table('reporteador', function (Blueprint $table) {
            $table->dropColumn('max_meses_consulta');
        });
    }
};
