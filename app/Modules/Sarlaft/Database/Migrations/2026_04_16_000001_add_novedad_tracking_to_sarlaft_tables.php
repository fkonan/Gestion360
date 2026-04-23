<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::table('sarlaft_registros_lista', function (Blueprint $table) {
            $table->enum('novedad', ['ingreso', 'salida', 'actualizado', 'sin_cambio'])
                ->default('ingreso')
                ->after('estado');

            $table->foreignId('sincronizacion_log_id')
                ->nullable()
                ->after('novedad')
                ->constrained('sarlaft_sincronizacion_logs')
                ->nullOnDelete();

            $table->index(['novedad', 'sincronizacion_log_id'], 'idx_novedad_sync');
        });

        Schema::table('sarlaft_sincronizacion_logs', function (Blueprint $table) {
            $table->integer('registros_eliminados')->default(0)->after('registros_actualizados');
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_registros_lista', function (Blueprint $table) {
            $table->dropIndex('idx_novedad_sync');
            $table->dropForeign(['sincronizacion_log_id']);
            $table->dropColumn(['novedad', 'sincronizacion_log_id']);
        });

        Schema::table('sarlaft_sincronizacion_logs', function (Blueprint $table) {
            $table->dropColumn('registros_eliminados');
        });
    }
};
