<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_lista_negra_interna', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_entidad', ['persona', 'organizacion']);
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombres', 500);
            $table->text('motivo');
            $table->unsignedBigInteger('creado_por')->index();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo_documento', 'numero_documento'], 'idx_documento_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_lista_negra_interna');
    }
};
