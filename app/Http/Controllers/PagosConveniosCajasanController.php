<?php

namespace App\Http\Controllers;

use App\Models\LOGTRANS\ConDetPagosRecaudos;
use App\Models\LOGTRANS\ConPagosRecaudos;
use App\Models\LOGTRANS\ConReversoCajasan;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\ApiAsopagos;
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
        $idsucursal = $cajaActiva[0]->idsucursal;
        session()->put('idsucursal', $idsucursal);

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

            // Limpiar sesión
            $this->limpiarDatosSesion();

            return toast('Pago realizado con éxito', 'success',route('pagosConvenios.index'));

        } catch (Exception $e) {
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
        // Respuesta de prueba (comentar cuando uses la API real)
        return [
            'responseCode' => true,
            'additionalData' => [
                'saldo' => 130000
            ],
        ];

        // Descomenta para usar la API real:
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

        // Respuesta de prueba (comentar cuando uses la API real)
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
        session()->forget(['clienteData_temp', 'respuesta_temp']);
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

    private function crearNuevoCarguePagoRecaudos(string $descripcion): int
    {
        $fechaActual = now()->format('d/m/y H:i:s');
        $userId = $this->obtenerUserId();
        $idsucursal = $this->obtenerIdSucursal();

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
        $cargue->empmodifica = $idsucursal;
        $cargue->usrmodifica = $userId;
        $cargue->rolmodifica = self::ROL_MODIFICA;
        $cargue->feccreacion = $fechaActual;
        $cargue->usrcreacion = $userId;
        $cargue->empcreacion = $idsucursal;

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
        $idsucursal = $this->obtenerIdSucursal();
        $sucursal = PerPersonas::findOrFail($idsucursal);
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
        $detalle->empmodifica = $idsucursal;
        $detalle->usrmodifica = $userId;
        $detalle->rolmodifica = self::ROL_MODIFICA;
        $detalle->feccreacion = $fechaActual;
        $detalle->usrcreacion = $userId;
        $detalle->empcreacion = $idsucursal;
        
        $detalle->save();

        session()->forget('idsucursal');
        return $detalle->id;
    }

    // Métodos auxiliares reutilizables

    private function obtenerUserId(): ?int
    {
        return PerPersonas::where('identificacion', Auth::user()->persona->PerNumDoc)->value('id');
    }

    private function obtenerIdSucursal(): int
    {
        if (!session()->has('idsucursal')) {
            throw new Exception('ID de sucursal no encontrado en sesión');
        }

        $idsucursal = session('idsucursal');
        
        return $idsucursal;
    }

    private function obtenerSiguienteId(string $secuencia): int
    {
        $result = DB::connection('oracle')
            ->select("SELECT {$secuencia}.NEXTVAL as id FROM DUAL");

        return $result[0]->id;
    }
}