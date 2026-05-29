<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table): void {
            if (Schema::connection('mysql-sarlaft')->hasColumn('sarlaft_alertas', 'consulta_id')) {
                $table->dropColumn('consulta_id');
            }
        });

        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_consultas_archivo');
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_consultas');

        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table): void {
            if (! $this->hasForeignKey('sarlaft_alertas', 'fk_alertas_intento')) {
                $table->foreign('intento_id', 'fk_alertas_intento')
                    ->references('id')
                    ->on('sarlaft_intentos_operacion')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->table('sarlaft_alertas', function (Blueprint $table): void {
            if ($this->hasForeignKey('sarlaft_alertas', 'fk_alertas_intento')) {
                $table->dropForeign('fk_alertas_intento');
            }
        });
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        $result = collect(Schema::connection('mysql-sarlaft')->getConnection()
            ->select('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY" AND CONSTRAINT_NAME = ?', [$table, $name]));

        return $result->isNotEmpty();
    }
};
