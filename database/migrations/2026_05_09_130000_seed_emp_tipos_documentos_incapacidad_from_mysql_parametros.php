<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const ORACLE_CONNECTION = 'oracle-360';
    private const MYSQL_CONNECTION = 'mysql-gestion-admin';

    public function up(): void
    {
        $tipoIncapacidadId = DB::connection(self::ORACLE_CONNECTION)
            ->table('EMP_NOVEDADES_TIPO')
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(codigo, '')) = ?", ['INCAPACIDAD'])
                    ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['INCAPACIDAD']);
            })
            ->value('id');

        if (! is_string($tipoIncapacidadId) || trim($tipoIncapacidadId) === '') {
            return;
        }

        $descripcionesExistentes = DB::connection(self::ORACLE_CONNECTION)
            ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
            ->where('id_tipo_novedad', trim($tipoIncapacidadId))
            ->pluck('descripcion')
            ->map(fn ($descripcion) => $this->normalizar((string) $descripcion))
            ->filter()
            ->all();

        $descripcionesExistentes = array_flip($descripcionesExistentes);

        $documentosMysql = DB::connection(self::MYSQL_CONNECTION)
            ->table('_parametros')
            ->whereIn('ParNomGru', [
                'DOCUMENTOS-INCAPACIDAD',
                'DOCUMENTOS-INCAPACIDAD-MP',
                'DOCUMENTOS-INCAPACIDAD-AT',
            ])
            ->orderBy('ParNomGru')
            ->orderBy('ParNom')
            ->get(['ParNomGru', 'ParNom']);

        foreach ($documentosMysql as $documento) {
            $descripcion = trim((string) ($documento->ParNom ?? ''));
            $grupo = trim((string) ($documento->ParNomGru ?? ''));
            $descripcionNormalizada = $this->normalizar($descripcion);

            if ($descripcion === '' || $grupo === '' || $descripcionNormalizada === '' || isset($descripcionesExistentes[$descripcionNormalizada])) {
                continue;
            }

            DB::connection(self::ORACLE_CONNECTION)
                ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
                ->insert([
                    'id' => (string) Str::uuid(),
                    'id_tipo_novedad' => trim($tipoIncapacidadId),
                    'codigo' => $grupo,
                    'descripcion' => $descripcion,
                    'activo' => 1,
                ]);

            $descripcionesExistentes[$descripcionNormalizada] = true;
        }
    }

    public function down(): void
    {
        // Sin rollback destructivo sobre catalogos ya poblados.
    }

    private function normalizar(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $sinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $sinAcentos = $sinAcentos !== false ? $sinAcentos : $texto;

        return mb_strtoupper(trim($sinAcentos), 'UTF-8');
    }
};
