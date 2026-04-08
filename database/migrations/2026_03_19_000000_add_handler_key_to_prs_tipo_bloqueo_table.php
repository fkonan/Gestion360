<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql-gestion-admin');

        if (! $schema->hasColumn('prs_tipo_bloqueo', 'handler_key')) {
            $schema->table('prs_tipo_bloqueo', function (Blueprint $table) {
                $table->string('handler_key', 60)->nullable()->after('permite_levantamiento_cop');
            });
        }

        DB::connection('mysql-gestion-admin')
            ->table('prs_tipo_bloqueo')
            ->where('id', 11)
            ->update(['handler_key' => 'descanso']);

        DB::connection('mysql-gestion-admin')
            ->table('prs_tipo_bloqueo')
            ->where('id', 35)
            ->update(['handler_key' => 'preoperacional_api']);
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql-gestion-admin');

        if ($schema->hasColumn('prs_tipo_bloqueo', 'handler_key')) {
            $schema->table('prs_tipo_bloqueo', function (Blueprint $table) {
                $table->dropColumn('handler_key');
            });
        }
    }
};
