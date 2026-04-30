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
        Schema::connection('mysql-sarlaft')->create('sarlaft_politicas', function (Blueprint $table): void {
            $table->id();
            $table->boolean('suppress_alert_on_bloquear')->default(true);
            $table->boolean('suppress_alert_on_permitir_permanente')->default(true);
            $table->unsignedTinyInteger('sla_dias_alerta')->default(1);
            $table->json('auto_escalar_riesgos')->nullable();
            $table->string('auto_estado', 20)->default('en_revision');
            $table->string('auto_decision', 30)->default('bloquear');
            $table->boolean('auto_atender_lista_negra_interna')->default(true);
            $table->boolean('auto_crear_alerta_atendida')->default(true);
            $table->unsignedBigInteger('auto_user_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')->insert([
            'id' => 1,
            'suppress_alert_on_bloquear' => true,
            'suppress_alert_on_permitir_permanente' => true,
            'sla_dias_alerta' => 1,
            'auto_escalar_riesgos' => json_encode(['alto', 'critico']),
            'auto_estado' => 'en_revision',
            'auto_decision' => 'bloquear',
            'auto_atender_lista_negra_interna' => true,
            'auto_crear_alerta_atendida' => true,
            'auto_user_id' => (int) config('sarlaft.auto_user_id', 1),
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_politicas');
    }
};
