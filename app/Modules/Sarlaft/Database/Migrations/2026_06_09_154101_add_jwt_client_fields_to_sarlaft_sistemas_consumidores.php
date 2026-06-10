<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table): void {
            // Credenciales para autenticacion JWT (client_credentials).
            // El client_id es la columna `codigo` existente; aqui se agrega el
            // secret (hasheado) y los scopes (permisos) del cliente.
            $table->string('client_secret', 255)->nullable()->after('api_token');
            $table->string('scopes', 500)->nullable()->after('client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table): void {
            $table->dropColumn(['client_secret', 'scopes']);
        });
    }
};
