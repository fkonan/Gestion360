<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_simulacion_pasajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('sarlaft_consultas');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('ciudad_origen_id');
            $table->string('ciudad_origen_nombre', 150);
            $table->unsignedBigInteger('ciudad_destino_id');
            $table->string('ciudad_destino_nombre', 150);
            $table->date('fecha_viaje');
            $table->string('tipo_documento', 20);
            $table->string('documento', 50);
            $table->string('nombres', 150);
            $table->string('apellidos', 150);
            $table->string('direccion', 250);
            $table->string('telefono', 30);
            $table->string('correo', 150);
            $table->boolean('encontrado');
            $table->boolean('presta_servicio');
            $table->enum('nivel_riesgo', ['ninguno', 'bajo', 'medio', 'alto'])->nullable();
            $table->json('coincidencias')->nullable();
            $table->timestamps();

            $table->index(['documento', 'fecha_viaje'], 'idx_sim_pasaje_doc_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_simulacion_pasajes');
    }
};
