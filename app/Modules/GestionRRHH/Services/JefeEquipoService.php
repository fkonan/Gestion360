<?php

namespace App\Modules\GestionRRHH\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JefeEquipoService
{
    public function obtenerEmpleadosDirectos(string $identificacionJefe): Collection
    {
        $identificacionJefe = trim($identificacionJefe);
        if ($identificacionJefe === '') {
            return collect();
        }

        $sql = <<<'SQL'
WITH jefe_cargos AS (
    SELECT DISTINCT
        p.id              AS jefe_pe_id,
        p.identificacion  AS doc_jefe,
        cc.ca_codigo      AS cargo_jefe,
        cc.ct_codigo      AS centro_costo_jefe
    FROM per_personas p
    JOIN per_empresapersonas ep ON ep.pe_id_pe = p.id
    JOIN per_cargoccostos cc ON cc.id = ep.cc_id
    JOIN per_centrocostos ct ON ct.codigo = cc.ct_codigo
    WHERE p.identificacion = ?
      AND ep.tp_id IN (1, 11)
      AND ep.activo = 1
      AND ep.estborrado = 0
      AND ep.fecfin IS NULL
      AND cc.activo = 1
      AND cc.estborrado = 0
      AND ct.estado = 1
      AND ct.estborrado = 0
)
SELECT DISTINCT
    pe.identificacion AS doc_empleado,
    TRIM(pe.pnombre || ' ' || NVL(pe.snombre, '')) || ' ' ||
    TRIM(pe.papellido || ' ' || NVL(pe.sapellido, '')) AS nombre_empleado,
    ca_act.descripcion AS cargo_empleado,
    cs_act.descripcion AS cargo_superior,
    ct_act.codigo AS codigo_centro_costo,
    ct_act.descripcion AS centro_costo
FROM jefe_cargos jc
JOIN per_empresapersonas ep_act
  ON ep_act.tp_id IN (1, 11)
 AND ep_act.activo = 1
 AND ep_act.estborrado = 0
 AND ep_act.fecfin IS NULL
JOIN per_cargoccostos cc_act
  ON cc_act.id = ep_act.cc_id
 AND cc_act.ct_codigo = jc.centro_costo_jefe
 AND cc_act.activo = 1
 AND cc_act.estborrado = 0
JOIN per_personas pe ON pe.id = ep_act.pe_id_pe
JOIN per_centrocostos ct_act
  ON ct_act.codigo = cc_act.ct_codigo
 AND ct_act.estado = 1
 AND ct_act.estborrado = 0
JOIN per_cargos ca_act
  ON ca_act.codigo = cc_act.ca_codigo
 AND ca_act.estborrado = 0
LEFT JOIN per_cargos cs_act ON cs_act.codigo = cc_act.id_cargosup
WHERE (
      cc_act.id_cargosup = jc.cargo_jefe
      OR (
          cc_act.id_cargosup IS NULL
          AND EXISTS (
              SELECT 1
              FROM per_empresapersonas ep_prev
              JOIN per_cargoccostos cc_prev ON cc_prev.id = ep_prev.cc_id
              JOIN per_centrocostos ct_prev ON ct_prev.codigo = cc_prev.ct_codigo
              WHERE ep_prev.pe_id_pe = ep_act.pe_id_pe
                AND ep_prev.tp_id IN (1, 11)
                AND ep_prev.estborrado = 0
                AND cc_prev.id_cargosup = jc.cargo_jefe
                AND cc_prev.ct_codigo = jc.centro_costo_jefe
                AND cc_prev.activo = 1
                AND cc_prev.estborrado = 0
                AND ct_prev.estado = 1
                AND ct_prev.estborrado = 0
          )
      )
  )
  AND pe.id <> jc.jefe_pe_id
ORDER BY nombre_empleado
SQL;

        $rows = DB::connection('oracle')->select($sql, [$identificacionJefe]);

        return collect($rows)->map(function ($row) {
            return [
                'doc_empleado' => trim((string) ($row->doc_empleado ?? '')),
                'nombre_empleado' => trim((string) ($row->nombre_empleado ?? '')),
                'cargo_empleado' => trim((string) ($row->cargo_empleado ?? '')),
                'cargo_superior' => trim((string) ($row->cargo_superior ?? '')),
                'codigo_centro_costo' => trim((string) ($row->codigo_centro_costo ?? '')),
                'centro_costo' => trim((string) ($row->centro_costo ?? '')),
            ];
        });
    }

    public function obtenerInformacionJefe(string $identificacionJefe): ?array
    {
        $identificacionJefe = trim($identificacionJefe);
        if ($identificacionJefe === '') {
            return null;
        }

        $sql = <<<'SQL'
SELECT *
FROM (
    SELECT
        p.identificacion AS doc_jefe,
        TRIM(p.pnombre || ' ' || NVL(p.snombre, '')) || ' ' ||
        TRIM(p.papellido || ' ' || NVL(p.sapellido, '')) AS nombre_jefe,
        ca.descripcion AS cargo_jefe,
        ct.codigo AS codigo_centro_costo,
        ct.descripcion AS centro_costo
    FROM per_personas p
    JOIN per_empresapersonas ep ON ep.pe_id_pe = p.id
    JOIN per_cargoccostos cc ON cc.id = ep.cc_id
    JOIN per_centrocostos ct ON ct.codigo = cc.ct_codigo
    JOIN per_cargos ca ON ca.codigo = cc.ca_codigo
    WHERE p.identificacion = ?
      AND ep.tp_id IN (1, 11)
      AND ep.activo = 1
      AND ep.estborrado = 0
      AND ep.fecfin IS NULL
      AND cc.activo = 1
      AND cc.estborrado = 0
      AND ct.estado = 1
      AND ct.estborrado = 0
      AND ca.estborrado = 0
    ORDER BY ep.id DESC
)
WHERE ROWNUM = 1
SQL;

        $row = DB::connection('oracle')->selectOne($sql, [$identificacionJefe]);
        if (! $row) {
            return null;
        }

        return [
            'identificacion' => trim((string) ($row->doc_jefe ?? '')),
            'nombre' => trim((string) ($row->nombre_jefe ?? '')),
            'cargo' => trim((string) ($row->cargo_jefe ?? '')),
            'codigo_centro_costo' => trim((string) ($row->codigo_centro_costo ?? '')),
            'centro_costo' => trim((string) ($row->centro_costo ?? '')),
        ];
    }

    public function obtenerJefesConPersonalACargo(): Collection
    {
        $sql = <<<'SQL'
SELECT DISTINCT
  p.identificacion AS identificacion,
  TRIM(p.pnombre || ' ' || NVL(p.snombre, '')) || ' ' ||
  TRIM(p.papellido || ' ' || NVL(p.sapellido, '')) AS nombre
FROM per_personas p
JOIN per_empresapersonas ep ON ep.pe_id_pe = p.id
JOIN per_cargoccostos cc ON cc.id = ep.cc_id
JOIN per_centrocostos ct ON ct.codigo = cc.ct_codigo
WHERE ep.tp_id IN (1, 11)
  AND ep.activo = 1
  AND ep.estborrado = 0
  AND ep.fecfin IS NULL
  AND cc.activo = 1
  AND cc.estborrado = 0
  AND ct.estado = 1
  AND ct.estborrado = 0
  AND EXISTS (
    SELECT 1
    FROM per_cargoccostos cce
    JOIN per_empresapersonas epe ON epe.cc_id = cce.id
    JOIN per_centrocostos cte ON cte.codigo = cce.ct_codigo
    WHERE cce.id_cargosup = cc.ca_codigo
      AND cce.ct_codigo = cc.ct_codigo
      AND cce.activo = 1
      AND cce.estborrado = 0
      AND epe.tp_id IN (1, 11)
      AND epe.activo = 1
      AND epe.estborrado = 0
      AND epe.fecfin IS NULL
      AND cte.estado = 1
      AND cte.estborrado = 0
  )
ORDER BY nombre
SQL;

        $rows = DB::connection('oracle')->select($sql);

        return collect($rows)->map(function ($row) {
            return [
                'identificacion' => trim((string) ($row->identificacion ?? '')),
                'nombre' => trim((string) ($row->nombre ?? '')),
            ];
        })->filter(function (array $item) {
            return $item['identificacion'] !== '';
        })->values();
    }

    public function obtenerJefeDirectoDeEmpleado(string $documentoEmpleado): ?array
    {
        $documentoEmpleado = trim($documentoEmpleado);
        if ($documentoEmpleado === '') {
            return null;
        }

        return Cache::remember('jefe_directo_empleado_v2_'.$documentoEmpleado, 300, function () use ($documentoEmpleado) {
            $sql = <<<'SQL'
SELECT *
FROM (
    WITH empleado_actual AS (
        SELECT *
        FROM (
            SELECT
                pe.id AS empleado_pe_id,
                pe.identificacion AS doc_empleado,
                cc_act.ct_codigo AS centro_costo_empleado,
                cc_act.id_cargosup AS cargo_superior_actual,
                ep_act.id AS ep_id_empleado
            FROM per_personas pe
            JOIN per_empresapersonas ep_act ON ep_act.pe_id_pe = pe.id
            JOIN per_cargoccostos cc_act ON cc_act.id = ep_act.cc_id
            JOIN per_centrocostos ct_act ON ct_act.codigo = cc_act.ct_codigo
            WHERE pe.identificacion = ?
              AND pe.estado = 'ACTIVO'
              AND pe.estborrado = 0
              AND ep_act.tp_id IN (1, 11)
              AND ep_act.activo = 1
              AND ep_act.estborrado = 0
              AND ep_act.fecfin IS NULL
              AND cc_act.activo = 1
              AND cc_act.estborrado = 0
              AND ct_act.estado = 1
              AND ct_act.estborrado = 0
            ORDER BY ep_act.id DESC
        )
        WHERE ROWNUM = 1
    ),
    jefe_candidatos AS (
        SELECT
            pj.id AS jefe_pe_id,
            pj.identificacion AS doc_jefe,
            TRIM(pj.pnombre || ' ' || NVL(pj.snombre, '')) || ' ' ||
            TRIM(pj.papellido || ' ' || NVL(pj.sapellido, '')) AS nombre_jefe,
            pj.dirweb AS correo_jefe,
            cc_jefe.ca_codigo AS cargo_jefe,
            cc_jefe.ct_codigo AS centro_costo_jefe,
            ep_jefe.id AS ep_id_jefe
        FROM per_personas pj
        JOIN per_empresapersonas ep_jefe ON ep_jefe.pe_id_pe = pj.id
        JOIN per_cargoccostos cc_jefe ON cc_jefe.id = ep_jefe.cc_id
        JOIN per_centrocostos ct_jefe ON ct_jefe.codigo = cc_jefe.ct_codigo
        WHERE pj.estado = 'ACTIVO'
          AND pj.estborrado = 0
          AND ep_jefe.tp_id IN (1, 11)
          AND ep_jefe.activo = 1
          AND ep_jefe.estborrado = 0
          AND ep_jefe.fecfin IS NULL
          AND cc_jefe.activo = 1
          AND cc_jefe.estborrado = 0
          AND ct_jefe.estado = 1
          AND ct_jefe.estborrado = 0
          AND EXISTS (
                SELECT 1
                FROM per_cargoccostos cce
                JOIN per_empresapersonas epe ON epe.cc_id = cce.id
                JOIN per_centrocostos cte ON cte.codigo = cce.ct_codigo
                WHERE cce.id_cargosup = cc_jefe.ca_codigo
                  AND cce.ct_codigo = cc_jefe.ct_codigo
                  AND cce.activo = 1
                  AND cce.estborrado = 0
                  AND epe.tp_id IN (1, 11)
                  AND epe.activo = 1
                  AND epe.estborrado = 0
                  AND epe.fecfin IS NULL
                  AND cte.estado = 1
                  AND cte.estborrado = 0
          )
    )
    SELECT
        jc.doc_jefe,
        jc.nombre_jefe,
        jc.correo_jefe,
        jc.ep_id_jefe
    FROM empleado_actual ea
    JOIN jefe_candidatos jc
      ON jc.centro_costo_jefe = ea.centro_costo_empleado
     AND jc.jefe_pe_id <> ea.empleado_pe_id
    WHERE (
            ea.cargo_superior_actual = jc.cargo_jefe
            OR (
                ea.cargo_superior_actual IS NULL
                AND EXISTS (
                    SELECT 1
                    FROM per_empresapersonas ep_prev
                    JOIN per_cargoccostos cc_prev ON cc_prev.id = ep_prev.cc_id
                    JOIN per_centrocostos ct_prev ON ct_prev.codigo = cc_prev.ct_codigo
                    WHERE ep_prev.pe_id_pe = ea.empleado_pe_id
                      AND ep_prev.tp_id IN (1, 11)
                      AND ep_prev.estborrado = 0
                      AND cc_prev.id_cargosup = jc.cargo_jefe
                      AND cc_prev.ct_codigo = jc.centro_costo_jefe
                      AND cc_prev.activo = 1
                      AND cc_prev.estborrado = 0
                      AND ct_prev.estado = 1
                      AND ct_prev.estborrado = 0
                )
            )
        )
    ORDER BY jc.ep_id_jefe DESC
)
WHERE ROWNUM = 1
SQL;

            $row = DB::connection('oracle')->selectOne($sql, [$documentoEmpleado]);
            if (! $row) {
                return null;
            }

            return [
                'identificacion' => trim((string) ($row->doc_jefe ?? '')),
                'nombre' => trim((string) ($row->nombre_jefe ?? '')),
                'correo' => trim((string) ($row->correo_jefe ?? '')),
            ];
        });
    }

    public function esJefeDirectoDeEmpleado(string $documentoJefe, string $documentoEmpleado): bool
    {
        $documentoJefe = trim($documentoJefe);
        $documentoEmpleado = trim($documentoEmpleado);
        if ($documentoJefe === '' || $documentoEmpleado === '') {
            return false;
        }

        $sql = <<<'SQL'
SELECT 1 AS aplica
FROM (
    WITH jefe_cargos AS (
        SELECT DISTINCT
            p.id              AS jefe_pe_id,
            p.identificacion  AS doc_jefe,
            cc.ca_codigo      AS cargo_jefe,
            cc.ct_codigo      AS centro_costo_jefe
        FROM per_personas p
        JOIN per_empresapersonas ep ON ep.pe_id_pe = p.id
        JOIN per_cargoccostos cc ON cc.id = ep.cc_id
        JOIN per_centrocostos ct ON ct.codigo = cc.ct_codigo
        WHERE p.identificacion = ?
          AND ep.tp_id IN (1, 11)
          AND ep.activo = 1
          AND ep.estborrado = 0
          AND ep.fecfin IS NULL
          AND cc.activo = 1
          AND cc.estborrado = 0
          AND ct.estado = 1
          AND ct.estborrado = 0
    )
    SELECT DISTINCT pe.identificacion AS doc_empleado
    FROM jefe_cargos jc
    JOIN per_empresapersonas ep_act
      ON ep_act.tp_id IN (1, 11)
     AND ep_act.activo = 1
     AND ep_act.estborrado = 0
     AND ep_act.fecfin IS NULL
    JOIN per_cargoccostos cc_act
      ON cc_act.id = ep_act.cc_id
     AND cc_act.ct_codigo = jc.centro_costo_jefe
     AND cc_act.activo = 1
     AND cc_act.estborrado = 0
    JOIN per_personas pe ON pe.id = ep_act.pe_id_pe
    JOIN per_centrocostos ct_act
      ON ct_act.codigo = cc_act.ct_codigo
     AND ct_act.estado = 1
     AND ct_act.estborrado = 0
    JOIN per_cargos ca_act
      ON ca_act.codigo = cc_act.ca_codigo
     AND ca_act.estborrado = 0
    WHERE (
          cc_act.id_cargosup = jc.cargo_jefe
          OR (
              cc_act.id_cargosup IS NULL
              AND EXISTS (
                  SELECT 1
                  FROM per_empresapersonas ep_prev
                  JOIN per_cargoccostos cc_prev ON cc_prev.id = ep_prev.cc_id
                  JOIN per_centrocostos ct_prev ON ct_prev.codigo = cc_prev.ct_codigo
                  WHERE ep_prev.pe_id_pe = ep_act.pe_id_pe
                    AND ep_prev.tp_id IN (1, 11)
                    AND ep_prev.estborrado = 0
                    AND cc_prev.id_cargosup = jc.cargo_jefe
                    AND cc_prev.ct_codigo = jc.centro_costo_jefe
                    AND cc_prev.activo = 1
                    AND cc_prev.estborrado = 0
                    AND ct_prev.estado = 1
                    AND ct_prev.estborrado = 0
              )
          )
      )
      AND pe.id <> jc.jefe_pe_id
) equipo
WHERE equipo.doc_empleado = ?
  AND ROWNUM = 1
SQL;

        $row = DB::connection('oracle')->selectOne($sql, [$documentoJefe, $documentoEmpleado]);

        return $row !== null;
    }
}
