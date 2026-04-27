<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_registros_lista', function (Blueprint $table) {
            $table->string('identificacion', 200)->nullable()->change();
            $table->string('tipo_identificacion', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_registros_lista', function (Blueprint $table) {
            $table->string('identificacion', 50)->nullable()->change();
            $table->string('tipo_identificacion', 20)->nullable()->change();
        });
    }
};
