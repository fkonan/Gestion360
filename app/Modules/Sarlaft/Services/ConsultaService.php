<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Events\AlertaGenerada;
use App\Modules\Sarlaft\Events\ConsultaRealizada;
use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Models\RegistroLista;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsultaService
{
    /**
     * @var array<int, string>
     */
    private const NAME_STOPWORDS = ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y', 'E'];

    private const NAME_SEARCH_LIMIT = 50;

    private const NAME_MIN_TOKENS = 2;

    private const NAME_MIN_SCORE = 0.60;

    private const NAME_HIGH_RISK_SCORE = 0.95;

    private const NAME_HIGH_RISK_MIN_MATCHED_TOKENS = 3;

    private const NAME_HIGH_RISK_MIN_COVERAGE = 1.0;

    public function __construct(
        private readonly GestionAlertaService $gestionAlertaService,
        private readonly PoliticaSarlaftService $politicaSarlaftService,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function ejecutar(array $datos, string $ip, string $sistemaOrigen): Consulta
    {
        $politica = $this->politicaSarlaftService->obtener();
        $coincidencias = $this->buscarEnListas(
            $datos['tipo_documento'],
            $datos['numero_documento'],
            $datos['nombre'] ?? null,
        );

        $encontrado = count($coincidencias) > 0;
        $coincidenciaListaNegraInterna = $this->tieneCoincidenciaTipo($coincidencias, 'lista_negra_interna');
        $nivelRiesgoBase = $this->calcularNivelRiesgo($coincidencias);
        $prestaServicioBase = $nivelRiesgoBase !== 'alto';
        $nivelRiesgo = $nivelRiesgoBase;
        $prestaServicio = $prestaServicioBase;
        $tieneContextoRiesgo = $encontrado;

        $consulta = Consulta::create([
            'sistema_origen' => $sistemaOrigen,
            'tipo_documento' => $datos['tipo_documento'],
            'numero_documento' => $datos['numero_documento'],
            'nombre_consultado' => $datos['nombre'] ?? null,
            'encontrado' => $encontrado,
            'presta_servicio' => $prestaServicio,
            'nivel_riesgo' => $tieneContextoRiesgo ? $nivelRiesgo : 'ninguno',
            'coincidencias' => $encontrado ? $coincidencias : null,
            'contexto_operacion' => $this->construirContextoOperacion(
                prestaServicioBase: $prestaServicioBase,
                prestaServicioFinal: $prestaServicio,
                alertaId: null,
                origenAtencion: null,
                coincidenciaListaNegraInterna: $coincidenciaListaNegraInterna,
            ),
            'ip_origen' => $ip,
            'created_at' => now(),
        ]);

        ConsultaRealizada::dispatch($consulta);

        $debeCrearAlerta = $encontrado;
        $alertaId = null;
        $origenAtencion = null;

        if ($debeCrearAlerta) {
            $alerta = Alerta::create([
                'consulta_id' => $consulta->id,
                'tipo' => 'coincidencia_lista',
                'nivel_riesgo' => $nivelRiesgo,
                'estado' => 'pendiente',
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_documento'],
                'datos_persona' => [
                    'tipo_documento' => $datos['tipo_documento'],
                    'numero_documento' => $datos['numero_documento'],
                    'nombre' => $datos['nombre'] ?? null,
                ],
                'listas_coincidentes' => $coincidencias,
                'contexto_operacion' => $this->construirContextoOperacion(
                    prestaServicioBase: $prestaServicioBase,
                    prestaServicioFinal: $prestaServicio,
                    alertaId: null,
                    origenAtencion: 'manual',
                    coincidenciaListaNegraInterna: $coincidenciaListaNegraInterna,
                ),
            ]);

            $alertaId = (int) $alerta->id;
            $origenAtencion = 'manual';

            $debeAutoAtenderListaNegra = $coincidenciaListaNegraInterna
                && (bool) ($politica['auto_atender_lista_negra_interna'] ?? true)
                && (bool) ($politica['auto_crear_alerta_atendida'] ?? true);

            if ($debeAutoAtenderListaNegra) {
                $this->gestionAlertaService->atender(
                    alerta: $alerta,
                    datos: [
                        'estado' => 'atendida',
                        'notas' => '[AUTO] Autoatencion inmediata por lista negra interna.',
                    ],
                    userId: $this->resolverAutoUserId($politica),
                    esAutomatica: true,
                );

                $alerta->refresh();
                $contextoAlerta = is_array($alerta->contexto_operacion) ? $alerta->contexto_operacion : [];
                $contextoAlerta['origen_atencion'] = 'auto_lista_negra_interna';
                $alerta->update([
                    'contexto_operacion' => $contextoAlerta,
                ]);
                $origenAtencion = 'auto_lista_negra_interna';
            }

            AlertaGenerada::dispatch($alerta);
        }

        $consulta->update([
            'contexto_operacion' => $this->construirContextoOperacion(
                prestaServicioBase: $prestaServicioBase,
                prestaServicioFinal: $prestaServicio,
                alertaId: $alertaId,
                origenAtencion: $origenAtencion,
                coincidenciaListaNegraInterna: $coincidenciaListaNegraInterna,
            ),
        ]);
        $consulta->refresh();

        return $consulta;
    }

    /**
     * @param  array<int, array<string, mixed>>  $registros
     * @return array<int, Consulta>
     */
    public function ejecutarLote(array $registros, string $ip, string $sistemaOrigen): array
    {
        $resultados = [];

        foreach ($registros as $registro) {
            $resultados[] = $this->ejecutar($registro, $ip, $sistemaOrigen);
        }

        return $resultados;
    }

    /**
     * @param  array<int, array<string, mixed>>  $coincidencias
     */
    private function tieneCoincidenciaTipo(array $coincidencias, string $tipoCoincidencia): bool
    {
        return collect($coincidencias)->contains(
            static fn (array $coincidencia): bool => ($coincidencia['tipo_coincidencia'] ?? null) === $tipoCoincidencia
        );
    }

    /**
     * @param  array<string, mixed>  $politica
     */
    private function resolverAutoUserId(array $politica): ?int
    {
        $userId = isset($politica['auto_user_id']) ? (int) $politica['auto_user_id'] : (int) config('sarlaft.auto_user_id', 1);

        return $userId > 0 ? $userId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function construirContextoOperacion(
        bool $prestaServicioBase,
        bool $prestaServicioFinal,
        ?int $alertaId,
        ?string $origenAtencion,
        bool $coincidenciaListaNegraInterna,
    ): array {
        return [
            'presta_servicio_base' => $prestaServicioBase,
            'presta_servicio_final' => $prestaServicioFinal,
            'coincidencia_lista_negra_interna' => $coincidenciaListaNegraInterna,
            'alerta_id' => $alertaId,
            'origen_atencion' => $origenAtencion,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarEnListas(string $tipoDocumento, string $numeroDocumento, ?string $nombre): array
    {
        $coincidencias = [];

        $porDocumento = RegistroLista::query()
            ->select(['lista_id', 'nombres', 'alias', 'identificacion'])
            ->where('identificacion', $numeroDocumento)
            ->where('estado', 'activo')
            ->with('lista:id,nombre,tipo')
            ->get();

        foreach ($porDocumento as $registro) {
            $coincidencias[] = [
                'lista' => $registro->lista->nombre,
                'tipo_lista' => $registro->lista->tipo,
                'nombres' => $registro->nombres,
                'alias' => $registro->alias,
                'identificacion' => $registro->identificacion,
                'tipo_coincidencia' => 'documento_exacto',
            ];
        }

        if ($nombre !== null && trim($nombre) !== '') {
            $coincidencias = array_merge(
                $coincidencias,
                $this->buscarPorNombre($tipoDocumento, $nombre, $coincidencias)
            );
        }

        $listaNegra = DB::connection('mysql-sarlaft')->table('sarlaft_lista_negra_interna')
            ->where('numero_documento', $numeroDocumento)
            ->where('tipo_documento', $tipoDocumento)
            ->where('estado', 'activo')
            ->whereNull('deleted_at')
            ->first();

        if ($listaNegra) {
            $coincidencias[] = [
                'lista' => 'Lista Negra Interna',
                'tipo_lista' => 'interna',
                'nombres' => $listaNegra->nombres,
                'alias' => null,
                'identificacion' => $listaNegra->numero_documento,
                'tipo_coincidencia' => 'lista_negra_interna',
            ];
        }

        return $coincidencias;
    }

    /**
     * @param  array<int, array<string, mixed>>  $coincidencias
     */
    private function calcularNivelRiesgo(array $coincidencias): string
    {
        if (count($coincidencias) === 0) {
            return 'ninguno';
        }

        $coincidenciaCritica = collect($coincidencias)->contains(
            static fn (array $c): bool => ($c['tipo_coincidencia'] ?? null) === 'lista_negra_interna'
              || (
                  ($c['tipo_coincidencia'] ?? null) === 'documento_exacto'
                  && in_array($c['tipo_lista'] ?? '', ['vinculante', 'interna'], true)
              )
        );

        if ($coincidenciaCritica) {
            return 'alto';
        }

        $coincidenciaCriticaPorNombre = collect($coincidencias)->contains(
            static fn (array $c): bool => ($c['tipo_coincidencia'] ?? null) === 'nombre_similar'
              && in_array($c['tipo_lista'] ?? '', ['vinculante', 'interna'], true)
              && (float) ($c['puntaje_nombre'] ?? 0) >= self::NAME_HIGH_RISK_SCORE
              && (int) ($c['tokens_coincidentes'] ?? 0) >= self::NAME_HIGH_RISK_MIN_MATCHED_TOKENS
              && (float) ($c['cobertura_nombre'] ?? 0) >= self::NAME_HIGH_RISK_MIN_COVERAGE
        );

        if ($coincidenciaCriticaPorNombre) {
            return 'alto';
        }

        return count($coincidencias) > 1 ? 'medio' : 'bajo';
    }

    /**
     * @param  array<int, array<string, mixed>>  $coincidenciasActuales
     * @return array<int, array<string, mixed>>
     */
    private function buscarPorNombre(string $tipoDocumento, string $nombre, array $coincidenciasActuales): array
    {
        $nombreNormalizado = $this->normalizarTexto($nombre);
        $contextoConsulta = $this->construirContextoNombreConsulta($nombreNormalizado);

        if ($contextoConsulta['tokens_consulta'] < self::NAME_MIN_TOKENS) {
            return [];
        }

        $consultaBooleana = collect($contextoConsulta['tokens'])
            ->map(static fn (string $token): string => '+'.$token.'*')
            ->implode(' ');

        $candidatos = RegistroLista::query()
            ->select(['lista_id', 'nombres', 'alias', 'identificacion', 'tipo_entidad'])
            ->whereRaw(
                'MATCH(nombres, alias) AGAINST(? IN BOOLEAN MODE)',
                [$consultaBooleana]
            )
            ->where('estado', 'activo')
            ->where('tipo_entidad', $this->tipoEntidadParaNombre($tipoDocumento))
            ->with('lista:id,nombre,tipo')
            ->limit(self::NAME_SEARCH_LIMIT)
            ->get();

        $coincidencias = [];
        $coincidenciasExistentes = [];

        foreach ($coincidenciasActuales as $coincidenciaActual) {
            $coincidenciasExistentes[$this->crearLlaveCoincidencia(
                (string) ($coincidenciaActual['identificacion'] ?? ''),
                (string) ($coincidenciaActual['lista'] ?? ''),
            )] = true;
        }

        foreach ($candidatos as $registro) {
            $llaveCoincidencia = $this->crearLlaveCoincidencia(
                (string) $registro->identificacion,
                $registro->lista->nombre,
            );

            if (isset($coincidenciasExistentes[$llaveCoincidencia])) {
                continue;
            }

            $analisis = $this->calcularMejorAnalisisNombre($contextoConsulta, $registro);
            if ((float) $analisis['puntaje'] < self::NAME_MIN_SCORE) {
                continue;
            }

            $coincidencias[] = [
                'lista' => $registro->lista->nombre,
                'tipo_lista' => $registro->lista->tipo,
                'nombres' => $registro->nombres,
                'alias' => $registro->alias,
                'identificacion' => $registro->identificacion,
                'tipo_coincidencia' => 'nombre_similar',
                'puntaje_nombre' => round((float) $analisis['puntaje'], 3),
                'tokens_coincidentes' => $analisis['tokens_coincidentes'],
                'tokens_consulta' => $analisis['tokens_consulta'],
                'cobertura_nombre' => round((float) $analisis['cobertura'], 3),
                'nombre_exacto' => $analisis['nombre_exacto'],
                'valor_coincidente' => $analisis['valor_coincidente'],
            ];
            $coincidenciasExistentes[$llaveCoincidencia] = true;
        }

        return $coincidencias;
    }

    /**
     * @return array{
     *   nombre: string,
     *   nombre_ordenado: string,
     *   tokens: array<int, string>,
     *   tokens_consulta: int
     * }
     */
    private function construirContextoNombreConsulta(string $nombreNormalizado): array
    {
        $tokens = $this->obtenerTokensSignificativosNormalizados($nombreNormalizado);
        $tokensOrdenados = $tokens;
        sort($tokensOrdenados);

        return [
            'nombre' => $nombreNormalizado,
            'nombre_ordenado' => implode(' ', $tokensOrdenados),
            'tokens' => $tokens,
            'tokens_consulta' => count($tokens),
        ];
    }

    private function crearLlaveCoincidencia(string $identificacion, string $lista): string
    {
        return $identificacion.'|'.$lista;
    }

    private function tipoEntidadParaNombre(string $tipoDocumento): string
    {
        return strtoupper($tipoDocumento) === 'NIT' ? 'organizacion' : 'persona';
    }

    /**
     * @param  array{
     *   nombre: string,
     *   nombre_ordenado: string,
     *   tokens: array<int, string>,
     *   tokens_consulta: int
     * }  $contextoConsulta
     * @return array{
     *   puntaje: float,
     *   tokens_coincidentes: int,
     *   tokens_consulta: int,
     *   cobertura: float,
     *   nombre_exacto: bool,
     *   valor_coincidente: string
     * }
     */
    private function calcularMejorAnalisisNombre(array $contextoConsulta, RegistroLista $registro): array
    {
        $valores = [(string) $registro->nombres];

        if (is_array($registro->alias)) {
            foreach ($registro->alias as $alias) {
                if (is_string($alias) && trim($alias) !== '') {
                    $valores[] = $alias;
                }
            }
        }

        if (is_string($registro->alias) && trim($registro->alias) !== '') {
            $valores[] = $registro->alias;
        }

        $mejorAnalisis = [
            'puntaje' => 0.0,
            'tokens_coincidentes' => 0,
            'tokens_consulta' => 0,
            'cobertura' => 0.0,
            'nombre_exacto' => false,
            'valor_coincidente' => '',
        ];

        foreach ($valores as $valor) {
            $analisis = $this->analizarCoincidenciaNombre($contextoConsulta, $this->normalizarTexto($valor));
            if ($analisis['puntaje'] > $mejorAnalisis['puntaje']) {
                $mejorAnalisis = $analisis;
                $mejorAnalisis['valor_coincidente'] = $valor;
            }
        }

        return $mejorAnalisis;
    }

    /**
     * @param  array{
     *   nombre: string,
     *   nombre_ordenado: string,
     *   tokens: array<int, string>,
     *   tokens_consulta: int
     * }  $contextoConsulta
     * @return array{
     *   puntaje: float,
     *   tokens_coincidentes: int,
     *   tokens_consulta: int,
     *   cobertura: float,
     *   nombre_exacto: bool
     * }
     */
    private function analizarCoincidenciaNombre(array $contextoConsulta, string $nombreLista): array
    {
        $nombreConsulta = $contextoConsulta['nombre'];
        if ($nombreConsulta === '' || $nombreLista === '') {
            return [
                'puntaje' => 0.0,
                'tokens_coincidentes' => 0,
                'tokens_consulta' => 0,
                'cobertura' => 0.0,
                'nombre_exacto' => false,
            ];
        }

        $tokensConsulta = $contextoConsulta['tokens'];
        $tokensLista = $this->obtenerTokensSignificativosNormalizados($nombreLista);

        if ($tokensConsulta === [] || $tokensLista === []) {
            return [
                'puntaje' => 0.0,
                'tokens_coincidentes' => 0,
                'tokens_consulta' => count($tokensConsulta),
                'cobertura' => 0.0,
                'nombre_exacto' => false,
            ];
        }

        $interseccion = count(array_intersect($tokensConsulta, $tokensLista));
        if ($interseccion === 0) {
            return [
                'puntaje' => 0.0,
                'tokens_coincidentes' => 0,
                'tokens_consulta' => count($tokensConsulta),
                'cobertura' => 0.0,
                'nombre_exacto' => false,
            ];
        }

        $union = count(array_unique(array_merge($tokensConsulta, $tokensLista)));
        $jaccard = $union > 0 ? $interseccion / $union : 0.0;
        $coberturaConsulta = $interseccion / count($tokensConsulta);

        $tokensListaOrdenados = $tokensLista;
        sort($tokensListaOrdenados);
        $nombreListaOrdenado = implode(' ', $tokensListaOrdenados);

        similar_text($nombreConsulta, $nombreLista, $porcentajeDirecto);
        similar_text($contextoConsulta['nombre_ordenado'], $nombreListaOrdenado, $porcentajeOrdenado);
        $similitudTexto = max($porcentajeDirecto / 100, $porcentajeOrdenado / 100);

        return [
            'puntaje' => ($similitudTexto * 0.35) + ($jaccard * 0.25) + ($coberturaConsulta * 0.40),
            'tokens_coincidentes' => $interseccion,
            'tokens_consulta' => count($tokensConsulta),
            'cobertura' => $coberturaConsulta,
            'nombre_exacto' => $nombreConsulta === $nombreLista,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function obtenerTokensSignificativosNormalizados(string $valorNormalizado): array
    {
        if ($valorNormalizado === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $valorNormalizado) ?: [];

        return array_values(array_unique(array_filter($tokens, static function (string $token): bool {
            return strlen($token) >= 3 && ! in_array($token, self::NAME_STOPWORDS, true);
        })));
    }

    private function normalizarTexto(string $valor): string
    {
        $conSeparacion = preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $valor) ?? $valor;
        $ascii = Str::upper(Str::ascii($conSeparacion));
        $limpio = preg_replace('/[^A-Z0-9 ]/', ' ', $ascii) ?? '';

        return trim(preg_replace('/\s+/', ' ', $limpio) ?? '');
    }
}
