<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table) {
            $table->boolean('escalada_automatica')->default(false)->after('decision_consumida_consulta_id');
            $table->timestamp('escalada_automatica_at')->nullable()->after('escalada_automatica');

            $table->index(
                ['escalada_automatica', 'escalada_automatica_at'],
                'idx_alerta_escalada_automatica'
            );
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table) {
            $table->dropIndex('idx_alerta_escalada_automatica');
            $table->dropColumn(['escalada_automatica', 'escalada_automatica_at']);
        });
    }
};
