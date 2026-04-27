<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::create('sarlaft_sistemas_consumidores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50)->unique();
            $table->string('api_token', 100)->unique();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->integer('limite_requests_minuto')->default(100);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarlaft_sistemas_consumidores');
    }
};
