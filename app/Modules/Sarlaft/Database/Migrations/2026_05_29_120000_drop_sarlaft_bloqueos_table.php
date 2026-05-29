<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_bloqueos');
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_bloqueos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombre', 300)->nullable();
            $table->string('tipo_bloqueo', 30)->default('manual');
            $table->string('estado', 30)->default('bloqueado');
            $table->text('motivo_bloqueo')->nullable();
            $table->text('justificacion_desbloqueo')->nullable();
            $table->json('archivo_soporte')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_documento', 'numero_documento'], 'idx_bloqueos_documento');
            $table->index('estado', 'idx_bloqueos_estado');
        });
    }
};
