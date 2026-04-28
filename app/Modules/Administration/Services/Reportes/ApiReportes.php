<?php

namespace App\Modules\Administration\Services\Reportes;

use App\Modules\Administration\Models\Reporteador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiReportes
{
    private const ORIGEN_FICS = 'FICS';

    private const ORIGEN_LOGTRANS = 'LOGTRANS';

    private const ORIGEN_GESTION_PASAJES = 'GESTION_PASAJES';

    public function obtenerReporte(array $params, int $timeout = 120)
    {
        unset($timeout);

        if (empty($params['idReporte'])) {
            $this->log('ApiReportes: Falta parametro obligatorio', ['param' => 'idReporte']);

            return null;
        }

        /** @var Reporteador|null $reporte */
        $reporte = Reporteador::query()
            ->select('id', 'sql_base', 'parametros', 'origen_db')
            ->find((int) $params['idReporte']);

        if (! $reporte) {
            $this->log('ApiReportes: Reporte no encontrado', ['idReporte' => $params['idReporte']]);

            return null;
        }

        $parametrosEsperados = $this->decodificarParametros($reporte->parametros, (int) $reporte->id);
        if ($parametrosEsperados === null) {
            return null;
        }

        $origen = strtoupper(trim((string) $reporte->origen_db));
        $sql = $this->normalizarSqlPorOrigen((string) $reporte->sql_base, $origen);
        $parametrosCliente = $this->normalizarParametrosEntrada($params);
        $bindingsAsociativos = [];
        $bindingsPosicionales = [];

        foreach ($parametrosEsperados as $paramDef) {
            if (! is_array($paramDef)) {
                continue;
            }

            $nombre = isset($paramDef['nombre']) ? (string) $paramDef['nombre'] : '';
            if ($nombre === '') {
                continue;
            }

            $tipo = isset($paramDef['tipo']) ? (string) $paramDef['tipo'] : null;
            $requerido = (bool) ($paramDef['requerido'] ?? false);
            $valor = array_key_exists($nombre, $parametrosCliente) ? $parametrosCliente[$nombre] : null;

            if ($requerido && $valor === null) {
                $this->log('ApiReportes: Falta parametro requerido para reporte local', [
                    'idReporte' => $reporte->id,
                    'param' => $nombre,
                ]);

                return null;
            }

            if ($valor !== null && ! $this->validarTipoParametro($tipo, $valor)) {
                $this->log('ApiReportes: Tipo de parametro invalido', [
                    'idReporte' => $reporte->id,
                    'param' => $nombre,
                    'tipo_esperado' => $tipo,
                ]);

                return null;
            }

            $valor = $this->normalizarValorPorOrigen($origen, $tipo, $valor);

            $marcador = $this->obtenerMarcadorPorOrigen($origen, $nombre);
            $sql = $this->reemplazarMarcadorEnSql($sql, $nombre, $marcador);
            $bindingsAsociativos[$nombre] = $valor;
            $bindingsPosicionales[] = $valor;
        }

        try {
            $rows = $this->ejecutarConsulta($origen, $sql, $bindingsAsociativos, $bindingsPosicionales);

            return array_map(static fn ($row) => (array) $row, $rows);
        } catch (\Throwable $e) {
            $this->log('ApiReportes: error al obtener reporte local', [
                'idReporte' => $reporte->id,
                'origen_db' => $origen,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtiene solo las primeras N filas para vista previa de reportes pesados.
     */
    public function obtenerReporteLimitado(array $params, int $limit, int $timeout = 120)
    {
        unset($timeout);

        $limit = max(1, $limit);
        $consulta = $this->prepararConsultaDesdeParametros($params);

        if (! $consulta) {
            return null;
        }

        try {
            $rows = $this->ejecutarConsultaLimitada(
                $consulta['origen'],
                $consulta['sql'],
                $consulta['bindingsAsociativos'],
                $consulta['bindingsPosicionales'],
                $limit
            );

            return array_map(static fn ($row) => (array) $row, $rows);
        } catch (\Throwable $e) {
            $this->log('ApiReportes: error al obtener reporte limitado local', [
                'idReporte' => $consulta['reporte']->id,
                'origen_db' => $consulta['origen'],
                'limit' => $limit,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Retorna un iterador de filas para exportaciones grandes sin cargar todo en memoria.
     */
    public function obtenerReporteCursor(array $params, int $timeout = 120)
    {
        unset($timeout);

        $consulta = $this->prepararConsultaDesdeParametros($params);
        if (! $consulta) {
            return null;
        }

        try {
            return $this->ejecutarConsultaCursor(
                $consulta['origen'],
                $consulta['sql'],
                $consulta['bindingsAsociativos'],
                $consulta['bindingsPosicionales']
            );
        } catch (\Throwable $e) {
            // Fallback a select normal si el driver no soporta cursor en crudo.
            $this->log('ApiReportes: cursor no disponible, se usa select tradicional', [
                'idReporte' => $consulta['reporte']->id,
                'origen_db' => $consulta['origen'],
                'error' => $e->getMessage(),
            ]);

            try {
                $rows = $this->ejecutarConsulta(
                    $consulta['origen'],
                    $consulta['sql'],
                    $consulta['bindingsAsociativos'],
                    $consulta['bindingsPosicionales']
                );

                return array_map(static fn ($row) => (array) $row, $rows);
            } catch (\Throwable $fallbackError) {
                $this->log('ApiReportes: error al obtener cursor de reporte local', [
                    'idReporte' => $consulta['reporte']->id,
                    'origen_db' => $consulta['origen'],
                    'error' => $fallbackError->getMessage(),
                ]);

                return null;
            }
        }
    }

    public function obtenerReportePaginado(array $params, int $limit, int $offset, int $timeout = 120)
    {
        unset($timeout);

        $limit = max(1, $limit);
        $offset = max(0, $offset);

        if (empty($params['idReporte'])) {
            $this->log('ApiReportes: Falta parametro obligatorio', ['param' => 'idReporte']);

            return null;
        }

        /** @var Reporteador|null $reporte */
        $reporte = Reporteador::query()
            ->select('id', 'sql_base', 'parametros', 'origen_db')
            ->find((int) $params['idReporte']);

        if (! $reporte) {
            $this->log('ApiReportes: Reporte no encontrado', ['idReporte' => $params['idReporte']]);

            return null;
        }

        $parametrosEsperados = $this->decodificarParametros($reporte->parametros, (int) $reporte->id);
        if ($parametrosEsperados === null) {
            return null;
        }

        $origen = strtoupper(trim((string) $reporte->origen_db));
        $sql = $this->normalizarSqlPorOrigen((string) $reporte->sql_base, $origen);
        $bindingsAsociativos = [];
        $bindingsPosicionales = [];
        $parametrosCliente = $this->normalizarParametrosEntrada($params);

        foreach ($parametrosEsperados as $paramDef) {
            if (! is_array($paramDef)) {
                continue;
            }

            $nombre = isset($paramDef['nombre']) ? (string) $paramDef['nombre'] : '';
            if ($nombre === '') {
                continue;
            }

            $tipo = isset($paramDef['tipo']) ? (string) $paramDef['tipo'] : null;
            $requerido = (bool) ($paramDef['requerido'] ?? false);
            $valor = array_key_exists($nombre, $parametrosCliente) ? $parametrosCliente[$nombre] : null;

            if ($requerido && $valor === null) {
                $this->log('ApiReportes: Falta parametro requerido para reporte local', [
                    'idReporte' => $reporte->id,
                    'param' => $nombre,
                ]);

                return null;
            }

            if ($valor !== null && ! $this->validarTipoParametro($tipo, $valor)) {
                $this->log('ApiReportes: Tipo de parametro invalido', [
                    'idReporte' => $reporte->id,
                    'param' => $nombre,
                    'tipo_esperado' => $tipo,
                ]);

                return null;
            }

            $valor = $this->normalizarValorPorOrigen($origen, $tipo, $valor);
            $marcador = $this->obtenerMarcadorPorOrigen($origen, $nombre);

            $sql = $this->reemplazarMarcadorEnSql($sql, $nombre, $marcador);
            $bindingsAsociativos[$nombre] = $valor;
            $bindingsPosicionales[] = $valor;
        }

        try {
            return $this->ejecutarConsultaPaginada(
                $origen,
                $sql,
                $bindingsAsociativos,
                $bindingsPosicionales,
                $limit,
                $offset
            );
        } catch (\Throwable $e) {
            $this->log('ApiReportes: error al obtener reporte paginado local', [
                'idReporte' => $reporte->id,
                'origen_db' => $origen,
                'limit' => $limit,
                'offset' => $offset,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function decodificarParametros(?string $parametrosRaw, int $idReporte): ?array
    {
        if ($parametrosRaw === null || trim($parametrosRaw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($parametrosRaw, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException $e) {
            $this->log('ApiReportes: Error al interpretar los parametros del reporte', [
                'idReporte' => $idReporte,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function normalizarParametrosEntrada(array $params): array
    {
        $normalizados = [];

        foreach ($params as $key => $value) {
            if (! is_string($key) || $key === 'idReporte') {
                continue;
            }

            $nombre = str_starts_with($key, 'param')
                ? $key
                : 'param'.ucfirst($key);

            $normalizados[$nombre] = $value;
        }

        return $normalizados;
    }

    protected function validarTipoParametro(?string $tipo, $valor): bool
    {
        if ($tipo === null || $tipo === '') {
            return true;
        }

        return match (strtolower(trim($tipo))) {
            'string' => is_string($valor),
            'number' => is_numeric($valor),
            'date' => is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1,
            default => false,
        };
    }

    protected function obtenerMarcadorPorOrigen(string $origen, string $nombre): string
    {
        return $origen === self::ORIGEN_LOGTRANS ? ':'.$nombre : '@'.$nombre;
    }

    protected function normalizarValorPorOrigen(string $origen, ?string $tipo, $valor)
    {
        if ($valor === null) {
            return null;
        }

        // SQL Server puede fallar con 'YYYY-MM-DD' segun DATEFORMAT de sesion.
        if ($origen === self::ORIGEN_FICS && strtolower((string) $tipo) === 'date' && is_string($valor)) {
            return str_replace('-', '', $valor); // YYYYMMDD (formato no ambiguo para SQL Server)
        }

        return $valor;
    }

    protected function normalizarSqlPorOrigen(string $sql, string $origen): string
    {
        // Normaliza comillas tipograficas copiadas desde Word/Excel/correos.
        $sql = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", '´', '`'],
            ["'", "'", '"', '"', "'", "'"],
            $sql
        );

        if ($origen === self::ORIGEN_LOGTRANS) {
            // Corrige typo frecuente: TO_DATE(:param,YYYY-MM-DD') -> TO_DATE(:param,'YYYY-MM-DD')
            $sql = preg_replace(
                "/TO_DATE\\(\\s*(:[A-Za-z0-9_]+)\\s*,\\s*([A-Za-z0-9_:\\/-]+)'\\s*\\)/i",
                "TO_DATE($1,'$2')",
                $sql
            ) ?? $sql;
        }

        return $sql;
    }

    protected function reemplazarMarcadorEnSql(string $sql, string $nombre, string $marcador): string
    {
        $sql = str_replace("'{$nombre}'", $marcador, $sql);
        $sql = str_replace("\"{$nombre}\"", $marcador, $sql);

        return $sql;
    }

    protected function ejecutarConsulta(
        string $origen,
        string $sql,
        array $bindingsAsociativos,
        array $bindingsPosicionales
    ): array {
        $conexion = $this->obtenerConexionPorOrigen($origen);

        if ($origen === self::ORIGEN_FICS) {
            [$sqlSqlSrv, $bindingsSqlSrv] = $this->convertirMarcadoresNombradosAPosicionales($sql, $bindingsAsociativos);

            return DB::connection($conexion)->select($sqlSqlSrv, $bindingsSqlSrv);
        }

        if ($origen === self::ORIGEN_LOGTRANS) {
            return DB::connection($conexion)->select($sql, $bindingsAsociativos);
        }

        if ($origen === self::ORIGEN_GESTION_PASAJES) {
            $sqlPasajes = $this->convertirMarcadoresArrobaAPdo($sql, array_keys($bindingsAsociativos));

            return DB::connection($conexion)->select($sqlPasajes, $bindingsAsociativos);
        }

        if (str_contains($sql, '?')) {
            return DB::connection($conexion)->select($sql, $bindingsPosicionales);
        }

        $sqlDefault = $this->convertirMarcadoresArrobaAPdo($sql, array_keys($bindingsAsociativos));

        return DB::connection($conexion)->select($sqlDefault, $bindingsAsociativos);
    }

    protected function ejecutarConsultaCursor(
        string $origen,
        string $sql,
        array $bindingsAsociativos,
        array $bindingsPosicionales
    ) {
        $conexion = $this->obtenerConexionPorOrigen($origen);

        if ($origen === self::ORIGEN_FICS) {
            [$sqlSqlSrv, $bindingsSqlSrv] = $this->convertirMarcadoresNombradosAPosicionales($sql, $bindingsAsociativos);

            return DB::connection($conexion)->cursor($sqlSqlSrv, $bindingsSqlSrv);
        }

        if ($origen === self::ORIGEN_LOGTRANS) {
            return DB::connection($conexion)->cursor($sql, $bindingsAsociativos);
        }

        if ($origen === self::ORIGEN_GESTION_PASAJES) {
            $sqlPasajes = $this->convertirMarcadoresArrobaAPdo($sql, array_keys($bindingsAsociativos));

            return DB::connection($conexion)->cursor($sqlPasajes, $bindingsAsociativos);
        }

        if (str_contains($sql, '?')) {
            return DB::connection($conexion)->cursor($sql, $bindingsPosicionales);
        }

        $sqlDefault = $this->convertirMarcadoresArrobaAPdo($sql, array_keys($bindingsAsociativos));

        return DB::connection($conexion)->cursor($sqlDefault, $bindingsAsociativos);
    }

    protected function ejecutarConsultaLimitada(
        string $origen,
        string $sql,
        array $bindingsAsociativos,
        array $bindingsPosicionales,
        int $limit
    ): array {
        $conexion = $this->obtenerConexionPorOrigen($origen);
        $limit = max(1, (int) $limit);

        if ($origen === self::ORIGEN_FICS) {
            [$sqlSqlSrv, $bindingsSqlSrv] = $this->convertirMarcadoresNombradosAPosicionales($sql, $bindingsAsociativos);
            $sqlLimit = "SELECT TOP {$limit} * FROM ({$sqlSqlSrv}) T_LIMIT";

            return DB::connection($conexion)->select($sqlLimit, $bindingsSqlSrv);
        }

        if ($origen === self::ORIGEN_LOGTRANS) {
            $sqlLimit = "SELECT * FROM ({$sql}) T_LIMIT WHERE ROWNUM <= :maxRows";

            return DB::connection($conexion)->select($sqlLimit, array_merge($bindingsAsociativos, [
                'maxRows' => $limit,
            ]));
        }

        if ($origen === self::ORIGEN_GESTION_PASAJES) {
            $sqlMotor = $this->convertirMarcadoresArrobaAPdo($sql, array_keys($bindingsAsociativos));
            $sqlLimit = "SELECT * FROM ({$sqlMotor}) T_LIMIT LIMIT :limitRows";

            return DB::connection($conexion)->select($sqlLimit, array_merge($bindingsAsociativos, [
                'limitRows' => $limit,
            ]));
        }

        if (str_contains($sql, '?')) {
            $sqlLimit = "SELECT * FROM ({$sql}) T_LIMIT LIMIT ?";

            return DB::connection($conexion)->select($sqlLimit, array_merge($bindingsPosicionales, [$limit]));
        }

        $sqlDefault = $this->convertirMarcadoresArrobaAPdo($sql, array_keys($bindingsAsociativos));
        $sqlLimit = "SELECT * FROM ({$sqlDefault}) T_LIMIT LIMIT :limitRows";

        return DB::connection($conexion)->select($sqlLimit, array_merge($bindingsAsociativos, [
            'limitRows' => $limit,
        ]));
    }

    protected function prepararConsultaDesdeParametros(array $params): ?array
    {
        if (empty($params['idReporte'])) {
            $this->log('ApiReportes: Falta parametro obligatorio', ['param' => 'idReporte']);

            return null;
        }

        /** @var Reporteador|null $reporte */
        $reporte = Reporteador::query()
            ->select('id', 'sql_base', 'parametros', 'origen_db')
            ->find((int) $params['idReporte']);

        if (! $reporte) {
            $this->log('ApiReportes: Reporte no encontrado', ['idReporte' => $params['idReporte']]);

            return null;
        }

        $parametrosEsperados = $this->decodificarParametros($reporte->parametros, (int) $reporte->id);
        if ($parametrosEsperados === null) {
            return null;
        }

        $origen = strtoupper(trim((string) $reporte->origen_db));
        $sql = $this->normalizarSqlPorOrigen((string) $reporte->sql_base, $origen);
        $parametrosCliente = $this->normalizarParametrosEntrada($params);
        $bindingsAsociativos = [];
        $bindingsPosicionales = [];

        foreach ($parametrosEsperados as $paramDef) {
            if (! is_array($paramDef)) {
                continue;
            }

            $nombre = isset($paramDef['nombre']) ? (string) $paramDef['nombre'] : '';
            if ($nombre === '') {
                continue;
            }

            $tipo = isset($paramDef['tipo']) ? (string) $paramDef['tipo'] : null;
            $requerido = (bool) ($paramDef['requerido'] ?? false);
            $valor = array_key_exists($nombre, $parametrosCliente) ? $parametrosCliente[$nombre] : null;

            if ($requerido && $valor === null) {
                $this->log('ApiReportes: Falta parametro requerido para reporte local', [
                    'idReporte' => $reporte->id,
                    'param' => $nombre,
                ]);

                return null;
            }

            if ($valor !== null && ! $this->validarTipoParametro($tipo, $valor)) {
                $this->log('ApiReportes: Tipo de parametro invalido', [
                    'idReporte' => $reporte->id,
                    'param' => $nombre,
                    'tipo_esperado' => $tipo,
                ]);

                return null;
            }

            $valor = $this->normalizarValorPorOrigen($origen, $tipo, $valor);
            $marcador = $this->obtenerMarcadorPorOrigen($origen, $nombre);
            $sql = $this->reemplazarMarcadorEnSql($sql, $nombre, $marcador);
            $bindingsAsociativos[$nombre] = $valor;
            $bindingsPosicionales[] = $valor;
        }

        return [
            'reporte' => $reporte,
            'origen' => $origen,
            'sql' => $sql,
            'bindingsAsociativos' => $bindingsAsociativos,
            'bindingsPosicionales' => $bindingsPosicionales,
        ];
    }

    protected function ejecutarConsultaPaginada(
        string $origen,
        string $sql,
        array $bindingsAsociativos,
        array $bindingsPosicionales,
        int $limit,
        int $offset
    ): array {
        $conexion = $this->obtenerConexionPorOrigen($origen);
        $sqlBase = $this->limpiarSqlParaSubconsulta($sql);

        if ($origen === self::ORIGEN_FICS) {
            [$sqlPosicional, $bindingsPosicionalesSqlSrv] = $this->convertirMarcadoresNombradosAPosicionales($sqlBase, $bindingsAsociativos);

            $sqlTotal = "SELECT COUNT(1) AS TOTAL FROM ({$sqlPosicional}) T_COUNT";
            $totalRow = DB::connection($conexion)->selectOne($sqlTotal, $bindingsPosicionalesSqlSrv);

            $sqlPage = "SELECT * FROM ({$sqlPosicional}) T_PAGE ORDER BY (SELECT NULL) OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $rows = DB::connection($conexion)->select(
                $sqlPage,
                array_merge($bindingsPosicionalesSqlSrv, [$offset, $limit])
            );

            return [
                'total' => $this->extraerTotal($totalRow),
                'rows' => array_map(static fn ($row) => (array) $row, $rows),
            ];
        }

        if ($origen === self::ORIGEN_LOGTRANS) {
            $sqlTotal = "SELECT COUNT(1) AS TOTAL FROM ({$sqlBase}) T_COUNT";
            $totalRow = DB::connection($conexion)->selectOne($sqlTotal, $bindingsAsociativos);

            $sqlPage = "SELECT * FROM (
                SELECT T_PAGE.*, ROWNUM AS RN
                FROM ({$sqlBase}) T_PAGE
                WHERE ROWNUM <= :__max_row
            ) WHERE RN > :__offset_row";

            $rows = DB::connection($conexion)->select($sqlPage, array_merge($bindingsAsociativos, [
                '__max_row' => $offset + $limit,
                '__offset_row' => $offset,
            ]));

            return [
                'total' => $this->extraerTotal($totalRow),
                'rows' => array_map(static fn ($row) => (array) $row, $rows),
            ];
        }

        $esMysqlPasajes = $origen === self::ORIGEN_GESTION_PASAJES;
        $sqlMotor = $esMysqlPasajes
            ? $this->convertirMarcadoresArrobaAPdo($sqlBase, array_keys($bindingsAsociativos))
            : $sqlBase;

        if (! $esMysqlPasajes && ! str_contains($sqlMotor, '?')) {
            $sqlMotor = $this->convertirMarcadoresArrobaAPdo($sqlMotor, array_keys($bindingsAsociativos));
        }

        if (str_contains($sqlMotor, '?')) {
            $sqlTotal = "SELECT COUNT(1) AS TOTAL FROM ({$sqlMotor}) T_COUNT";
            $totalRow = DB::connection($conexion)->selectOne($sqlTotal, $bindingsPosicionales);

            $sqlPage = "SELECT * FROM ({$sqlMotor}) T_PAGE LIMIT ? OFFSET ?";
            $rows = DB::connection($conexion)->select(
                $sqlPage,
                array_merge($bindingsPosicionales, [$limit, $offset])
            );
        } else {
            $sqlTotal = "SELECT COUNT(1) AS TOTAL FROM ({$sqlMotor}) T_COUNT";
            $totalRow = DB::connection($conexion)->selectOne($sqlTotal, $bindingsAsociativos);

            $sqlPage = "SELECT * FROM ({$sqlMotor}) T_PAGE LIMIT :__limit_rows OFFSET :__offset_rows";
            $rows = DB::connection($conexion)->select($sqlPage, array_merge($bindingsAsociativos, [
                '__limit_rows' => $limit,
                '__offset_rows' => $offset,
            ]));
        }

        return [
            'total' => $this->extraerTotal($totalRow),
            'rows' => array_map(static fn ($row) => (array) $row, $rows),
        ];
    }

    protected function limpiarSqlParaSubconsulta(string $sql): string
    {
        return rtrim(trim($sql), ';');
    }

    protected function extraerTotal($row): int
    {
        if (! $row) {
            return 0;
        }

        $data = (array) $row;
        foreach (['TOTAL', 'total', 'Total', 'COUNT', 'count'] as $key) {
            if (array_key_exists($key, $data)) {
                return max(0, (int) $data[$key]);
            }
        }

        $valor = reset($data);

        return max(0, (int) $valor);
    }

    protected function obtenerConexionPorOrigen(string $origen): string
    {
        $mapeoPorOrigen = config('reporteador.connections.origenes', []);
        $conexionDefault = config('reporteador.connections.default', 'mysql-gestion-admin');

        return $mapeoPorOrigen[$origen] ?? $conexionDefault;
    }

    protected function convertirMarcadoresArrobaAPdo(string $sql, array $nombres): string
    {
        usort($nombres, static fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));

        foreach ($nombres as $nombre) {
            $nombre = (string) $nombre;
            $sql = preg_replace('/@'.preg_quote($nombre, '/').'\b/', ':'.$nombre, $sql) ?? $sql;
        }

        return $sql;
    }

    /**
     * SQL Server presenta errores con parametros nombrados repetidos.
     * Convertimos @param y :param a ? respetando orden de aparicion.
     */
    protected function convertirMarcadoresNombradosAPosicionales(string $sql, array $bindingsAsociativos): array
    {
        if (empty($bindingsAsociativos)) {
            return [$sql, []];
        }

        $nombres = array_keys($bindingsAsociativos);
        usort($nombres, static fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));

        $patternNombres = implode('|', array_map(
            static fn ($nombre) => preg_quote((string) $nombre, '/'),
            $nombres
        ));

        if ($patternNombres === '') {
            return [$sql, []];
        }

        $bindingsPosicionales = [];
        $sqlPosicional = preg_replace_callback(
            '/[@:]('.$patternNombres.')\b/',
            function (array $matches) use (&$bindingsPosicionales, $bindingsAsociativos) {
                $nombre = $matches[1];
                $bindingsPosicionales[] = $bindingsAsociativos[$nombre] ?? null;

                return '?';
            },
            $sql
        );

        return [$sqlPosicional ?? $sql, $bindingsPosicionales];
    }

    protected function log(string $mensaje, array $context = []): void
    {
        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/reportes/apiReportes.log'),
            'days' => 7,
        ])->info($mensaje, $context);
    }
}
