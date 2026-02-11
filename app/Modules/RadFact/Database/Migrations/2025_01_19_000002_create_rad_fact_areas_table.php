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
        Schema::create('rad_fact_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Usuario responsable del área');
            $table->string('area', 150)->comment('Nombre del área');
            $table->string('responsable', 200)->nullable()->comment('Nombre del responsable');
            $table->string('correo', 150)->nullable()->comment('Correo para notificaciones');
            $table->boolean('subgerencia')->default(false)->comment('Es área de subgerencia administrativa');
            $table->boolean('compras')->default(false)->comment('Es área de compras');
            $table->timestamps();

            $table->index('user_id');
            $table->index('area');
            $table->index('subgerencia');
            $table->index('compras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rad_fact_areas');
    }
};
