<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_bloqueos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombre', 300)->nullable();
            $table->enum('tipo_bloqueo', ['automatico', 'manual']);
            $table->enum('estado', ['bloqueado', 'desbloqueado']);
            $table->text('motivo_bloqueo');
            $table->text('justificacion_desbloqueo')->nullable();
            $table->json('documentos_soporte')->nullable();
            $table->unsignedBigInteger('creado_por')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_documento', 'idx_documento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_bloqueos');
    }
};
