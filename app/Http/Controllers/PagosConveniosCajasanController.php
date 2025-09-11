<?php

namespace App\Http\Controllers;

use App\Models\LOGTRANS\ConDetPagosRecaudos;
use App\Models\LOGTRANS\ConPagosRecaudos;
use App\Models\LOGTRANS\ConReversoCajasan;
use App\Models\LOGTRANS\PerPersonas;
use App\Models\LOGTRANS\TesCajaTurnoDoc;
use App\Services\ApiAsopagos;
use App\Services\ComprobanteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagosConveniosCajasanController extends Controller
{
    // Constantes organizadas por categoría
    private const IDENEMPRESA = 890200106;
    private const TIPOMOVIMIENTO = 1;
    private const ROL_MODIFICA = 60;
    private const USRCARGUE = 6761;
    private const CIUDAD_DEFAULT = 'Bucaramanga';
    
    // Estados de transacciones
    private const ESTADO_CREADO = 'C';
    private const ESTADO_PAGADO = 'P';
    private const ESTADO_ANULADO = 'A';
    
    // Estados de fallo con reverso
    private const FALLOS_CON_REVERSO = [
        'fallo_timeout_con_reverso',
        'fallo_timeout_sin_reverso'
    ];

    // Configuraciones de comprobantes
    private const TM_ID = 1132941243;


    public function index(Request $request)
    {
        try {
            $cajaActiva = $request->attributes->get('caja_activa');
            
            if (!$cajaActiva || !isset($cajaActiva[0])) {
                Log::error('Caja activa no encontrada en el request');
                return sweetAlert(
                    'No existe caja activa',
                    'error', 
                    null, 
                    'No es posible acceder al módulo sin que el usuario tenga una caja activa asociada.');
            }
           
            return view('pagosRecaudos.index', compact('cajaActiva'));
        } catch (Exception $e) {
            Log::error('Error en index: ' . $e->getMessage());
            return redirect()->route('home')->with('error', 'Error al cargar la página');
        }
    }

    public function consultar(Request $request, ApiAsopagos $apiAsopagos)
    {
        try {
            // Validar entrada
            $identificacion = trim($request->identificacion);
            if (empty($identificacion)) {
                return toastModal('La identificación es requerida.', 'danger');
            }

            // Validar cliente
            $cliente = $this->validarCliente($identificacion);
            if (!$cliente) {
                return toastModal('El documento no es válido.', 'danger');
            }

            // Consulta a la API
            $respuesta = $this->consultarSaldoApi($apiAsopagos, $identificacion);
            if ($this->tieneErrorRespuesta($respuesta)) {
                Log::error('Error con API asopagos.', $respuesta);
                if($respuesta['error']){
                    return toastModal($respuesta['error'], 'danger');
                }
                return toastModal('Error en la consulta, verifique la información proporcionada', 'danger');
            }

            // Validar que hay saldo disponible
            $saldo = $respuesta['additionalData']['saldo'] ?? 0;
            if ($saldo <= 0) {
                return toastModal('No hay saldo disponible para este cliente', 'warning');
            }

            // Preparar y guardar datos
            $clienteData = $this->prepararDatosCliente($cliente);
          /*   $clienteData = [
                'identificacion' => $identificacion,
                'nombre' => "Usuario Prueba Cajasan",
            ]; */

            $cajaActiva = DB::connection('oracle')
                ->table('TES_CAJATURNOS as T')
                ->join('tes_cajas as CJ', 'T.CJ_ID', '=', 'CJ.ID')
                ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
                ->select('P.NOMSUCURSAL','P.id as idsucursal','T.*')
                ->where('T.ID', $request->caja_activa_id)
                ->first();

            if (!$cajaActiva) {
                return toastModal('No se encontró una caja activa válida.', 'danger');
            }

            //Generar UUID y guardar en cache (10 min)
            $uuid = Str::uuid()->toString();
            $datosParaCifrar = compact('clienteData', 'respuesta', 'cajaActiva');

            // Cifrar los datos sensibles
            $datosCifrados = Crypt::encrypt($datosParaCifrar);

            // Guardar datos cifrados en caché
            Cache::put("pago:{$uuid}", $datosCifrados, now()->addMinutes(10));

            return response()->json([
                'success' => true,
                'uuid'    => $uuid,
                'html'    => view('pagosRecaudos.pagosDisponibles', compact('clienteData', 'respuesta', 'uuid'))->render()
            ]);

        } catch (Exception $e) {
            Log::error('Error al consultar pago disponible cajasan: ' . $e->getMessage());
            return toastModal('Error en la consulta, inténtelo nuevamente más tarde', 'danger');
        }
    }

    public function validarInformacion(string $uuid)
    {
        $datosCifrados = Cache::get("pago:{$uuid}");

        if (!$datosCifrados) {
            return response()->json([
                'error' => true,
                'message' => "Sesión expirada, consulte nuevamente"
            ]);;
        }

        // Descifrar los datos
        $data = Crypt::decrypt($datosCifrados);

        $clienteData = $data['clienteData'];
        $respuesta   = $data['respuesta'];

        return view("pagosRecaudos.validarPago", compact('clienteData', 'respuesta', 'uuid'));
    }

    public function pagar(Request $request, ApiAsopagos $apiAsopagos)
    {
        $uuid = $request->uuid;
        $datosCifrados = Cache::get("pago:{$uuid}");

        if (!$datosCifrados) {
            return sweetAlert('Sesión expirada, consulte nuevamente', 'error');
        }

        // Descifrar datos
        $data        = Crypt::decrypt($datosCifrados);
        $clienteData = $data['clienteData'];
        $respuesta   = $data['respuesta'];
        $cajaActiva  = $data['cajaActiva'];

        try {
            //1. Validar saldo
            $saldo = $respuesta['additionalData']['saldo'] ?? 0;
            if ($saldo <= 0) {
                throw new Exception('Saldo insuficiente para procesar el pago');
            }

            //2. Crear cargue y detalle
            $idCargue = $this->obtenerIdCarguePagoRecaudos($cajaActiva);
            $idPagoDetalle = $this->crearDetallePagoRecaudos($idCargue, $clienteData, $respuesta, $cajaActiva);
            $detallePago = ConDetPagosRecaudos::findOrFail($idPagoDetalle);


            //3. Ejecutar pago en la API
            $pagoResponse = $this->procesarPago($apiAsopagos, $clienteData, $respuesta, $idPagoDetalle);
             
            if (!$this->esPagoExitoso($pagoResponse)) { 
                $this->manejarPagoFallido($pagoResponse, $detallePago); 
                Log::error('Error al realizar el pago', [
                    'identificacion' => $clienteData['identificacion'],
                    'resp_api'       => $pagoResponse,
                ]);

                //Fallo el reverso -> accion manual requerida
                if($pagoResponse['reverso']['responseCode'] == false){
                    return sweetAlert(
                        'Ocurrio un error en el pago', 
                        'error',
                        null, 
                        'Por favor comuniquese con mesa de ayuda y guarde el siguiente identificador: '. $pagoResponse['reverso']['authorizationRspCode']);
                }

                return sweetAlert(
                    'No fue posible procesar el pago.', 
                    'error',
                    null, 
                    'El pago fue rechazado por el proveedor. Por favor, verifique la información e intente nuevamente.');
            }


            //4. Crear comprobantes y caja turno documento
            try {
                $resultado = DB::connection('oracle')->transaction(function () use ($pagoResponse, $detallePago, $clienteData, $respuesta, $cajaActiva) {
                   
                    // Pago exitoso: actualizar estado y crear comprobantes
                    $codigo = $pagoResponse['authorizationRspCode'];
                    $detallePago->update([
                        'estado'      => self::ESTADO_PAGADO,
                        'nro_interno' => $codigo
                    ]);
                    
                    $saldo = $respuesta['additionalData']['saldo'] ?? 0;
                    $comprobanteService = new ComprobanteService();
                    $comprobante = $comprobanteService->crearComprobante($saldo, $cajaActiva);
                    $comprobanteService->crearAuxComprobante($comprobante->id, 'D', $saldo, $clienteData, $cajaActiva);
                    $comprobanteService->crearAuxComprobante($comprobante->id, 'C', $saldo, $clienteData, $cajaActiva);
                    $this->crearCajaTurnoDoc($comprobante->id, $saldo, $cajaActiva);

                     return [
                        'error'         => false,
                        'comprobante'   => $comprobante,
                        'idPagoDetalle' => $detallePago->id,
                        'saldo'         => $saldo
                    ];
                });
            } catch (Exception $e) {
                //Actualizar estado
                $codigo = $pagoResponse['authorizationRspCode'];
                $detallePago->update([
                    'estado'      => self::ESTADO_ANULADO,
                    'nro_interno' => $codigo
                ]);

                //Lanzar reverso en la API
                return $apiAsopagos->reversoRetiro(
                    "CC",
                    $clienteData['identificacion'],
                    $respuesta['additionalData']['saldo'],
                    11,
                    11001,
                    $idPagoDetalle,
                    $idPagoDetalle
                );

                Log::error('Error al realizar el pago, error interno', [
                    'identificacion' => $clienteData['identificacion'],
                    'error'          => $e->getMessage()
                ]);

                return sweetAlert('No fue posible completar el pago en este momento.', 'error', null, 'Ocurrió un error interno. Por favor, intente nuevamente más tarde.');
            }


            // Confirmación
            return view('pagosRecaudos.confirmacionPago', [
                'cajaActiva'    => $cajaActiva,
                'comprobante'   => $resultado['comprobante'],
                'idPagoDetalle' => $resultado['idPagoDetalle']
            ]);

        } catch (Exception $e) {
            Log::error('Error al realizar el pago cajasan: ' . $e->getMessage());
            return sweetAlert('Error al realizar el pago', 'error');
        } finally {
            // Limpiar cache del flujo
            Cache::forget("pago:{$uuid}");
        }
    }

    // === MÉTODOS PRIVADOS ===

    /**
     * Validar cliente por identificación
     */
    private function validarCliente(string $identificacion): ?PerPersonas
    {
        try {
            return PerPersonas::where('identificacion', $identificacion)->first();
        } catch (Exception $e) {
            Log::error('Error al validar cliente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Consultar saldo en la API
     */
    private function consultarSaldoApi(ApiAsopagos $apiAsopagos, string $identificacion): array
    {
        try {
            // Respuesta de prueba
            if(config('apiAsopagos.test_mode')){
                return [
                'responseCode' => true,
                'additionalData' => [
                    'saldo' => 500000
                ],
            ];
            }
            
            // Respuesta API
            return $apiAsopagos->consultarSaldo('CC', $identificacion, 11, 11001);
        } catch (Exception $e) {
            Log::error('Error en API consultarSaldo: ' . $e->getMessage());
            return ['error' => 'Error de comunicación con la API'];
        }
    }

    /**
     * Verificar si la respuesta tiene errores
     */
    private function tieneErrorRespuesta(array $respuesta): bool
    {
        return isset($respuesta['error']) || 
               ($respuesta['responseCode'] ?? false) === false ||
               !isset($respuesta['additionalData']['saldo']);
    }

    /**
     * Preparar datos del cliente
     */
    private function prepararDatosCliente(PerPersonas $cliente): array
    {
        return [
            'identificacion' => $cliente->identificacion,
            'nombre' => $cliente->nombreCompleto(),
        ];
    }

    /**
     * Procesar pago con la API
     */
    private function procesarPago(ApiAsopagos $apiAsopagos, array $clienteData, array $respuesta, int $idPagoDetalle): array
    {
        try {
            if(config('apiAsopagos.test_mode')){
                // success 
               /*   return [
                    'transactionId'        => random_int(1000000000000000, 9999999999999999),
                    'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
                    'responseCode'         => true,
                    'authorizationRspCode' => 555,
                    'errorID'              => 'E1',
                ];  */

                // Caso de prueba con error y reverso (satisfactorio/fallido)
                return [
                    'error' => 'Error al procesar el pago',
                    'responseCode' => false, 
                    'status' => 'fallo_timeout_sin_reverso', 
                    'reverso' => [
                        'transactionId' => $idPagoDetalle, 
                        'transmissionDataTime' => now(), 
                        'responseCode' => true, 
                        'authorizationRspCode' => 636771889,
                        'errorID' => '99'
                    ]
                ]; 
            }
           
            // Descomenta para usar la API real: 
            return $apiAsopagos->retirar(
                "CC",
                $clienteData['identificacion'],
                $respuesta['additionalData']['saldo'],
                11,
                11001,
                $idPagoDetalle,
                $idPagoDetalle
            );

        } catch (Exception $e) {
            Log::error('Error en API retirar: ' . $e->getMessage());
            return ['error' => 'Error de comunicación con la API', 'responseCode' => false];
        }
    }

    /**
     * Verificar si el pago fue exitoso
     */
    private function esPagoExitoso(array $pagoResponse): bool
    {
        return empty($pagoResponse['error']) && ($pagoResponse['responseCode'] ?? false) === true;
    }

    /**
     * Manejar pago fallido
     */
    private function manejarPagoFallido(array $pagoResponse, ConDetPagosRecaudos $detallePago): void
    {
        try {
            // Procesar reverso si aplica
            if ($this->requiereReverso($pagoResponse)) {
                $this->crearReverso($pagoResponse['reverso']);
            }

            // Cambiar estado a anulado
            $detallePago->update([
                'estado'      => self::ESTADO_ANULADO,
                'nro_interno' => $pagoResponse['reverso']['authorizationRspCode']
            ]);
        } catch (Exception $e) {
            Log::error('Error al manejar pago fallido: ' . $e->getMessage());
        }
    }

    /**
     * Verificar si requiere reverso
     */
    private function requiereReverso(array $pagoResponse): bool
    {
        return !empty($pagoResponse['status']) && 
               in_array($pagoResponse['status'], self::FALLOS_CON_REVERSO) &&
               isset($pagoResponse['reverso']);
    }

    /**
     * Crear registro de reverso
     */
    private function crearReverso(array $datosReverso): void
    {
        try {
            $nextId = $this->obtenerSiguienteIdReverso();

            $reverso = new ConReversoCajasan();
            $reverso->id = $nextId;
            $reverso->detalle_id = $datosReverso['transactionId'];
            $reverso->transmission_datetime = $datosReverso['transmissionDataTime'];
            $reverso->response_code = $datosReverso['responseCode'];
            $reverso->authorization_rsp_code = $datosReverso['authorizationRspCode'];
            $reverso->error_id = $datosReverso['errorID'];
            $reverso->additional_data = null;
            $reverso->save();
        } catch (Exception $e) {
            Log::error('Error al crear reverso: ' . $e->getMessage());
        }
    }

    /**
     * Obtener siguiente ID de reverso
     */
    private function obtenerSiguienteIdReverso()
    {
        try {
            return DB::connection('oracle')->table('CON_REVERSO_CAJASAN')->max('ID') + 1;
        } catch (Exception $e) {
            Log::error('Error al obtener ID reverso: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener o crear ID de cargue de pagos y recaudos
     */
    private function obtenerIdCarguePagoRecaudos($cajaActiva)
    {
        try {
            $fechaHoy = now()->format('Y-n-d');
            $descripcion = "CAJASAN " . $fechaHoy;

            // Buscar registro existente
            $cargue = ConPagosRecaudos::where('descripcion', $descripcion)->first();

            if ($cargue) {
                return $cargue->id;
            }

            // Crear nuevo registro
            return $this->crearNuevoCarguePagoRecaudos($descripcion, $cajaActiva);
        } catch (Exception $e) {
            Log::error('Error al obtener ID cargue: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Crear registro de caja turno documento
     */
    private function crearCajaTurnoDoc(int $idComprobante, float $valor, object $cajaActiva): void
    {
        try {
            $nextId = TesCajaTurnoDoc::max('id') + 1;
            $userId = $this->obtenerUserId();

            $cajaTurnoDoc = new TesCajaTurnoDoc();
            $cajaTurnoDoc->id = $nextId;
            $cajaTurnoDoc->ctu_ori_id = $cajaActiva->id;
            $cajaTurnoDoc->ctu_res_id = $cajaActiva->id;
            $cajaTurnoDoc->tm_id = self::TM_ID;
            $cajaTurnoDoc->fp_id = 2;
            $cajaTurnoDoc->bco_id = null;
            $cajaTurnoDoc->ctb_id = null;
            $cajaTurnoDoc->tipo = 'E';
            $cajaTurnoDoc->valor = $valor;
            $cajaTurnoDoc->estdocumento = 'EC';
            $cajaTurnoDoc->fecdocumento = now();
            $cajaTurnoDoc->nrodocumento = now();
            $cajaTurnoDoc->fecmodifica = now();
            $cajaTurnoDoc->usrmodifica = $userId;
            $cajaTurnoDoc->rolmodifica = self::ROL_MODIFICA;
            $cajaTurnoDoc->empmodifica = $cajaActiva->idsucursal;
            $cajaTurnoDoc->estborrado = 0;
            $cajaTurnoDoc->cpd_id = null;
            $cajaTurnoDoc->bco_nombre = null;
            $cajaTurnoDoc->nrocheque = null;
            $cajaTurnoDoc->feccheque = null;
            $cajaTurnoDoc->cp_id = $idComprobante;
            $cajaTurnoDoc->girador = null;
            $cajaTurnoDoc->save();
        } catch (Exception $e) {
            Log::error('Error al crear caja turno doc: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear nuevo cargue de pagos y recaudos
     */
    private function crearNuevoCarguePagoRecaudos(string $descripcion, object $cajaActiva): int
    {
        try {
            $fechaActual = now();
            $userId = $this->obtenerUserId();
            $idGenerado = $this->obtenerSiguienteId('SEC_PAGOSYRECAUDOS');

            $cargue = new ConPagosRecaudos();
            $cargue->id = $idGenerado;
            $cargue->idenempresa = self::IDENEMPRESA;
            $cargue->descripcion = $descripcion;
            $cargue->valortotal = 0;
            $cargue->tipomovimiento = self::TIPOMOVIMIENTO;
            $cargue->estado = self::ESTADO_CREADO;
            $cargue->fechacargue = $fechaActual;
            $cargue->usrcargue = self::USRCARGUE;
            $cargue->estborrado = 0;
            $cargue->fecmodifica = $fechaActual;
            $cargue->empmodifica = $cajaActiva->idsucursal;
            $cargue->usrmodifica = $userId;
            $cargue->rolmodifica = self::ROL_MODIFICA;
            $cargue->feccreacion = $fechaActual;
            $cargue->usrcreacion = $userId;
            $cargue->empcreacion = $cajaActiva->idsucursal;
            $cargue->save();
            
            return $cargue->id;
        } catch (Exception $e) {
            Log::error('Error al crear cargue: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear detalle de pago y recaudos
     */
    private function crearDetallePagoRecaudos(int $idCargue, array $clienteData, array $respuestaApi, object $cajaActiva): int
    {
        try {
            $fechaFormatoEspecial = now()->format('Y-n');
            $concepto = "CONSULTA CAJASAN " . $fechaFormatoEspecial;

            $identificacion = $clienteData['identificacion'];
            $nombreCompleto = $clienteData['nombre'];
            $saldoTotal = $respuestaApi['additionalData']['saldo'];

            $fechaActual = now();
            $sucursal = PerPersonas::findOrFail($cajaActiva->idsucursal);
            $userId = $this->obtenerUserId();
            $idGenerado = $this->obtenerSiguienteId('SEC_PAGOSYRECAUDOSDET');

            $detalle = new ConDetPagosRecaudos();
            $detalle->id = $idGenerado;
            $detalle->id_carguepagyrec = $idCargue;
            $detalle->concepto = $concepto;
            $detalle->iden_clienteprincipal = $identificacion;
            $detalle->clienteprincipal = $nombreCompleto;
            $detalle->iden_clientesecundario = $identificacion;
            $detalle->clientesecundario = $nombreCompleto;
            $detalle->valortotal = $saldoTotal;
            $detalle->valorparcial = $saldoTotal;
            $detalle->estado = self::ESTADO_CREADO;
            $detalle->codciudad = $sucursal->codigo;
            $detalle->ciudad = self::CIUDAD_DEFAULT;
            $detalle->fecha_desde = now()->startOfDay();
            $detalle->fecha_hasta = now()->addDay()->startOfDay();
            $detalle->nro_interno = 0;
            $detalle->codagencia = $sucursal->codigo;
            $detalle->agencia = $sucursal->codigo . " - " . $sucursal->nomsucursal;
            $detalle->estborrado = 0;
            $detalle->fecmodifica = $fechaActual;
            $detalle->empmodifica = $cajaActiva->idsucursal;
            $detalle->usrmodifica = $userId;
            $detalle->rolmodifica = self::ROL_MODIFICA;
            $detalle->feccreacion = $fechaActual;
            $detalle->usrcreacion = $userId;
            $detalle->empcreacion = $cajaActiva->idsucursal;
            $detalle->save();
            return $detalle->id;

        } catch (Exception $e) {
            Log::error('Error al crear detalle pago: ' . $e->getMessage());
            throw $e;
        }
    }

    // === MÉTODOS AUXILIARES ===

    private function obtenerUserId(): int
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->persona) {
                throw new Exception('Usuario no autenticado o sin persona asociada');
            }

            $userId = PerPersonas::where('identificacion', $user->persona->PerNumDoc)->value('id');
            
            if (!$userId) {
                throw new Exception('Usuario no encontrado en la tabla PerPersonas');
            }

            return $userId;
        } catch (Exception $e) {
            Log::error('Error al obtener userId: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener siguiente ID de una secuencia Oracle
     */
    private function obtenerSiguienteId(string $secuencia)
    {
        try {
            $result = DB::connection('oracle')
                ->select("SELECT {$secuencia}.NEXTVAL as id FROM DUAL");

            if (!$result || !isset($result[0]->id)) {
                throw new Exception('No se pudo obtener el siguiente ID de la secuencia: ' . $secuencia);
            }

            return $result[0]->id;
        } catch (Exception $e) {
            Log::error('Error al obtener siguiente ID: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar recibo PDF
     */
    public function generarRecibo($IdDetallePago)
    {
        try {
            $detallePago = ConDetPagosRecaudos::findOrFail($IdDetallePago);

            $agencia = PerPersonas::where("codigo", $detallePago->codagencia)->value("nomsucursal");
            $usuarioAgencia = Auth::user()->persona->nombreCompleto();

            // Validar y separar fecha y hora
            if (!$detallePago->feccreacion) {
                throw new Exception('Fecha de creación no disponible');
            }

            $fechaHora = explode(' ', $detallePago->feccreacion);
            $fechaPago = $fechaHora[0] ?? '';
            $horaPago = $fechaHora[1] ?? '';
            
            $data = [
                'linea_atencion' => 'Línea de Atención al Cliente 018000114161-0180006444164',
                'vigencia' => 'Vigilado por Mintic',
                'convenio' => 'CONVENIO CAJASAN - PAGOS',
                'fecha' => $fechaPago,
                'hora' => $horaPago,
                'agencia' => $agencia,
                'ciudad' => strtoupper($detallePago->ciudad),
                'codigo' => $detallePago->nro_interno,
                'principal' => $detallePago->clienteprincipal,
                'identificacion_principal' => $detallePago->iden_clienteprincipal,
                'pagado_a' => $detallePago->clienteprincipal,
                'identificacion_pagado' => $detallePago->iden_clienteprincipal,
                'concepto' => 'CONVENIO CAJASAN - PAGOS',
                'valor' => '$' . number_format($detallePago->valortotal, 0, ',', '.') . '=',
                'valor_total' => '$' . number_format($detallePago->valortotal, 0, ',', '.') . '=',
                'usuario_impresion' => $usuarioAgencia,
                'fecha_impresion' => $fechaPago,
                'hora_impresion' => $horaPago
            ];

            $pdf = PDF::loadView('pagosRecaudos.recibo', $data)
                ->setPaper([0, 0, 80, 210], 'portrait') 
                ->setOptions([
                    'dpi' => 203,
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                ]);

            return $pdf->stream('recibo-cajasan.pdf');

        } catch (Exception $e) {
            Log::error('Error al generar recibo: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el recibo');
        }
    }

    // === MÉTODOS DE RESPUESTA AUXILIARES ===

    /**
     * Generar respuesta sweet alert consistente
     */
    private function sweetAlertResponse(string $message, string $type, ?string $route = null): mixed
    {
        if ($route) {
            return sweetAlert($message, $type, $route);
        }
        return sweetAlert($message, $type);
    }
}