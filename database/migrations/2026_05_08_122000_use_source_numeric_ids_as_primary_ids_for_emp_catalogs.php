<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $this->alignEmpEps();
        $this->alignEmpArl();
        $this->alignEmpDiagnosticos();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function alignEmpEps(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_EPS') || ! $schema->hasColumn('EMP_EPS', 'IdEPS')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("
            UPDATE EMP_EPS
            SET id = TO_CHAR(IdEPS)
            WHERE IdEPS IS NOT NULL
        ");

        DB::connection(self::CONNECTION)->statement("
            ALTER TABLE EMP_EPS
            MODIFY (id DEFAULT NULL)
        ");

        $this->dropIndexIfExists('emp_eps_ideps_idx');
        DB::connection(self::CONNECTION)->statement('ALTER TABLE EMP_EPS DROP COLUMN IdEPS');
    }

    private function alignEmpArl(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_ARL') || ! $schema->hasColumn('EMP_ARL', 'IdARL')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("
            UPDATE EMP_ARL
            SET id = TO_CHAR(IdARL)
            WHERE IdARL IS NOT NULL
        ");

        DB::connection(self::CONNECTION)->statement("
            ALTER TABLE EMP_ARL
            MODIFY (id DEFAULT NULL)
        ");

        $this->dropIndexIfExists('emp_arl_idarl_idx');
        DB::connection(self::CONNECTION)->statement('ALTER TABLE EMP_ARL DROP COLUMN IdARL');
    }

    private function alignEmpDiagnosticos(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_DIAGNOSTICOS') || ! $schema->hasColumn('EMP_DIAGNOSTICOS', 'IdEnfermedad')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement("
            UPDATE EMP_DIAGNOSTICOS
            SET id = TO_CHAR(IdEnfermedad)
            WHERE IdEnfermedad IS NOT NULL
        ");

        DB::connection(self::CONNECTION)->statement("
            ALTER TABLE EMP_DIAGNOSTICOS
            MODIFY (id DEFAULT NULL)
        ");

        $this->dropIndexIfExists('emp_diagnosticos_idenfermedad_idx');
        DB::connection(self::CONNECTION)->statement('ALTER TABLE EMP_DIAGNOSTICOS DROP COLUMN IdEnfermedad');
    }

    private function dropIndexIfExists(string $index): void
    {
        try {
            DB::connection(self::CONNECTION)->statement("DROP INDEX {$index}");
        } catch (\Throwable $exception) {
            // El indice puede no existir segun el estado previo del esquema.
        }
    }
};
