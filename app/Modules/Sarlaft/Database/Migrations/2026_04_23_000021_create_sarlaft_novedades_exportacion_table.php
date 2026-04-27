<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_novedades_exportacion', function (Blueprint $table): void {
            $table->id();
            $table->enum('origen_lista', ['vinculante', 'interna']);
            $table->enum('tipo_novedad', ['ingreso', 'actualizado', 'salida']);
            $table->unsignedBigInteger('registro_lista_id')->nullable()->index();
            $table->unsignedBigInteger('lista_negra_id')->nullable()->index();
            $table->unsignedBigInteger('sincronizacion_log_id')->nullable()->index();
            $table->unsignedBigInteger('lista_id')->nullable()->index();
            $table->string('nombre_lista', 150);
            $table->json('datos')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['origen_lista', 'created_at'], 'idx_novedad_origen_fecha');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_novedades_exportacion');
    }
};
