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
        Schema::table('sarlaft_alertas', function (Blueprint $table): void {
            // Decision de servicio tomada por el oficial: permitido | bloqueado.
            // null = aun sin decision explicita (bloqueado por defecto operativo).
            $table->enum('decision_servicio', ['permitido', 'bloqueado'])
                ->nullable()
                ->after('estado');
            $table->timestamp('decision_at')->nullable()->after('decision_servicio');
        });
    }

    public function down(): void
    {
        Schema::table('sarlaft_alertas', function (Blueprint $table): void {
            $table->dropColumn(['decision_servicio', 'decision_at']);
        });
    }
};
