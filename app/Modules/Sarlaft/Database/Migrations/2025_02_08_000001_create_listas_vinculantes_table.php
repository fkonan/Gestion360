<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_listas_vinculantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('tipo', ['vinculante', 'recomendada', 'interna']);
            $table->string('url_fuente', 500)->nullable();
            $table->string('frecuencia_sync', 50)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamp('ultima_sincronizacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_listas_vinculantes');
    }
};
