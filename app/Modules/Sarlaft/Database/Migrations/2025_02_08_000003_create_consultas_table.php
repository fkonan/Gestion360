<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_consultas', function (Blueprint $table) {
            $table->id();
            $table->string('sistema_origen', 50);
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombre_consultado', 300)->nullable();
            $table->boolean('encontrado');
            $table->boolean('presta_servicio');
            $table->enum('nivel_riesgo', ['ninguno', 'bajo', 'medio', 'alto'])->nullable();
            $table->json('coincidencias')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('numero_documento', 'idx_documento');
            $table->index(['sistema_origen', 'created_at'], 'idx_sistema_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_consultas');
    }
};
