<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql-gestion-pasajes')->create('bloqueos_pendientes', function (Blueprint $table) {
            $table->id();
            $table->string('identificacion', 20);
            $table->unsignedInteger('id_bloqueo_fics');
            $table->boolean('reactivar_si_sin_bloqueos')->default(false);
            $table->string('origen', 60)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedInteger('intentos')->default(0);
            $table->text('ultimo_error')->nullable();
            $table->timestamp('ultimo_intento_at')->nullable();
            $table->timestamp('proximo_intento_at')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();

            $table->index(['estado', 'proximo_intento_at'], 'idx_bloqueos_pend_estado_proximo');
            $table->index(['identificacion', 'id_bloqueo_fics'], 'idx_bloqueos_pend_doc_tipo');
            $table->index('resuelto_at', 'idx_bloqueos_pend_resuelto');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-gestion-pasajes')->dropIfExists('bloqueos_pendientes');
    }
};
