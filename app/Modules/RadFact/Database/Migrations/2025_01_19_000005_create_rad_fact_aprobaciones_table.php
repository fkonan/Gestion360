<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rad_fact_aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Usuario que aprueba/rechaza');
            $table->foreignId('distribucion_id')
                ->constrained('rad_fact_distribuciones')
                ->onDelete('cascade');
            $table->string('estado', 20)->default('PENDIENTE')->comment('PENDIENTE, APROBADO, RECHAZADO');
            $table->text('observacion')->nullable()->comment('Observación del aprobador');
            $table->timestamp('fecha_respuesta')->nullable()->comment('Fecha de aprobación o rechazo');
            $table->timestamps();

            $table->index('user_id');
            $table->index('distribucion_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rad_fact_aprobaciones');
    }
};
