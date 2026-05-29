<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_politicas');
    }

    public function down(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_politicas', function (Blueprint $table): void {
            $table->id();
            $table->integer('sla_dias_alerta')->default(1);
            $table->json('auto_escalar_riesgos')->nullable();
            $table->string('auto_estado', 30)->default('en_revision');
            $table->boolean('auto_atender_lista_negra_interna')->default(true);
            $table->boolean('auto_crear_alerta_atendida')->default(true);
            $table->unsignedBigInteger('auto_user_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }
};
