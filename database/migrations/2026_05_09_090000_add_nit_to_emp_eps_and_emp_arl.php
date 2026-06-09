<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORACLE_CONNECTION = 'oracle-360';
    private const MYSQL_CONNECTION = 'mysql-gestion-humana';

    public function up(): void
    {
        $this->addColumns();
        $this->populateEmpEpsNit();
        $this->populateEmpArlNit();
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function addColumns(): void
    {
        $schema = Schema::connection(self::ORACLE_CONNECTION);

        if ($schema->hasTable('EMP_EPS') && ! $schema->hasColumn('EMP_EPS', 'EPSNit')) {
            $schema->table('EMP_EPS', function (Blueprint $table) {
                $table->string('EPSNit', 100)->nullable();
            });
        }

        if ($schema->hasTable('EMP_ARL') && ! $schema->hasColumn('EMP_ARL', 'ARLNit')) {
            $schema->table('EMP_ARL', function (Blueprint $table) {
                $table->string('ARLNit', 100)->nullable();
            });
        }
    }

    private function populateEmpEpsNit(): void
    {
        $nitColumn = $this->detectSourceColumn('eps', ['EPSNit', 'Nit', 'NIT', 'NitEPS']);
        if ($nitColumn === null) {
            return;
        }

        $rows = DB::connection(self::MYSQL_CONNECTION)
            ->table('eps')
            ->select(['EPSNombre', $nitColumn])
            ->get();

        foreach ($rows as $row) {
            $nombre = trim((string) ($row->EPSNombre ?? ''));
            if ($nombre === '') {
                continue;
            }

            DB::connection(self::ORACLE_CONNECTION)
                ->table('EMP_EPS')
                ->where('EPSNombre', $nombre)
                ->update([
                    'EPSNit' => $this->normalizeValue($row->{$nitColumn} ?? null, 100),
                ]);
        }
    }

    private function populateEmpArlNit(): void
    {
        $nitColumn = $this->detectSourceColumn('arl', ['ARLNit', 'Nit', 'NIT', 'NitARL']);
        if ($nitColumn === null) {
            return;
        }

        $rows = DB::connection(self::MYSQL_CONNECTION)
            ->table('arl')
            ->select(['ARLNombre', $nitColumn])
            ->get();

        foreach ($rows as $row) {
            $nombre = trim((string) ($row->ARLNombre ?? ''));
            if ($nombre === '') {
                continue;
            }

            DB::connection(self::ORACLE_CONNECTION)
                ->table('EMP_ARL')
                ->where('ARLNombre', $nombre)
                ->update([
                    'ARLNit' => $this->normalizeValue($row->{$nitColumn} ?? null, 100),
                ]);
        }
    }

    private function detectSourceColumn(string $table, array $candidates): ?string
    {
        $columns = Schema::connection(self::MYSQL_CONNECTION)->getColumnListing($table);

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
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
