<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);
        if (! $schema->hasTable('EMP_NOVEDADES_TIPO')) {
            return;
        }

        $schema->table('EMP_NOVEDADES_TIPO', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'id_bloqueo_logtrans')) {
                $table->unsignedInteger('id_bloqueo_logtrans')->nullable();
            }

            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'id_bloqueo_fics')) {
                $table->unsignedInteger('id_bloqueo_fics')->nullable();
            }
        });

        DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_TIPO')
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(tabla, '')) = ?", ['EMP_INCAPACIDADES'])
                    ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['INCAPACIDAD']);
            })
            ->update([
                'id_bloqueo_logtrans' => 45,
                'id_bloqueo_fics' => 5,
            ]);

        DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_TIPO')
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(tabla, '')) = ?", ['EMP_VACACIONES'])
                    ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['VACACION']);
            })
            ->update([
                'id_bloqueo_logtrans' => 53,
                'id_bloqueo_fics' => 1,
            ]);
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};

