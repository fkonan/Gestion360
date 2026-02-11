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
        Schema::create('rad_fact_proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento', 20)->comment('Tipo de documento (CC, NIT, CE, etc.)');
            $table->string('documento', 50)->unique()->comment('Número de documento');
            $table->string('nombres', 150)->nullable()->comment('Nombres (persona natural)');
            $table->string('apellidos', 150)->nullable()->comment('Apellidos (persona natural)');
            $table->string('razon_social', 255)->nullable()->comment('Razón social (persona jurídica)');
            $table->string('telefono', 50)->nullable();
            $table->string('correo', 150)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Usuario que registró el proveedor');
            $table->timestamps();

            $table->index('documento');
            $table->index('razon_social');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rad_fact_proveedores');
    }
};
