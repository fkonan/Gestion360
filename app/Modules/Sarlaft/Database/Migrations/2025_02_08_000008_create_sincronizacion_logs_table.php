<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_sincronizacion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lista_id')->constrained('sarlaft_listas_vinculantes');
            $table->enum('estado', ['exitoso', 'fallido', 'parcial']);
            $table->integer('registros_procesados')->default(0);
            $table->integer('registros_nuevos')->default(0);
            $table->integer('registros_actualizados')->default(0);
            $table->text('error_mensaje')->nullable();
            $table->integer('duracion_segundos')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_sincronizacion_logs');
    }
};
