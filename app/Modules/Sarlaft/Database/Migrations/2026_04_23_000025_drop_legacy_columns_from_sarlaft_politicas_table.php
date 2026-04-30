<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        $schema = Schema::connection('mysql-sarlaft');
        $columnas = [
            'suppress_alert_on_bloquear',
            'suppress_alert_on_permitir_permanente',
            'auto_decision',
        ];

        $columnasExistentes = array_values(array_filter(
            $columnas,
            static fn (string $columna): bool => $schema->hasColumn('sarlaft_politicas', $columna),
        ));

        if ($columnasExistentes === []) {
            return;
        }

        $schema->table('sarlaft_politicas', function (Blueprint $table) use ($columnasExistentes): void {
            $table->dropColumn($columnasExistentes);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql-sarlaft');

        if ($schema->hasColumn('sarlaft_politicas', 'auto_decision')) {
            return;
        }

        $schema->table('sarlaft_politicas', function (Blueprint $table): void {
            $table->boolean('suppress_alert_on_bloquear')->default(true)->after('id');
            $table->boolean('suppress_alert_on_permitir_permanente')->default(true)->after('suppress_alert_on_bloquear');
            $table->string('auto_decision', 30)->default('bloquear')->after('auto_estado');
        });
    }
};
