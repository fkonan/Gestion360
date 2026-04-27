<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::table('sarlaft_bloqueos', function (Blueprint $table) {
            $table->dropColumn('documentos_soporte');
            $table->json('archivo_soporte')->nullable()->after('justificacion_desbloqueo');
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_bloqueos', function (Blueprint $table) {
            $table->dropColumn('archivo_soporte');
            $table->json('documentos_soporte')->nullable()->after('justificacion_desbloqueo');
        });
    }
};
