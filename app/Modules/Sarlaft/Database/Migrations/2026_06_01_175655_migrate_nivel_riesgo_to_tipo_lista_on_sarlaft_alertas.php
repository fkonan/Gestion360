<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        // 1. Ampliar el ENUM temporalmente para que acepte los valores viejos y los nuevos.
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_alertas MODIFY nivel_riesgo
            ENUM('bajo','medio','alto','critico','vinculante','restrictiva') NOT NULL"
        );

        // 2. Migrar la data existente al nuevo lenguaje (coincidencia por tipo de lista).
        DB::connection($this->connection)->table('sarlaft_alertas')
            ->whereIn('nivel_riesgo', ['alto', 'critico'])
            ->update(['nivel_riesgo' => 'vinculante']);

        DB::connection($this->connection)->table('sarlaft_alertas')
            ->whereIn('nivel_riesgo', ['bajo', 'medio'])
            ->update(['nivel_riesgo' => 'restrictiva']);

        // 3. Reducir el ENUM a solo los valores nuevos.
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_alertas MODIFY nivel_riesgo
            ENUM('vinculante','restrictiva') NOT NULL"
        );
    }

    public function down(): void
    {
        // Revertir: ampliar ENUM, mapear de vuelta a los valores viejos y restaurar el enum original.
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_alertas MODIFY nivel_riesgo
            ENUM('bajo','medio','alto','critico','vinculante','restrictiva') NOT NULL"
        );

        DB::connection($this->connection)->table('sarlaft_alertas')
            ->where('nivel_riesgo', 'vinculante')
            ->update(['nivel_riesgo' => 'alto']);

        DB::connection($this->connection)->table('sarlaft_alertas')
            ->where('nivel_riesgo', 'restrictiva')
            ->update(['nivel_riesgo' => 'medio']);

        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_alertas MODIFY nivel_riesgo
            ENUM('bajo','medio','alto','critico') NOT NULL"
        );
    }
};
