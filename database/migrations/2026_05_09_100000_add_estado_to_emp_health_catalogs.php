<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORACLE_CONNECTION = 'oracle-360';
    private const MYSQL_HUMANA_CONNECTION = 'mysql-gestion-humana';

    public function up(): void
    {
        $this->addEstadoColumn('EMP_EPS');
        $this->addEstadoColumn('EMP_ARL');
        $this->addEstadoColumn('EMP_DIAGNOSTICOS');
        $this->addEstadoColumn('EMP_CAUSAS_INCAPACIDAD');

        $this->populateEmpEpsEstado();
        $this->populateEmpArlEstado();
        $this->populateDefaultEstado('EMP_DIAGNOSTICOS');
        $this->populateDefaultEstado('EMP_CAUSAS_INCAPACIDAD');
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function addEstadoColumn(string $table): void
    {
        $schema = Schema::connection(self::ORACLE_CONNECTION);

        if (! $schema->hasTable($table) || $schema->hasColumn($table, 'estado')) {
            return;
        }

        $schema->table($table, function (Blueprint $table) {
            $table->string('estado', 20)->nullable();
        });
    }

    private function populateEmpEpsEstado(): void
    {
        $rows = DB::connection(self::MYSQL_HUMANA_CONNECTION)
            ->table('eps')
            ->select(['EPSNombre', 'EPSEstado'])
            ->get();

        foreach ($rows as $row) {
            $nombre = trim((string) ($row->EPSNombre ?? ''));
            if ($nombre === '') {
                continue;
            }

            DB::connection(self::ORACLE_CONNECTION)
                ->table('EMP_EPS')
                ->whereRaw('UPPER(TRIM(EPSNombre)) = ?', [mb_strtoupper($nombre, 'UTF-8')])
                ->update([
                    'estado' => $this->mapEstado($row->EPSEstado ?? null),
                ]);
        }

        $this->populateDefaultEstado('EMP_EPS');
    }

    private function populateEmpArlEstado(): void
    {
        $rows = DB::connection(self::MYSQL_HUMANA_CONNECTION)
            ->table('arl')
            ->select(['ARLNombre', 'ARLEstado'])
            ->get();

        foreach ($rows as $row) {
            $nombre = trim((string) ($row->ARLNombre ?? ''));
            if ($nombre === '') {
                continue;
            }

            DB::connection(self::ORACLE_CONNECTION)
                ->table('EMP_ARL')
                ->whereRaw('UPPER(TRIM(ARLNombre)) = ?', [mb_strtoupper($nombre, 'UTF-8')])
                ->update([
                    'estado' => $this->mapEstado($row->ARLEstado ?? null),
                ]);
        }

        $this->populateDefaultEstado('EMP_ARL');
    }

    private function populateDefaultEstado(string $table): void
    {
        DB::connection(self::ORACLE_CONNECTION)
            ->table($table)
            ->whereNull('estado')
            ->update(['estado' => 'ACTIVO']);
    }

    private function mapEstado(mixed $value): string
    {
        $normalized = mb_strtoupper(trim((string) $value), 'UTF-8');

        if ($normalized === '' || in_array($normalized, ['1', 'ACTIVO', 'A', 'SI', 'S', 'TRUE'], true)) {
            return 'ACTIVO';
        }

        if (in_array($normalized, ['0', 'INACTIVO', 'I', 'NO', 'N', 'FALSE'], true)) {
            return 'INACTIVO';
        }

        return 'ACTIVO';
    }
};
