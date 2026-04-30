<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table) {
            $table->string('tipo_documento', 20)->nullable()->after('estado');
            $table->string('numero_documento', 50)->nullable()->after('tipo_documento');
            $table->enum('decision_servicio', [
                'sin_decision',
                'bloquear',
                'permitir_una_operacion',
                'permitir_permanente',
            ])->default('sin_decision')->after('numero_documento');
            $table->boolean('decision_activa')->default(false)->after('decision_servicio');
            $table->timestamp('decision_consumida_at')->nullable()->after('decision_activa');
            $table->unsignedBigInteger('decision_consumida_consulta_id')->nullable()->after('decision_consumida_at');

            $table->index(['tipo_documento', 'numero_documento'], 'idx_alerta_documento');
            $table->index('decision_activa', 'idx_alerta_decision_activa');
            $table->index('decision_consumida_consulta_id', 'idx_alerta_decision_consumida_consulta');
        });

        DB::connection('mysql-sarlaft')
            ->table('sarlaft_alertas')
            ->orderBy('id')
            ->chunkById(200, function ($alertas): void {
                foreach ($alertas as $alerta) {
                    $datosPersona = is_string($alerta->datos_persona)
                        ? json_decode($alerta->datos_persona, true)
                        : (array) $alerta->datos_persona;

                    $tipoDocumento = isset($datosPersona['tipo_documento']) ? (string) $datosPersona['tipo_documento'] : null;
                    $numeroDocumento = isset($datosPersona['numero_documento']) ? (string) $datosPersona['numero_documento'] : null;

                    $decisionServicio = 'sin_decision';
                    $decisionActiva = false;

                    if ($alerta->estado === 'descartada') {
                        $decisionServicio = 'permitir_una_operacion';
                        $decisionActiva = true;
                    }

                    DB::connection('mysql-sarlaft')
                        ->table('sarlaft_alertas')
                        ->where('id', $alerta->id)
                        ->update([
                            'tipo_documento' => $tipoDocumento !== '' ? $tipoDocumento : null,
                            'numero_documento' => $numeroDocumento !== '' ? $numeroDocumento : null,
                            'decision_servicio' => $decisionServicio,
                            'decision_activa' => $decisionActiva,
                            'decision_consumida_at' => null,
                            'decision_consumida_consulta_id' => null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table) {
            $table->dropIndex('idx_alerta_documento');
            $table->dropIndex('idx_alerta_decision_activa');
            $table->dropIndex('idx_alerta_decision_consumida_consulta');

            $table->dropColumn([
                'tipo_documento',
                'numero_documento',
                'decision_servicio',
                'decision_activa',
                'decision_consumida_at',
                'decision_consumida_consulta_id',
            ]);
        });
    }
};
