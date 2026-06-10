<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        // Agrega el modo 'consulta' (autenticacion JWT para empresas que solo
        // verifican coincidencia en listas).
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_sistemas_consumidores MODIFY modo_integracion
            ENUM('push','pull','db','consulta') NOT NULL DEFAULT 'pull'"
        );
    }

    public function down(): void
    {
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_sistemas_consumidores MODIFY modo_integracion
            ENUM('push','pull','db') NOT NULL DEFAULT 'pull'"
        );
    }
};
