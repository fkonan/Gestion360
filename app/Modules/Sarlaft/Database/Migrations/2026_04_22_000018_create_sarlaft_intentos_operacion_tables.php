<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_intentos_operacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sistema_id')->index();
            $table->enum('modo_integracion', ['push', 'pull']);
            $table->enum('tipo_operacion', ['pasaje', 'carga', 'contrato', 'compra', 'pago', 'venta']);
            $table->string('referencia_externa', 100)->nullable();
            $table->dateTime('fecha_operacion');
            $table->string('origen', 200)->nullable();
            $table->string('destino', 200)->nullable();
            $table->decimal('monto', 14, 2)->nullable();
            $table->char('moneda', 3)->default('COP');
            $table->string('descripcion', 500)->nullable();
            $table->json('contexto')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->timestamps();

            $table->index(['sistema_id', 'tipo_operacion', 'created_at'], 'idx_intento_sistema_tipo_fecha');
            $table->index(['referencia_externa'], 'idx_intento_referencia');
        });

        Schema::create('sarlaft_intento_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_id')
                ->constrained('sarlaft_intentos_operacion')
                ->cascadeOnDelete();
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombre', 300)->nullable();
            $table->string('rol', 50);
            $table->enum('tipo_lista', ['vinculante', 'restrictiva']);
            $table->string('lista_nombre', 150);
            $table->json('detalle_coincidencia')->nullable();

            $table->index(['intento_id'], 'idx_intento_persona_intento');
            $table->index(['tipo_documento', 'numero_documento'], 'idx_intento_persona_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_intento_personas');
        Schema::dropIfExists('sarlaft_intentos_operacion');
    }
};
