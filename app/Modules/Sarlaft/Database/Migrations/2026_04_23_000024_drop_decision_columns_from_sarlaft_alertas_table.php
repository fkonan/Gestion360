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
            'decision_servicio',
            'decision_activa',
            'decision_consumida_at',
            'decision_consumida_consulta_id',
        ];

        $columnasExistentes = array_values(array_filter(
            $columnas,
            static fn (string $columna): bool => $schema->hasColumn('sarlaft_alertas', $columna),
        ));

        if ($columnasExistentes === []) {
            return;
        }

        $schema->table('sarlaft_alertas', function (Blueprint $table) use ($columnasExistentes): void {
            $table->dropColumn($columnasExistentes);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql-sarlaft');

        if ($schema->hasColumn('sarlaft_alertas', 'decision_servicio')) {
            return;
        }

        $schema->table('sarlaft_alertas', function (Blueprint $table): void {
            $table->enum('decision_servicio', [
                'sin_decision',
                'bloquear',
                'permitir_una_operacion',
                'permitir_permanente',
            ])->default('sin_decision')->after('numero_documento');
            $table->boolean('decision_activa')->default(false)->after('decision_servicio');
            $table->timestamp('decision_consumida_at')->nullable()->after('decision_activa');
            $table->unsignedBigInteger('decision_consumida_consulta_id')->nullable()->after('decision_consumida_at');

            $table->index('decision_activa', 'idx_alerta_decision_activa');
            $table->index('decision_consumida_consulta_id', 'idx_alerta_decision_consumida_consulta');
        });
    }
};
