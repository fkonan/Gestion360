<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_mantenimiento_logs', function (Blueprint $table) {
            $table->id();
            $table->string('proceso', 100);
            $table->enum('estado', ['exitoso', 'parcial', 'fallido'])->default('exitoso');
            $table->unsignedInteger('registros_evaluados')->default(0);
            $table->unsignedInteger('registros_procesados')->default(0);
            $table->unsignedInteger('registros_omitidos')->default(0);
            $table->integer('duracion_segundos')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->json('detalles')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['proceso', 'created_at'], 'idx_mantenimiento_proceso_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_mantenimiento_logs');
    }
};
