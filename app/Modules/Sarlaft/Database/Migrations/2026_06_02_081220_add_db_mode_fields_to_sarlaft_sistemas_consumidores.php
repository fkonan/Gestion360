<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table): void {
            // Modo de ingesta del sistema consumidor: push (nos llaman), pull (consultamos
            // su endpoint), db (leemos directamente su tabla en otra BD).
            $table->enum('modo_integracion', ['push', 'pull', 'db'])
                ->default('pull')
                ->after('estado');

            // Configuracion para el modo 'db' (lectura directa de BD externa).
            $table->string('db_conexion', 100)->nullable()->after('pull_token');
            $table->string('db_tabla', 150)->nullable()->after('db_conexion');
            $table->string('db_filtro_sistema_origen', 100)->nullable()->after('db_tabla');
            $table->timestamp('db_ultima_lectura_at')->nullable()->after('db_filtro_sistema_origen');
        });

        // Ampliar el ENUM de modo_integracion en intentos para aceptar 'db'.
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_intentos_operacion MODIFY modo_integracion
            ENUM('push','pull','db') NOT NULL"
        );
    }

    public function down(): void
    {
        // Revertir el ENUM de intentos (asume que no quedan registros 'db'; si los hay,
        // habria que migrarlos antes de revertir).
        DB::connection($this->connection)->statement(
            "ALTER TABLE sarlaft_intentos_operacion MODIFY modo_integracion
            ENUM('push','pull') NOT NULL"
        );

        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table): void {
            $table->dropColumn([
                'modo_integracion',
                'db_conexion',
                'db_tabla',
                'db_filtro_sistema_origen',
                'db_ultima_lectura_at',
            ]);
        });
    }
};
