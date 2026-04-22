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
        Schema::create('rad_fact_distribuciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Usuario que creó la distribución');
            $table->foreignId('radicacion_id')
                ->constrained('rad_fact_radicaciones')
                ->onDelete('cascade');
            $table->foreignId('area_id')
                ->constrained('rad_fact_areas')
                ->onDelete('restrict');
            $table->decimal('porcentaje', 5, 2)->comment('Porcentaje asignado al área (0.00 - 100.00)');
            $table->decimal('valor_calculado', 15, 2)->comment('Valor calculado según porcentaje');
            $table->text('observacion')->nullable();
            $table->boolean('activo')->default(true)->comment('Indica si es la distribución vigente');
            $table->timestamps();

            // Sin UNIQUE para permitir múltiples distribuciones (historial)
            $table->index('user_id');
            $table->index('radicacion_id');
            $table->index('area_id');
            $table->index('activo');
            $table->index(['radicacion_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rad_fact_distribuciones');
    }
};
