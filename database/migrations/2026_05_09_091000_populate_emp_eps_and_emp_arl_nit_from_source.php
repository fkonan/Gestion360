<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ORACLE_CONNECTION = 'oracle-360';
    private const MYSQL_CONNECTION = 'mysql-gestion-humana';

    public function up(): void
    {
        $this->populateEmpEpsNit();
        $this->populateEmpArlNit();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function populateEmpEpsNit(): void
    {
        $rows = DB::connection(self::MYSQL_CONNECTION)
            ->table('eps')
            ->select(['EPSNombre', 'EPSNIT'])
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
                    'EPSNit' => $this->normalizeValue($row->EPSNIT ?? null, 100),
                ]);
        }
    }

    private function populateEmpArlNit(): void
    {
        $rows = DB::connection(self::MYSQL_CONNECTION)
            ->table('arl')
            ->select(['ARLNombre', 'ARLNIT'])
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
                    'ARLNit' => $this->normalizeValue($row->ARLNIT ?? null, 100),
                ]);
        }
    }

    private function normalizeValue(mixed $value, int $limit): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }
};
