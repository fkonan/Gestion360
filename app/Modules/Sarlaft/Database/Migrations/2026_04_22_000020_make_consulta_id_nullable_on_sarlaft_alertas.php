<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::table('sarlaft_alertas', function (Blueprint $table) {
            $table->unsignedBigInteger('consulta_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_alertas', function (Blueprint $table) {
            $table->unsignedBigInteger('consulta_id')->nullable(false)->change();
        });
    }
};
