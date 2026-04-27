<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('sarlaft_consultas');
            $table->string('tipo', 50);
            $table->enum('nivel_riesgo', ['bajo', 'medio', 'alto', 'critico']);
            $table->enum('estado', ['pendiente', 'en_revision', 'atendida', 'descartada'])->default('pendiente');
            $table->json('datos_persona');
            $table->json('listas_coincidentes');
            $table->json('contexto_operacion')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable()->index();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_alertas');
    }
};
