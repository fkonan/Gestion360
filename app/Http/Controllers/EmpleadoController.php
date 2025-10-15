<?php

namespace App\Http\Controllers;

use App\Mail\CorreoUsuarioTemporal;
use App\Models\GESTIONADMIN\FirmaPreingreso;
use App\Models\GESTIONADMIN\UsuarioTemporal;
use App\Models\GESTIONPASAJES\FirmaPoliticas;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\BloqueoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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

  public function reporteFirmaPoliticas()
  {
    return view('reportes.empleados.firmaPoliticas');
  }

  public function filtrarfirmaPoliticas(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'fechaInicio' => 'date',
      'fechaFin' => 'date|after_or_equal:fechaInicio',
    ], [
      'fechaInicio.date' => 'La fecha de inicio debe ser una fecha válida.',
      'fechaFin.date' => 'La fecha de fin debe ser una fecha válida.',
      'fechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      $fechaInicio = $request->fechaInicio;
      $fechaFin = $request->fechaFin;

      // Consulta base
      $query = FirmaPoliticas::whereBetween('FirFecReg', [$fechaInicio, $fechaFin])
        ->where('PoliticaId', '!=', 2)
        ->orderBy('FirFecReg', 'desc')
        ->orderBy('FirHorReg', 'desc');

      // Filtros
      if ($request->tipoFiltro === 'identificacion') {
        $query->where('DocCon', $request->valorFiltro);
      } elseif ($request->tipoFiltro === 'codigo') {
        $query->where('CodCon', $request->valorFiltro);
      } elseif ($request->tipoFiltro !== 'todos') {
        return toastModal("Tipo de filtro no válido", "error");
      }

      $data = $query->get();

      if ($data->isEmpty()) {
        return toastModal("No se han encontrado registros para las fechas seleccionadas", "warning");
      }

      // Agrupación y mapeo
      $agrupado = $data
        ->groupBy(fn($item) => $item->DocCon . '|' . $item->CodCon)
        ->flatMap(function ($grupo) {
          $politicasEspeciales = $grupo->whereIn('PoliticaId', [1, 3, 5]);
          $otrasPoliticas = $grupo->whereNotIn('PoliticaId', [1, 3, 5]);

          $resultado = collect();

          // Función para estructurar el formato de salida
          $formatear = fn($item, $nombrePolitica) => [
            'IdFirma' => $item->IdFirma,
            'Código' => $item->CodCon,
            'Documento' => $item->DocCon,
            'Nombre del Empleado' => $item->NomCon,
            'Fecha de Firma' => $item->FirFecReg . ' ' . $item->FirHorReg,
            'Cargo' => $item->Cargo,
            'Correo Electrónico' => $item->Correo,
            'Nombre Política' => $nombrePolitica,
          ];

          if ($politicasEspeciales->isNotEmpty()) {
            $primero = $politicasEspeciales->sortByDesc('FirFecReg')->first();
            $nombreAgrupado = $politicasEspeciales->pluck('nombre_politica')->unique()->join(', ');
            $resultado->push($formatear($primero, $nombreAgrupado));
          }

          foreach ($otrasPoliticas as $item) {
            $resultado->push($formatear($item, $item->nombre_politica));
          }

          return $resultado;
        });

      session(['firmas' => $agrupado]);

      return toastModal(
        "Se han encontrado {$agrupado->count()} registros para las fechas seleccionadas",
        "success",
        route('lista.firmaPoliticas')
      );
    } catch (Exception $e) {
      Log::error('Error al obtener la lista de firmas de empleados: ' . $e->getMessage());
      return toastModal("Error al obtener los resultados", "error");
    }
  }


  public function listaFirmasPoliticas()
  {
    return view('reportes.empleados.listaFirmasPoliticas');
  }

  public function cargarDataFirmaPoliticas()
  {
    $firmas = session('firmas') ?? [];
    return $firmas;
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
