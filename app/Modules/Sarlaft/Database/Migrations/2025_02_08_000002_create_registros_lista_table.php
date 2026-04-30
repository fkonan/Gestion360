<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_registros_lista', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lista_id')->constrained('sarlaft_listas_vinculantes');
            $table->enum('tipo_entidad', ['persona', 'organizacion']);
            $table->string('identificacion', 50)->nullable()->index();
            $table->string('tipo_identificacion', 20)->nullable();
            $table->string('nombres', 500);
            $table->text('alias')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('pais', 100)->nullable();
            $table->text('motivo')->nullable();
            $table->date('fecha_inclusion')->nullable();
            $table->string('referencia_externa', 100)->nullable();
            $table->enum('estado', ['activo', 'removido'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::connection('mysql-sarlaft')->statement('ALTER TABLE sarlaft_registros_lista ADD FULLTEXT ft_nombres (nombres, alias)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_registros_lista');
    }
};
