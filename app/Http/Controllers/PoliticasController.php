<?php

namespace App\Http\Controllers;

use App\Models\GESTIONPASAJES\ConfigPoliticas;
use App\Models\GESTIONPASAJES\FirmaPoliticas;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PoliticasController extends Controller
{
  //Politicas IDs
  public const ID_POLITICA_EQUIPAJE = 1;
  public const ID_POLITICA_CAMARAS  = 2;
  public const ID_POLITICA_MENORES  = 3;
  public const ID_POLITICA_SARLAFT  = 4;
  public const ID_POLITICA_MASCOTAS = 5;

  public function index()
  {
    return view("politicas.index");
  }

  public function politicasFirmadas(Request $request)
  {

    $identificacion = $request->identificacion;

    //Obtener los ultimos registros de firma para cada politica
    $firmas = DB::connection('mysql-gestion-pasajes')
      ->select("
                SELECT f.*, cp.politica
                FROM _FirConductores f
                INNER JOIN (
                    SELECT PoliticaId, MAX(CONCAT(FirFecReg, ' ', FirHorReg)) AS ultima
                    FROM _FirConductores
                    WHERE DocCon = ?
                    GROUP BY PoliticaId
                ) ultimas
                    ON f.PoliticaId = ultimas.PoliticaId
                AND CONCAT(f.FirFecReg, ' ', f.FirHorReg) = ultimas.ultima
                JOIN config_politicas cp ON cp.id = f.PoliticaId
                WHERE f.DocCon = ?
            ", [$identificacion, $identificacion]);

    if (empty($firmas)) {
      return toastModal("No hay registros de firmas asociados al documento ingresado", "warning");
    }

    return response()->json([
      'success' => true,
      'html' => view('politicas.firmados', [
        'firmas' => $firmas,
      ])->render(),
    ]);
  }

  public function generarPDFPolitica(Request $request)
  {
    $firma = FirmaPoliticas::findOrFail($request->firma_id);

    $politicaId = $firma->PoliticaId;
    $politica = ConfigPoliticas::findOrFail($politicaId);

    //Plantilla pdf camaras
    if ($politicaId == self::ID_POLITICA_CAMARAS) {
      $funcionesCargo = ConductorController::funcionesCargo($firma->Cargo);

      $pdf = Pdf::loadView('politicas.plantillasPDF.camaras', compact('firma', 'funcionesCargo'));
      $pdf->setPaper('A4', 'portrait');
      return $pdf->stream($politica->politica . '-' . $firma->NomCon . '.pdf');
    }

    //Plantilla pdf SARLAFT
    if ($politicaId == self::ID_POLITICA_SARLAFT) {
      $pdf = Pdf::loadView('politicas.plantillasPDF.sarlaft', compact('firma'));
      $pdf->setPaper('A4', 'portrait');
      return $pdf->stream($politica->politica . '-' . $firma->NomCon . '.pdf');
    }

    //Plantilla otras politicas
    $pdf = Pdf::loadView('politicas.plantillasPDF.clausulaCumplimiento', [
      'firma' => $firma,
      'politicaId' => $politicaId,
      'ID_POLITICA_EQUIPAJE' => self::ID_POLITICA_EQUIPAJE,
      'ID_POLITICA_MENORES' => self::ID_POLITICA_MENORES,
      'ID_POLITICA_MASCOTAS' => self::ID_POLITICA_MASCOTAS,
    ]);

    $pdf->setPaper('A4', 'portrait');
    return $pdf->stream($politica->politica . '-' . $firma->NomCon . '.pdf');
  }
}
