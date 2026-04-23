<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_lista_negra_interna', function (Blueprint $table): void {
            $table->json('evidencia_inclusion')->nullable()->after('motivo');
            $table->text('motivo_retiro')->nullable()->after('evidencia_inclusion');
            $table->json('evidencia_retiro')->nullable()->after('motivo_retiro');
            $table->unsignedBigInteger('retirado_por')->nullable()->after('creado_por')->index();
            $table->timestamp('retirado_at')->nullable()->after('retirado_por');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_lista_negra_interna', function (Blueprint $table): void {
            $table->dropColumn([
                'evidencia_inclusion',
                'motivo_retiro',
                'evidencia_retiro',
                'retirado_por',
                'retirado_at',
            ]);
        });
    }
};
