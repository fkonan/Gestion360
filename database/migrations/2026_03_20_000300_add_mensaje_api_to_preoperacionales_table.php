<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql-gestion-pasajes');

        if (! $schema->hasTable('preoperacionales')) {
            return;
        }

        if ($schema->hasColumn('preoperacionales', 'mensaje_api')) {
            return;
        }

        $schema->table('preoperacionales', function (Blueprint $table) {
            $table->text('mensaje_api')->nullable()->after('observacion');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql-gestion-pasajes');

        if (! $schema->hasTable('preoperacionales')) {
            return;
        }

        if (! $schema->hasColumn('preoperacionales', 'mensaje_api')) {
            return;
        }

        $schema->table('preoperacionales', function (Blueprint $table) {
            $table->dropColumn('mensaje_api');
        });
    }
};
