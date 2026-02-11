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
        Schema::create('rad_fact_radicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Usuario que radica');
            $table->foreignId('proveedor_id')
                ->constrained('rad_fact_proveedores')
                ->onDelete('restrict');
            $table->string('num_factura', 50)->comment('Número de factura del proveedor');
            $table->string('num_contrato', 50)->nullable()->comment('Número de contrato asociado');
            $table->unsignedSmallInteger('numero_pagos')->default(1)->comment('Cantidad de pagos');
            $table->date('fecha_radicacion')->comment('Fecha de radicación');
            $table->date('fecha_vencimiento')->comment('Fecha de vencimiento de la factura');
            $table->boolean('necesita_visto_bueno')->default(false)->comment('Requiere visto bueno de áreas');
            $table->text('descripcion')->nullable()->comment('Descripción de la factura');
            $table->decimal('valor', 15, 2)->comment('Valor total de la factura');
            $table->string('pdf', 500)->nullable()->comment('Ruta del archivo PDF');
            $table->text('observacion')->nullable();
            $table->string('estado', 30)->default('RADICADO')->comment('RADICADO, EN_APROBACION, PENDIENTE_SUBGERENCIA, PENDIENTE_COMPRAS, APROBADO, RECHAZADO, PAGADO');

            // Campos para aprobación de Subgerencia (Opción A)
            $table->date('fecha_envio_subgerencia')->nullable()->comment('Fecha envío a subgerencia');
            $table->string('estado_subgerencia', 20)->nullable()->comment('PENDIENTE, APROBADO, RECHAZADO');
            $table->text('observacion_subgerencia')->nullable()->comment('Observación de subgerencia');
            $table->unsignedBigInteger('usuario_subgerencia_id')->nullable()->comment('Usuario que aprobó/rechazó en subgerencia');
            $table->timestamp('fecha_respuesta_subgerencia')->nullable();

            // Campos para aprobación de Compras (Opción A)
            $table->date('fecha_envio_compras')->nullable()->comment('Fecha envío a compras');
            $table->string('estado_compras', 20)->nullable()->comment('PENDIENTE, APROBADO, RECHAZADO');
            $table->text('observacion_compras')->nullable()->comment('Observación de compras');
            $table->unsignedBigInteger('usuario_compras_id')->nullable()->comment('Usuario que aprobó/rechazó en compras');
            $table->timestamp('fecha_respuesta_compras')->nullable();

            $table->date('fecha_pago')->nullable()->comment('Fecha de pago');
            $table->timestamps();

            $table->index('user_id');
            $table->index('proveedor_id');
            $table->index('num_factura');
            $table->index('num_contrato');
            $table->index('estado');
            $table->index('fecha_radicacion');
            $table->index('fecha_vencimiento');
            $table->index(['proveedor_id', 'num_factura']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rad_fact_radicaciones');
    }
};
