<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table): void {
            // Ultimo ID de la tabla externa (modo db) ya procesado. Control
            // incremental principal: se leen solo filas con ID mayor a este.
            $table->unsignedBigInteger('db_ultimo_id')->nullable()->after('db_ultima_lectura_at');
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table): void {
            $table->dropColumn('db_ultimo_id');
        });
    }
};
