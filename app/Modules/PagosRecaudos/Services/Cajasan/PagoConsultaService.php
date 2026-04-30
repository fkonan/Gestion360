<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\Administration\Models\TipoDocumento;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\GestionWeb\Models\GenMunicipios;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Modules\Sarlaft\Services\ValidacionListaNegraService;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagoConsultaService
{
    private const TIPOS_DOCUMENTO_ASOPAGOS = [
        1 => 'CC',
        2 => 'TI',
        3 => 'NT',
        5 => 'CE',
        6 => 'PA',
        43 => 'RC',
        50 => 'PE',
    ];

    private const TIPOS_DOCUMENTO_SOPORTADOS = [
        'RC',
        'TI',
        'CC',
        'CE',
        'PA',
        'CD',
        'NT',
        'PE',
        'SV',
        'PT',
    ];

    public function __construct(
        private readonly ValidacionListaNegraService $validacionListaNegraService,
    ) {}

    /**
     * Consultar saldo disponible y preparar datos de cliente
     */
    public function consultarSaldo(string $identificacion, int|object $cajaActivaRef, ApiAsopagos $apiAsopagos): array
    {
        $startedAt = microtime(true);
        $runtime = new AsopagosRuntimeConfig;

        try {
            PagosRecaudosLogger::info('Inicio de consulta de saldo', [
                'operation' => 'consulta_saldo',
                'identificacion_cliente' => $identificacion,
                'caja_activa_ref' => is_object($cajaActivaRef) ? ($cajaActivaRef->id ?? null) : $cajaActivaRef,
            ] + $runtime->context());

            if ($runtime->isDangerousMockConfiguration()) {
                PagosRecaudosLogger::warning('Proveedor mock activo con persistencia real en PagosRecaudos', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                ] + $runtime->context());
            }

            // 1. Validar cliente
            $cliente = PerPersonas::where('identificacion', $identificacion)
                ->where('estado', 'ACTIVO')
                ->where('estborrado', 0)
                ->first();
            if (! $cliente) {
                PagosRecaudosLogger::warning('Cliente no encontrado en consulta de saldo', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return ['error' => true, 'message' => 'Verifique el documento. Si es primer pago, registre la persona.'];
            }

            $sigla = $this->resolverTipoIdentificacion($cliente);

            if (! $sigla) {
                PagosRecaudosLogger::warning('Tipo de identificacion no valido para consulta de saldo', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'tipo_documento' => $cliente->tipdocumento ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return [
                    'error' => true,
                    'message' => 'No fue posible validar la identificacion del usuario.',
                ];
            }

            // 2. Obtener caja activa
            $cajaActiva = $this->resolverCajaActiva($cajaActivaRef);

            if (! $cajaActiva) {
                PagosRecaudosLogger::warning('No fue posible resolver la caja activa para la consulta', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);
                return ['error' => true, 'message' => 'No se encontro una caja activa valida.'];
            }

            $userId = UsuarioService::obtenerUserId();

            // 3. Preparar datos cliente
            [$departamento, $municipio] = $this->resolverUbicacionCaja($cajaActiva);

            if ($departamento === null || $municipio === null) {
                PagosRecaudosLogger::warning('No fue posible resolver la ubicacion de la caja activa', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'caja_activa_id' => $cajaActiva->id ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);
                return ['error' => true, 'message' => 'No fue posible resolver la ubicacion de la caja activa.'];
            }

            $clienteData = [
                'tipoIdentificacion' => $sigla,
                'identificacion' => $cliente->identificacion,
                'nombre' => $cliente->nombreCompleto(),
                'departamento' => $departamento,
                'municipio' => $municipio,
            ];

            // 4. Consultar API
            $respuesta = $this->consultarSaldoApi($apiAsopagos, $clienteData, $runtime);
            if ($this->tieneErrorRespuesta($respuesta)) {
                $mensajeProveedor = $this->extraerMensajeProveedor($respuesta, 'Error en la consulta.');

                PagosRecaudosLogger::warning('Consulta de saldo finalizo con error en proveedor', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'provider_response_code' => $respuesta['responseCode'] ?? null,
                    'provider_error' => $mensajeProveedor,
                    'provider_error_id' => $respuesta['errorID'] ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return [
                    'error' => true,
                    'message' => $mensajeProveedor,
                ];
            }

            // 5. Validar listas restrictivas con saldo de respuesta API
            $saldo = $respuesta['additionalData']['saldo'] ?? 0;

            $validacionListas = $this->validacionListaNegraService->consultarPorIdentificacion(
                numeroIdentificacion: (string) $cliente->identificacion,
                tipoDocumento: $sigla,
            );

            if (($validacionListas['en_lista_negra'] ?? false) === true) {
                $listasCoincidentes = is_array($validacionListas['listas'] ?? null)
                    ? $validacionListas['listas']
                    : [];

                $this->validacionListaNegraService->registrarIntentoOperacionPrimeraCoincidencia(
                    resultadoValidacion: $validacionListas,
                    datosOperacion: [
                        'sistema_origen' => 'pagos_recaudos_cajasan',
                        'modo_integracion' => 'push',
                        'tipo_documento' => $sigla,
                        'numero_documento' => (string) $cliente->identificacion,
                        'nombre' => $cliente->nombreCompleto(),
                        'tipo_operacion' => 'pago',
                        'referencia' => null,
                        'referencia_externa' => null,
                        'fecha_operacion' => now(),
                        'monto' => $saldo,
                        'moneda' => 'COP',
                        'descripcion' => 'Intento bloqueado por coincidencia en lista negra durante consulta de saldo Cajasan.',
                        'contexto' => [
                            'modulo' => 'pagos_recaudos',
                            'proceso' => 'consulta_saldo_cajasan',
                            'saldo_api' => $saldo,
                            'agencia' => $this->resolverNombreAgencia($cajaActiva),
                        ],
                        'ip_origen' => request()?->ip(),
                    ],
                );

                PagosRecaudosLogger::warning('Cliente bloqueado por coincidencia en listas restrictivas', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'tipo_documento' => $sigla,
                    'listas_coincidentes' => $listasCoincidentes,
                    'saldo' => $saldo,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return [
                    'error' => true,
                    'message' => 'Por el momento no es posible continuar con el pago debido a un problema con la validación de los datos personales suministrados.',
                    'listas' => $listasCoincidentes,
                ];
            }

            // 6. Validar saldo
            if ($saldo <= 0) {
                PagosRecaudosLogger::info('Consulta de saldo sin fondos disponibles', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'saldo' => $saldo,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return [
                    'error' => true,
                    'message' => 'El usuario no cuenta con saldo disponible para realizar el pago.',
                ];
            }

            // 7. Guardar en cache
            $uuid = Str::uuid()->toString();
            $contextoPago = $this->crearContextoPago($cajaActiva, $userId);
            $datosCifrados = Crypt::encrypt(compact('clienteData', 'respuesta', 'cajaActiva', 'contextoPago'));
            Cache::put("pago:{$uuid}", $datosCifrados, now()->addMinutes(10));

            PagosRecaudosLogger::info('Consulta de saldo completada y cacheada', [
                'operation' => 'consulta_saldo',
                'uuid' => $uuid,
                'identificacion_cliente' => $identificacion,
                'saldo' => $saldo,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return [
                'error' => false,
                'uuid' => $uuid,
                'clienteData' => $clienteData,
                'respuesta' => $respuesta,
            ];
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error no controlado en consulta de saldo', $e, [
                'operation' => 'consulta_saldo',
                'identificacion_cliente' => $identificacion,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return ['error' => true, 'message' => 'Error en la consulta, intentelo nuevamente mas tarde'];
        }
    }

    private function resolverCajaActiva(int|object $cajaActivaRef): ?object
    {
        if (is_object($cajaActivaRef)) {
            return $cajaActivaRef;
        }

        return DB::connection('oracle')
            ->table('TES_CAJATURNOS as T')
            ->join('TES_CAJAS as CJ', 'T.CJ_ID', '=', 'CJ.ID')
            ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
            ->select('P.NOMSUCURSAL', 'P.id as idsucursal', 'T.*')
            ->where('T.ID', $cajaActivaRef)
            ->first();
    }

    private function resolverUbicacionCaja(object $cajaActiva): array
    {
        $ubicacion = Cache::remember(
            "pagos-recaudos:ubicacion-caja:{$cajaActiva->idsucursal}",
            now()->addMinutes(30),
            function () use ($cajaActiva) {
                $sucursal = PerPersonas::where('id', $cajaActiva->idsucursal)
                    ->where('estado', 'ACTIVO')
                    ->where('estborrado', 0)
                    ->firstOrFail();

                $municipio = $this->buscarMunicipioConFallback($sucursal->mu_id);

                return [
                    'departamento' => $this->normalizarCodigoUbicacion($municipio->do_codigo, 2),
                    'municipio' => $this->normalizarCodigoUbicacion($municipio->codigo, 5),
                ];
            }
        );

        return [(string) $ubicacion['departamento'], (string) $ubicacion['municipio']];
    }

    private function buscarMunicipioConFallback(int|string|null $codigoMunicipio): GenMunicipios
    {
        $codigoOriginal = trim((string) $codigoMunicipio);
        $municipio = GenMunicipios::where('codigo', $codigoOriginal)->first();

        if (! $municipio && preg_match('/^\d{4}$/', $codigoOriginal) === 1) {
            $municipio = GenMunicipios::where('codigo', '0'.$codigoOriginal)->first();
        }

        if (! $municipio) {
            $municipio = GenMunicipios::where('codigo', $codigoOriginal)->firstOrFail();
        }

        return $municipio;
    }

    private function normalizarCodigoUbicacion(mixed $codigo, int $longitud): string
    {
        $valor = trim((string) $codigo);

        if ($valor !== '' && ctype_digit($valor)) {
            return str_pad($valor, $longitud, '0', STR_PAD_LEFT);
        }

        return $valor;
    }

    private function consultarSaldoApi(ApiAsopagos $apiAsopagos, array $clienteData, AsopagosRuntimeConfig $runtime): array
    {
        try {
            if ($runtime->shouldMockProvider()) {
                $mock = new AsopagosMockService;
                PagosRecaudosLogger::debug('Consulta de saldo usando respuesta mock', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $clienteData['identificacion'] ?? null,
                ] + $runtime->context());

                return $mock->consultaSaldo($clienteData, $runtime);
            }

            return $apiAsopagos->consultarSaldo(
                $clienteData['tipoIdentificacion'],
                $clienteData['identificacion'],
                $clienteData['departamento'],
                $clienteData['municipio']
            );
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al consultar saldo en proveedor', $e, [
                'operation' => 'consulta_saldo',
                'identificacion_cliente' => $clienteData['identificacion'] ?? null,
            ]);

            return ['error' => true, 'message' => 'No fue posible consultar el saldo en este momento. Intente nuevamente mas tarde.'];
        }
    }

    private function tieneErrorRespuesta(array $respuesta): bool
    {
        return isset($respuesta['error']) ||
          ($respuesta['responseCode'] ?? false) === false ||
          ! isset($respuesta['additionalData']['saldo']);
    }

    private function extraerMensajeProveedor(array $respuesta, string $fallback): string
    {
        $additionalData = is_array($respuesta['additionalData'] ?? null) ? $respuesta['additionalData'] : [];
        $candidatos = [
            $respuesta['message'] ?? null,
            $respuesta['error'] ?? null,
            $additionalData['validationError'] ?? null,
            $additionalData['errorMessage'] ?? null,
            $additionalData['errorMesssage'] ?? null,
            $respuesta['errorID'] ?? null,
            $fallback,
        ];

        foreach ($candidatos as $valor) {
            if (is_string($valor) && trim($valor) !== '') {
                return trim($valor);
            }
        }

        return $fallback;
    }

    private function crearContextoPago(object $cajaActiva, int $userId): array
    {
        return [
            'usuario_id' => $userId,
            'caja_turno_id' => (int) ($cajaActiva->id ?? 0),
            'sucursal_id' => (int) ($cajaActiva->idsucursal ?? 0),
        ];
    }

    private function resolverTipoIdentificacion(object $cliente): ?string
    {
        $sigla = self::TIPOS_DOCUMENTO_ASOPAGOS[$cliente->tipdocumento] ?? null;
        if ($sigla) {
            return $sigla;
        }

        $cacheKey = "pagos-recaudos:tipo-documento:{$cliente->tipdocumento}";
        $nomenclatura = Cache::remember($cacheKey, now()->addHours(4), function () use ($cliente) {
            $valor = TipoDocumento::query()
                ->whereKey($cliente->tipdocumento)
                ->value('nomenclatura');

            return is_string($valor) ? strtoupper(trim($valor)) : null;
        });

        if (! is_string($nomenclatura) || $nomenclatura === '') {
            return null;
        }

        return in_array($nomenclatura, self::TIPOS_DOCUMENTO_SOPORTADOS, true)
            ? $nomenclatura
            : null;
    }

    private function resolverNombreAgencia(object $cajaActiva): ?string
    {
        $candidatos = [
            $cajaActiva->NOMSUCURSAL ?? null,
            $cajaActiva->nomsucursal ?? null,
            $cajaActiva->nomSucursal ?? null,
            $cajaActiva->nombre_sucursal ?? null,
        ];

        foreach ($candidatos as $candidato) {
            if (! is_string($candidato)) {
                continue;
            }

            $valor = trim($candidato);
            if ($valor !== '') {
                return $valor;
            }
        }

        return isset($cajaActiva->idsucursal) ? (string) $cajaActiva->idsucursal : null;
    }
}

