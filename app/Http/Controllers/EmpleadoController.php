<?php

namespace App\Http\Controllers;

use App\Mail\CorreoUsuarioTemporal;
use App\Models\GESTIONADMIN\FirmaPreingreso;
use App\Models\GESTIONADMIN\UsuarioTemporal;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\BloqueoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmpleadoController extends Controller
{
  //Modal solicitud nuevo ingreso - gestion empleado
  public function nuevoIngreso()
  {
    return view('empleados.nuevoIngreso');
  }

  public function buscar($identificacion)
  {
    $persona = PerPersonas::where('identificacion', $identificacion)->first();

    if (!$persona) {
      return response()->json(['success' => false]);
    }

    return response()->json([
      'success' => true,
      'data' => [
        'nombres' => $persona->pnombre . ' ' . $persona->snombre,
        'apellidos' => $persona->papellido . ' ' . $persona->sapellido,
        'email' => $persona->dirweb,
      ]
    ]);
  }

  public function gestionNuevoIngreso(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'identificacion' => 'required|numeric|digits_between:5,20',
    ], [
      'identificacion.required' => 'La identificación es obligatoria.',
      'identificacion.numeric'  => 'La identificación debe ser un número.',
      'identificacion.digits_between' => 'La identificación debe tener entre 5 y 20 dígitos.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      //Datos persona
      $persona = PerPersonas::where('identificacion', $request->identificacion)->first();

      if (!$persona) {
        return toastModal("No se encontró una persona con la identificación ingresada", "warning", route('gestion-incapacidades.index'));
      }
      $correo = $persona->dirweb;
      $nombreCompleto = $persona->pnombre . ' ' . $persona->snombre . ' ' . $persona->papellido . ' ' . $persona->sapellido;

      //Crear bloqueo SARLAFT
      $descripcionBloqueo = "REQUIERE FIRMA NORMAS SARLAFT";

      $bloqueo = BloqueoService::crearNovedadEmpleado(
        $request->identificacion,
        $descripcionBloqueo,
        BloqueoService::ID_BLOQUEO_LOGTRANS_SARLAFT
      );

      if (!$bloqueo) {
        return toastModal("Error al registrar la solicitud, intente nuevamente", "error", route('gestion-incapacidades.index'));
      }

      DB::beginTransaction();

      //Crear usuario temporal
      $token = Str::random(40);

      //Crear o actualizar un nuevo registro de usuario temporal
      UsuarioTemporal::updateOrCreate(
        ['identificacion' => $request->identificacion],
        [
          'correo' => $correo,
          'token' => $token,
          'nombreCompleto' => $nombreCompleto,
          'estado' => true,
        ]
      );

      //URL validacion de token
      $baseUrl = config('app.validar_temporal_url');
      $url = $baseUrl . $token;

      //Enviar correo
      Mail::to($correo)->send(new CorreoUsuarioTemporal([
        'nombre' => $nombreCompleto,
        'token' => $token,
        'url' => $url
      ]));

      DB::commit();
      return toastModal("Solicitud creada exitosamente", "success", route('gestion-incapacidades.index'));
    } catch (Exception $e) {
      DB::rollBack();
      Log::error("Error al registrar la solicitud: " . $e->getMessage());
      return toastModal("Error al registrar la solicitud", "error", route('gestion-incapacidades.index'));
    }
  }

  public function reporteFirmaNormas()
  {
    return view('reportes.empleados.firmaNormas');
  }

  public function filtrarFirmaNormas(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'identificacion' => ['nullable', 'numeric'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $columns = [
        DB::raw('Id'),
        DB::raw('Identificacion as identificacion'),
        DB::raw('NombreCompleto as nombre_completo'),
        DB::raw('FirmaIp as firma_ip'),
        DB::raw('Correo as correo'),
        DB::raw('FirFecReg as fecha_registro'),
        DB::raw('FirHorReg as hora_registro')
      ];

      $query = FirmaPreingreso::select($columns);

      if ($request->filled('identificacion')) {
        $query->where("Identificacion", $request->identificacion);
      }

      $listaFirmas = $query->get();

      if ($listaFirmas->isEmpty()) {
        return toastModal("No se encontraron resultados para los parametros ingresados.", "warning", "#");
      } else {
        $params = http_build_query($request->only(['identificacion']));
        return toastModal(
          'Se han encontrado ' . $listaFirmas->count() . ' registros para los parámetros seleccionados',
          "success",
          route("lista.firmaNormas") . "?" . $params
        );
      }
    } catch (Exception $e) {
      Log::error('Error al obtener la lista de firmas preingreso empleados: ' . $e->getMessage());
      return toastModal("Error al obtener los resultados", "error");
    }
  }

  public function listaFirmasNormas()
  {
    return view('reportes.empleados.listaFirmasNormas');
  }

  public function cargarDataFirmaNormas(Request $request)
  {

    $columns = [
      DB::raw('Id'),
      DB::raw('Identificacion as identificacion'),
      DB::raw('NombreCompleto as nombre_completo'),
      DB::raw('Correo as correo'),
      DB::raw('FirmaIp as firma_ip'),
      DB::raw('FirFecReg as fecha_registro'),
      DB::raw('FirHorReg as hora_registro')
    ];

    $query = FirmaPreingreso::select($columns);

    if ($request->filled('identificacion')) {
      $query->where("Identificacion", $request->identificacion);
    }

    return $query->get();
  }

  public function generarComprobantePDF($id)
  {
    try {
      $firmaData = FirmaPreingreso::where('Id', $id)->first();

      if (!$firmaData) {
        return toastModal("No se encontró la firma para la identificación proporcionada.", "error", route('reportes.empleados'));
      }

      $firma = (object) [
        'NomCon'    => $firmaData->NombreCompleto,
        'DocCon'    => $firmaData->Identificacion,
        'FirFecReg' => $firmaData->FirFecReg,
        'FirHorReg' => $firmaData->FirHorReg,
        'Correo'    => $firmaData->Correo,
        'FirmaIp'   => $firmaData->FirmaIp,
        'DepFir'    => $firmaData->DepFir,
        'MunFir'    => $firmaData->MunFir
      ];

      $pdf = PDF::loadView('politicas.plantillasPDF.comprobanteFirmaNormas', compact('firma'));
      return $pdf->stream('comprobante_firma_normas_' . $firmaData->Identificacion . '.pdf');
    } catch (Exception $e) {
      Log::error('Error al generar el comprobante PDF: ' . $e->getMessage());
      return sweetAlert('Error al generar el comprobante PDF', 'error');
    }
  }
}
