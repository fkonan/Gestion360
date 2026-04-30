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
            $table->unsignedBigInteger('intento_id')->nullable()->after('consulta_id')->index();
        });

        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table) {
            $table->string('pull_endpoint', 500)->nullable()->after('limite_requests_minuto');
            $table->string('pull_token', 255)->nullable()->after('pull_endpoint');
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_alertas', function (Blueprint $table) {
            $table->dropColumn('intento_id');
        });

        Schema::table('sarlaft_sistemas_consumidores', function (Blueprint $table) {
            $table->dropColumn(['pull_endpoint', 'pull_token']);
        });
    }
};
