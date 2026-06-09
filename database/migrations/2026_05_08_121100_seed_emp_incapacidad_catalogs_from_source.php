<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->seedEmpEps();
        $this->seedEmpArl();
        $this->seedEmpDiagnosticos();
        $this->seedEmpCausasIncapacidad();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function seedEmpEps(): void
    {
        $rows = DB::connection('mysql-gestion-humana')
            ->table('eps')
            ->select(['IdEPS', 'EPSNombre'])
            ->orderBy('IdEPS')
            ->get();

        foreach ($rows as $row) {
            DB::connection('oracle-360')
                ->table('EMP_EPS')
                ->updateOrInsert(
                    ['IdEPS' => (int) $row->IdEPS],
                    ['EPSNombre' => $this->truncate($row->EPSNombre, 255)]
                );
        }
    }

    private function seedEmpArl(): void
    {
        $rows = DB::connection('mysql-gestion-humana')
            ->table('arl')
            ->select(['IdARL', 'ARLNombre'])
            ->orderBy('IdARL')
            ->get();

        foreach ($rows as $row) {
            DB::connection('oracle-360')
                ->table('EMP_ARL')
                ->updateOrInsert(
                    ['IdARL' => (int) $row->IdARL],
                    ['ARLNombre' => $this->truncate($row->ARLNombre, 255)]
                );
        }
    }

    private function seedEmpDiagnosticos(): void
    {
        $rows = DB::connection('mysql-gestion-humana')
            ->table('Enfermedades')
            ->select(['IdEnfermedad', 'CodigoCie', 'DescCie'])
            ->orderBy('IdEnfermedad')
            ->get();

        foreach ($rows as $row) {
            DB::connection('oracle-360')
                ->table('EMP_DIAGNOSTICOS')
                ->updateOrInsert(
                    ['IdEnfermedad' => (int) $row->IdEnfermedad],
                    [
                        'CodigoCie' => $this->truncate($row->CodigoCie, 80),
                        'DescCie' => $this->truncate($row->DescCie, 1000),
                    ]
                );
        }
    }

    private function seedEmpCausasIncapacidad(): void
    {
        $rows = DB::connection('mysql-gestion-admin')
            ->table('_parametros')
            ->select(['IdParametro', 'ParNomGru', 'ParNom'])
            ->where('ParNomGru', 'CAUSA-INCAPACIDAD')
            ->orderBy('IdParametro')
            ->get();

        foreach ($rows as $row) {
            DB::connection('oracle-360')
                ->table('EMP_CAUSAS_INCAPACIDAD')
                ->updateOrInsert(
                    ['IdParametro' => (int) $row->IdParametro],
                    [
                        'ParNomGru' => $this->truncate($row->ParNomGru, 120),
                        'ParNom' => $this->truncate($row->ParNom, 255),
                    ]
                );
        }
    }

    private function truncate(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr(trim($value), 0, $limit);
    }
};
