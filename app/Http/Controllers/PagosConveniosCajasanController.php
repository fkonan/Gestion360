<?php

namespace App\Http\Controllers;

use App\Models\LOGTRANS\ConAuxComprobantes;
use App\Models\LOGTRANS\ConComprobantes;
use App\Models\LOGTRANS\ConDetPagosRecaudos;
use App\Models\LOGTRANS\ConPagosRecaudos;
use App\Models\LOGTRANS\ConReversoCajasan;
use App\Models\LOGTRANS\PerPersonas;
use App\Models\LOGTRANS\TesCajaTurnoDoc;
use App\Services\ApiAsopagos;
use App\Services\EmpleadoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagosConveniosCajasanController extends Controller
{
    // Constantes
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

    public function index(Request $request)
    {
        $cajaActiva = $request->attributes->get('caja_activa');
        session()->put('cajaActiva', $cajaActiva[0]);

        return view('pagosRecaudos.index', compact('cajaActiva'));
    }

    public function consultar(Request $request, ApiAsopagos $apiAsopagos)
    {
        try {
            // Validar identificación
            $cliente = $this->validarCliente($request->identificacion);
            if (!$cliente) {
                Log::error('El documento no es valido: ' . $request->identificacion);
                return toastModal('El documento no es válido.', 'danger');
            }

            // Consulta a la API
            $respuesta = $this->consultarSaldoApi($apiAsopagos, $request->identificacion);

            if ($this->tieneErrorRespuesta($respuesta)) {
                Log::error('Error con API asopagos.' . $respuesta);
                return toastModal('Error en la consulta, verifique la información proporcionada', 'danger');
            }

            // Guardar datos del cliente en sesion
            $clienteData = $this->prepararDatosCliente($cliente);
            $this->guardarDatosEnSesion($clienteData, $respuesta);

            return response()->json([
                'success' => true,
                'html' => view('pagosRecaudos.pagosDisponibles', [
                    'data' => $respuesta,
                    'clienteData' => $clienteData
                ])->render()
            ]);

        } catch (Exception $e) {
            Log::error('Error al consultar pago disponible cajasan: ' . $e->getMessage());
            return toastModal('Error en la consulta, intentelo nuevamente más tarde', 'danger');
        }
    }

    public function validarInformacion()
    {
        if (!$this->validarDatosSesion()) {
            return response()->view('home');
        }

        $clienteData = session('clienteData_temp');
        $respuesta = session('respuesta_temp');

        return view("pagosRecaudos.validarPago", compact('clienteData', 'respuesta'));
    }

    public function pagar(Request $request, ApiAsopagos $apiAsopagos)
    {
        DB::beginTransaction();

        try {
            // Validar datos de sesión
            if (!$this->validarDatosSesion()) {
                return toast('Datos de sesión inválidos', 'danger', route('pagosConvenios.index'));
            }

            $clienteData = session('clienteData_temp');
            $respuesta = session('respuesta_temp');

            // Crear registros necesarios
            $idCargue = $this->obtenerIdCarguePagoRecaudos();
            $idPagoDetalle = $this->crearDetallePagoRecaudos($idCargue);

            // Procesar pago
            $pagoResponse = $this->procesarPago($apiAsopagos, $clienteData, $respuesta, $idPagoDetalle);
            $detallePago = ConDetPagosRecaudos::findOrFail($idPagoDetalle);

            // Manejar respuesta del pago
            if (!$this->esPagoExitoso($pagoResponse)) {
                $this->manejarPagoFallido($pagoResponse, $detallePago);
                Log::error('Error al realizar el pago cajasan, pago no exitoso' . $pagoResponse);
                return sweetAlert('Error al realizar el pago, verifique e intente nuevamente','error');
            }

            // Actualizar estado a pagado
            $detallePago->update(['estado' => self::ESTADO_PAGADO]);

            // Crear comprobante
            $idComprobante = $this->crearComprobante();

            // Crear comprobante auxiliar (para debito y credito)
            $this->crearAuxComprobante($idComprobante,'D');  
            $this->crearAuxComprobante($idComprobante,'C');

            // Caja turno
            $this->crearCajaTurnoDoc($idComprobante);

            // Limpiar sesión
            $this->limpiarDatosSesion();

            DB::commit();
            return toast('Pago realizado con éxito', 'success',route('pagosConvenios.index'));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al realizar el pago cajasan: ' . $e->getMessage());
            return sweetAlert(
                    'Error al realizar el pago, verifique e intente nuevamente',
                    'error'
             );
        }
    }


    // Métodos privados
    private function validarCliente(string $identificacion): ?PerPersonas
    {
        return PerPersonas::where('identificacion', $identificacion)->first();
    }

    private function consultarSaldoApi(ApiAsopagos $apiAsopagos, string $identificacion): array
    {
        // Respuesta de prueba
        return [
            'responseCode' => true,
            'additionalData' => [
                'saldo' => 65000
            ],
        ];

        // Descomenta para usar la API real
        // return $apiAsopagos->consultarSaldo('CC', $identificacion, 11, 11001);
    }

    private function tieneErrorRespuesta(array $respuesta): bool
    {
        return isset($respuesta['error']) || ($respuesta['responseCode'] ?? false) === false;
    }

    private function prepararDatosCliente(PerPersonas $cliente): array
    {
        return [
            'identificacion' => $cliente->identificacion,
            'nombre' => $cliente->nombreCompleto(),
        ];
    }

    private function guardarDatosEnSesion(array $clienteData, array $respuesta): void
    {
        session([
            'clienteData_temp' => $clienteData,
            'respuesta_temp' => $respuesta
        ]);
    }

    private function validarDatosSesion(): bool
    {
        return session()->has('clienteData_temp') && session()->has('respuesta_temp');
    }

    private function procesarPago(ApiAsopagos $apiAsopagos, array $clienteData, array $respuesta, int $idPagoDetalle): array
    {
        // Descomentar para usar la API real:
        /*
        return $apiAsopagos->retirar(
            "CC",
            $clienteData['identificacion'],
            $respuesta['additionalData']['saldo'],
            11,
            11001,
            $idPagoDetalle,
            $idPagoDetalle
        );
        */

        // Respuesta de prueba
        return ['responseCode' => true];


        // Caso de prueba con error y reverso (satisfactorio/fallido)
        /*   return [
            'error' => 'Error al procesar el pago',
            'responseCode' => false, 
            'status' => 'fallo_timeout_con_reverso', 
            'reverso' => [
                'transactionId' => $idPagoDetalle, 
                'transmissionDataTime' => now()->format('d/m/y H:i:s'), 
                'responseCode' => true, 
                'authorizationRspCode' => 636771870,
                'errorID' => '99'
            ]
        ]; */
    }

    private function esPagoExitoso(array $pagoResponse): bool
    {
        return empty($pagoResponse['error']) && ($pagoResponse['responseCode'] ?? false) === true;
    }

    private function manejarPagoFallido(array $pagoResponse, ConDetPagosRecaudos $detallePago): void
    {
        // Procesar reverso si aplica
        if ($this->requiereReverso($pagoResponse)) {
            $this->crearReverso($pagoResponse['reverso']);
        }

        // Cambiar estado a anulado
        $detallePago->update(['estado' => self::ESTADO_ANULADO]);
    }

    private function requiereReverso(array $pagoResponse): bool
    {
        return !empty($pagoResponse['status']) && 
               in_array($pagoResponse['status'], self::FALLOS_CON_REVERSO) &&
               isset($pagoResponse['reverso']);
    }

    private function crearReverso(array $datosReverso): void
    {
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
    }

    private function obtenerSiguienteIdReverso(): int
    {
        return DB::connection('oracle')->table('CON_REVERSO_CAJASAN')->max('ID') + 1;
    }

    private function limpiarDatosSesion(): void
    {
        session()->forget(['clienteData_temp', 'respuesta_temp', 'cajaActiva']);
    }

    private function obtenerIdCarguePagoRecaudos(): int
    {
        $fechaHoy = now()->format('Y-n-d');
        $descripcion = "CAJASAN " . $fechaHoy;

        // Buscar registro existente
        $cargue = ConPagosRecaudos::where('descripcion', $descripcion)->first();

        if ($cargue) {
            return $cargue->id;
        }

        // Crear nuevo registro
        return $this->crearNuevoCarguePagoRecaudos($descripcion);
    }

    private function crearComprobante(): int
    {
        //Informacion del comprobante
        $nextId = ConComprobantes::max('id') + 1;
        $comprobanteId = DB::connection('oracle')->select("SELECT SEC_DOC_COMPROBANTE.NEXTVAL as id FROM DUAL")[0]->id;

        //Datos de la caja activa
        $cajaActiva = $this->obtenerCajaActiva();

        //Valor del comprobante
        $respuestaApi = $this->obtenerRespuestaApi();   
        $valor = $respuestaApi['additionalData']['saldo'];

        $tc_codigo = DB::connection('oracle')
            ->table('CON_ASIENTOS as A')
            ->join('CON_ASIENTOMOVIMIENTOS as AM', 'A.ID', '=', 'AM.AS_ID')
            ->where('AM.TM_ID', 1132941243)
            ->where('A.ESTBORRADO', 0)
            ->where('AM.ESTBORRADO', 0)
            ->value('TC_CODIGO'); 

        //Crear el comprobante
        $comprobante = new ConComprobantes();
        $comprobante->id = $nextId;
        $comprobante->descripcion = 'MOVIMIENTOS GIROS';
        $comprobante->comprobante = $comprobanteId;
        $comprobante->estado = 0;
        $comprobante->pe_id_ag = $cajaActiva->idsucursal;
        $comprobante->en_id = $cajaActiva->en_id;
        $comprobante->cp_id = null;
        $comprobante->fecautoriza = null;
        $comprobante->fecaplica = now();
        $comprobante->usrautoriza = null;
        $comprobante->valtotcredito = $valor;
        $comprobante->valtotdebito = $valor;
        $comprobante->fecmodifica = now();
        $comprobante->usrmodifica = $this->obtenerUserId();
        $comprobante->rolmodifica = self::ROL_MODIFICA;
        $comprobante->empmodifica = $cajaActiva->idsucursal;
        $comprobante->estborrado = 0;
        $comprobante->docnro = $comprobanteId;
        $comprobante->docrep = 1;
        $comprobante->docver = 1;
        $comprobante->docestado = 'TEMPORAL';
        $comprobante->tc_codigo = $tc_codigo;
        $comprobante->ct_id = $cajaActiva->id;
        $comprobante->as_id = 1139194189;
        $comprobante->agrupado = 'TA';
        $comprobante->generado = null;
        $comprobante->automatico = 'T';
        $comprobante->constipoagencia = 87838;
        $comprobante->nrooriginal = null;
        $comprobante->tipoperacion = 60;
        $comprobante->indicador = null;
        $comprobante->consap = 0;
        $comprobante->reversado = null;
        $comprobante->feccreacion = now();
        $comprobante->usrcreacion = $this->obtenerUserId();
        $comprobante->empcreacion = $cajaActiva->idsucursal;
        $comprobante->feccontasap = null;
        $comprobante->cp_idanula = null;
        $comprobante->gr_ledger = null;
        $comprobante->conniif = null;
        $comprobante->save();

        return $nextId;
    }

    private function crearAuxComprobante($idComprobante,$tipo): void
        //Tipo -> Debido 'D'  y Credito 'C'
    {
        //Informacion para crear el comprobante auxiliar
        $nextId = ConAuxComprobantes::max('id') + 1;
        $numeroCuenta = $this->obtenerNumeroCuenta($tipo);
        $respuestaApi = $this->obtenerRespuestaApi();

        //Usuario caja
        $user = Auth::user();
        $documento = $user->persona->PerNumDoc;
        $centroCosto = EmpleadoService::codigoCentroCosto($documento);
        $userId = $this->obtenerUserId();

        //Datos de la caja activa
        $cajaActiva = $this->obtenerCajaActiva();

        //Informacion del usuario (quien recibe el pago)
        $datosUsuario = $this->obtenerDatosUsuario();

        //Formato especial para la descripcion
        $fechaFormatoEspecial = now()->format('Y-n');
        $descripcion = "CONSULTA CAJASAN " . $fechaFormatoEspecial;

        //Formato numdocumento (fecha - DD.MM.AA)
        $fechaNumDoc = now()->format('d.m.Y');


        //Crear el comprobante auxiliar
        $auxComprobante = new ConAuxComprobantes();
        $auxComprobante->id = $nextId;
        $auxComprobante->cp_id = $idComprobante;
        $auxComprobante->ct_codigo = $numeroCuenta;
        $auxComprobante->libro = null;
        $auxComprobante->referencia1 = $datosUsuario['identificacion'];
        $auxComprobante->referencia2 = null;
        $auxComprobante->referencia3 = null;
        $auxComprobante->basaplicada = null;
        $auxComprobante->descripcion = $descripcion;
        $auxComprobante->digdocumento = null;
        $auxComprobante->estado = 'A';
        $auxComprobante->fecaplica = now()->format('d/m/y H:i:s');
        $auxComprobante->fecmodifica = now()->format('d/m/y H:i:s');
        $auxComprobante->usrmodifica = $userId;
        $auxComprobante->rolmodifica = self::ROL_MODIFICA;
        $auxComprobante->empmodifica = $cajaActiva->idsucursal;
        $auxComprobante->estborrado = 0;
        $auxComprobante->fecelabora = null;
        $auxComprobante->fecvence = null;
        $auxComprobante->placa = null;
        $auxComprobante->valcredito = $tipo == 'C' ? $respuestaApi['additionalData']['saldo'] : 0;
        $auxComprobante->valdebito = $tipo == 'D' ? $respuestaApi['additionalData']['saldo'] : 0;
        $auxComprobante->docnro = null;
        $auxComprobante->docver = null;
        $auxComprobante->docestado = null;
        $auxComprobante->cc_codigo = $centroCosto;
        $auxComprobante->grupo = 1;
        $auxComprobante->numdocumento = $fechaNumDoc;
        $auxComprobante->tercero_id = 10631;
        $auxComprobante->fecdocumento = null;
        $auxComprobante->tm_id = 1132941243;
        $auxComprobante->bloque = 1;
        $auxComprobante->id_detalleimptos = null;
        $auxComprobante->docrep = null;
        $auxComprobante->save();
    }

    private function crearCajaTurnoDoc($idComprobante): void
    {
        //Informacion para crear el registro
        $nextId = TesCajaTurnoDoc::max('id') + 1;
        $respuestaApi = $this->obtenerRespuestaApi();
        $userId = $this->obtenerUserId();

        //Datos de la caja activa
        $cajaActiva = $this->obtenerCajaActiva();

        $cajaTurnoDoc = new TesCajaTurnoDoc();
        $cajaTurnoDoc->id = $nextId;
        $cajaTurnoDoc->ctu_ori_id = $cajaActiva->id;
        $cajaTurnoDoc->ctu_res_id = $cajaActiva->id;
        $cajaTurnoDoc->tm_id = 1132941243;
        $cajaTurnoDoc->fp_id = 2;
        $cajaTurnoDoc->bco_id = null;
        $cajaTurnoDoc->ctb_id = null;
        $cajaTurnoDoc->tipo = 'E';
        $cajaTurnoDoc->valor = $respuestaApi['additionalData']['saldo'];
        $cajaTurnoDoc->estdocumento = 'EC';
        $cajaTurnoDoc->fecdocumento = now()->format('d/m/y H:i:s');
        $cajaTurnoDoc->nrodocumento = now()->format('d/m/y H:i:s');
        $cajaTurnoDoc->fecmodifica = now()->format('d/m/y H:i:s');
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
    }

    private function crearNuevoCarguePagoRecaudos(string $descripcion): int
    {
        $fechaActual = now()->format('d/m/y H:i:s');
        $userId = $this->obtenerUserId();
        $cajaActiva = $this->obtenerCajaActiva();
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
    }

    private function crearDetallePagoRecaudos(int $idCargue): int
    {
        $fechaFormatoEspecial = now()->format('Y-n');
        $concepto = "CONSULTA CAJASAN " . $fechaFormatoEspecial;

        $clienteData = session('clienteData_temp');
        $respuestaApi = session('respuesta_temp');
        
        $identificacion = $clienteData['identificacion'];
        $nombreCompleto = $clienteData['nombre'];
        $saldoTotal = $respuestaApi['additionalData']['saldo'];

        $fechaActual = now()->format('d/m/y H:i:s');

        $cajaActiva = $this->obtenerCajaActiva();
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
        $detalle->fecha_desde = $fechaActual;
        $detalle->fecha_hasta = null;
        $detalle->nro_interno = null;
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
    }

    // Métodos auxiliares reutilizables

    private function obtenerUserId(): ?int
    {
        return PerPersonas::where('identificacion', Auth::user()->persona->PerNumDoc)->value('id');
    }
    
    private function obtenerCajaActiva()
    {
        if (!session()->has('cajaActiva')) {
            throw new Exception('Caja activa no encontrada en sesión');
        }

        return session('cajaActiva');
    }

    private function obtenerRespuestaApi()
    {
        if (!session()->has('respuesta_temp')) {
            throw new Exception('Respuesta de API no encontrada en sesión');
        }

        return session('respuesta_temp');
    }

    private function obtenerDatosUsuario()
    {
        if (!session()->has('clienteData_temp')) {
            throw new Exception('Datos del cliente no encontrados en sesión');
        }

        return session('clienteData_temp');
    }

    private function obtenerNumeroCuenta($tipo)
    {
        // Tipo: D = Débito, C = Crédito
        return DB::connection('oracle')
            ->table('CON_ENLACEDETALLES as D')
            ->where('D.ENL_ID', 1139194416)
            ->where('D.ESTBORRADO', 0)
            ->where('D.AFECTACION', $tipo) 
            ->orderBy('D.GRUPO')
            ->orderByDesc('D.AFECTACION')
            ->orderBy('D.CU_CUENTA')
            ->value('CU_CUENTA'); 
    }

    private function obtenerSiguienteId(string $secuencia): int
    {
        $result = DB::connection('oracle')
            ->select("SELECT {$secuencia}.NEXTVAL as id FROM DUAL");

        return $result[0]->id;
    }
}