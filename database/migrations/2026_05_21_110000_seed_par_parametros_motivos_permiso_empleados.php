<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';
    private const AREA_ID = 'EMPLEADOS-MOTIVOS-PERMISO';
    private const TIPO_ID = 'TEXTO';
    private const PARAMETRO_ID_PREFIX = 'EMPL-MOTI-PERM';

    private const MOTIVOS = [
        ['codigo_largo' => 'REUNION_ESCOLAR', 'codigo_corto' => 'REUN_ESC', 'descripcion' => 'REUNION ESCOLAR'],
        ['codigo_largo' => 'CITA_MEDICA_FAMILIARES', 'codigo_corto' => 'CITA_FAM', 'descripcion' => 'CITA MEDICA FAMILIARES'],
        ['codigo_largo' => 'ESTUDIO', 'codigo_corto' => 'ESTUDIO', 'descripcion' => 'ESTUDIO'],
        ['codigo_largo' => 'ACTIVIDAD_LABORAL_EXTERNA', 'codigo_corto' => 'ACT_EXT', 'descripcion' => 'ACTIVIDAD LABORAL EXTERNA'],
        ['codigo_largo' => 'MEDICINA_GENERAL', 'codigo_corto' => 'MED_GEN', 'descripcion' => 'MEDICINA GENERAL'],
        ['codigo_largo' => 'MEDICINA_ESPECIALIZADA', 'codigo_corto' => 'MED_ESP', 'descripcion' => 'MEDICINA ESPECIALIZADA'],
        ['codigo_largo' => 'TERAPIAS', 'codigo_corto' => 'TERAPIAS', 'descripcion' => 'TERAPIAS'],
        ['codigo_largo' => 'ODONTOLOGIA', 'codigo_corto' => 'ODONTO', 'descripcion' => 'ODONTOLOGIA'],
        ['codigo_largo' => 'URGENCIA_O_CITA_PRIORITARIA', 'codigo_corto' => 'URG_CITA', 'descripcion' => 'URGENCIAS O CITA PRIORITARIA'],
        ['codigo_largo' => 'ACCIDENTE_DE_TRABAJO', 'codigo_corto' => 'ACC_TRAB', 'descripcion' => 'ACCIDENTE DE TRABAJO'],
        ['codigo_largo' => 'EXAMENES', 'codigo_corto' => 'EXAMENES', 'descripcion' => 'EXAMENES'],
        ['codigo_largo' => 'OTROS', 'codigo_corto' => 'OTROS', 'descripcion' => 'OTROS'],
    ];

    public function up(): void
    {
        $connection = DB::connection(self::CONNECTION);

        $areaExiste = $connection
            ->table('PAR_PARAMETROS_AREAS')
            ->where('area_id', self::AREA_ID)
            ->exists();

        if (! $areaExiste) {
            throw new \RuntimeException('No existe el AREA_ID '.self::AREA_ID.' en PAR_PARAMETROS_AREAS.');
        }

        $orden = 1;
        foreach (self::MOTIVOS as $item) {
            $codigoLargo = strtoupper(trim((string) ($item['codigo_largo'] ?? '')));
            $codigoCorto = strtoupper(trim((string) ($item['codigo_corto'] ?? '')));
            $descripcion = trim((string) ($item['descripcion'] ?? ''));

            if ($codigoLargo === '' || $codigoCorto === '' || $descripcion === '') {
                $orden++;
                continue;
            }

            $descripcionLarga = 'Motivo permiso de salida empleado: '.$descripcion;

            $row = $connection
                ->table('PAR_PARAMETROS')
                ->where('area_id', self::AREA_ID)
                ->where(function ($query) use ($codigoLargo, $codigoCorto) {
                    $query->whereRaw('UPPER(NVL(parametro_valor2, \'\')) = ?', [$codigoLargo])
                        ->orWhereRaw('UPPER(NVL(parametro_valorcorto, \'\')) = ?', [$codigoCorto]);
                })
                ->first(['parametro_id']);

            if ($row) {
                $connection
                    ->table('PAR_PARAMETROS')
                    ->where('parametro_id', (string) $row->parametro_id)
                    ->update([
                        'parametro_descripcion' => $descripcionLarga,
                        'parametro_orden' => $orden,
                        'tipo_id' => self::TIPO_ID,
                        'parametro_valor' => $descripcion,
                        'parametro_valorcorto' => $codigoCorto,
                        'parametro_valor2' => $codigoLargo,
                        'activo' => 1,
                        'parametro_fecha_modificacion' => now(),
                    ]);
            } else {
                $parametroId = $this->resolverParametroIdDisponible($connection, $orden);

                $connection->table('PAR_PARAMETROS')->insert([
                    'parametro_id' => $parametroId,
                    'parametro_descripcion' => $descripcionLarga,
                    'area_id' => self::AREA_ID,
                    'parametro_orden' => $orden,
                    'tipo_id' => self::TIPO_ID,
                    'parametro_valor' => $descripcion,
                    'parametro_valorcorto' => $codigoCorto,
                    'parametro_valor2' => $codigoLargo,
                    'activo' => 1,
                    'parametro_fecha_modificacion' => now(),
                    'codigo_rndc' => null,
                    'codigo_dian' => null,
                ]);
            }

            $orden++;
        }

        cache()->forget('empleados_permisos_motivos_oracle_v1');
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible para evitar afectar catalogos globales.
    }

    private function resolverParametroIdDisponible($connection, int $orden): string
    {
        $base = self::PARAMETRO_ID_PREFIX.str_pad((string) $orden, 3, '0', STR_PAD_LEFT);
        $id = $base;
        $sufijo = 1;

        while (
            $connection
                ->table('PAR_PARAMETROS')
                ->where('parametro_id', $id)
                ->exists()
        ) {
            $id = $base.'_'.$sufijo;
            $sufijo++;
        }

        return $id;
    }
};
