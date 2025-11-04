<?php


namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\GESTIONPASAJES\MunicipioPasajes;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GestionPasajesController extends Controller
{
  public function formBuscarViaje()
  {
    $municipios = MunicipioPasajes::with('departamento')
      ->orderBy('MunNom', 'asc')
      ->get();

    return view('gestionpasajes.formBuscarViaje', compact('municipios'));
  }

  public function filtrarViajes(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'origen' => 'required',
      'destino' => 'required|different:origen',
    ], [
      'destino.different' => 'El origen y el destino deben ser diferentes.',
      'origen.required' => 'El origen es obligatorio.',
      'destino.required' => 'El destino es obligatorio.',
    ]);

    if ($validator->fails()) {
      return toast($validator->errors()->first(), 'danger');
    }

    try {
      $origen = $request->origen;
      $destino = $request->destino;

      $resultados = DB::connection('sqlsrv')
        ->table('esquemastarifariosTarifas as T')
        ->join('PASAJES as P', 'P.EsquemaTarifarioTarifa', '=', 'T.Id')
        ->join('Terminales as T_O', 'T_O.ID', '=', 'T.TerminalOrigen')
        ->join('Terminales as T_D', 'T_D.ID', '=', 'T.TerminalDestino')
        ->join('CategoriasServicios as C', 'C.Id', '=', 'T.Categoria')
        ->join('VIAJES as V', 'V.ID', '=', 'P.Viaje')
        ->whereBetween('V.FechaPartida', [
          now()->format('d/m/Y H:i:s'),
          now()->addDays(2)->format('d/m/Y H:i:s')
        ])
        ->whereIn('T.TerminalOrigen', function ($query) use ($origen) {
          $query->select('id')
            ->from('Terminales')
            ->where('Nombre', 'LIKE', '%' . $origen . '%');
        })
        ->whereIn('T.TerminalDestino', function ($query) use ($destino) {
          $query->select('id')
            ->from('Terminales')
            ->where('Nombre', 'LIKE', '%' . $destino . '%');
        })
        ->where('T.EsquemaTarifario', 1)
        ->where('V.estado', 0)
        ->groupBy(
          'T.Precio_OneWay',
          'v.id',
          'T_O.Nombre',
          'T_D.NOMBRE',
          'C.Nombre',
          'V.FechaPartida'
        )
        ->orderBy('V.FechaPartida', 'asc')
        ->select(
          'v.id as Viaje',
          'T_O.Nombre as TerminalOrigen',
          'T_D.NOMBRE as TerminalDestino',
          'C.Nombre as Servicio',
          'V.FechaPartida as FechaPartida',
          'T.Precio_OneWay as Precio'
        )
        ->get();

      if ($resultados->isEmpty()) {
        return toast('No se encontraron viajes para los criterios seleccionados.', 'warning');
      }

      return view('gestionpasajes.resultadoViajes', compact('resultados'));
    } catch (Exception $e) {
      Log::error('Error al filtrar los viajes: ' . $e->getMessage());
      return toast('Error al filtrar los viajes', 'danger');
    }
  }

  public function formEsquemaTarifario()
  {
    $municipios = MunicipioPasajes::with('departamento')
      ->orderBy('MunNom', 'asc')
      ->get();

    return view('reportes.pasajes.formEsquemaTarifario', compact('municipios'));
  }

  public function filtrarEsquemaTarifario(Request $request)
  {
    try {
      $fechaIni = Carbon::parse($request->fechaInicio)->format('d/m/Y H:i:s');
      $fechaFin = Carbon::parse($request->fechaFin)->format('d/m/Y H:i:s');
      $terminalOrigen = $request->origen;
      $terminalDestino = $request->destino;

      $resultados = DB::connection('sqlsrv')
        ->table('EsquemasTarifariosTarifas as ett')
        ->join('Terminales as t_o', 't_o.Id', '=', 'ett.TerminalOrigen')
        ->join('Terminales as t_d', 't_d.Id', '=', 'ett.TerminalDestino')
        ->join('CategoriasServicios as cs', 'cs.Id', '=', 'ett.Categoria')
        ->select(
          'ett.id',
          't_o.Nombre as origen',
          't_d.Nombre as destino',
          'cs.Nombre as servicio',
          'ett.Precio_OneWay as precio',
          DB::raw('CONVERT(DATE, ett.Fecha_Ini) as [fechaInicial]'),
          DB::raw('CONVERT(DATE, ett.Fecha_Fin) as [fechaFinal]'),
          'ett.Estado as estado',
        )
        ->whereBetween(DB::raw('CONVERT(DATE, ett.Fecha_Ini)'), [$fechaIni, DB::raw('GETDATE()')])
        ->where(DB::raw('CONVERT(DATE, ett.Fecha_Fin)'), '<=', $fechaFin)
        ->where('ett.Estado', '0')
        ->whereNotIn('ett.Categoria', ['1', '2', '3', '7', '8', '9', '14'])
        ->where('ett.EsquemaTarifario', '1')
        ->where('t_o.Nombre', 'like', '%' . $terminalOrigen . '%')
        ->where('t_d.Nombre', 'like', '%' . $terminalDestino . '%')
        ->orderBy('ett.Fecha_Ini', 'asc')
        ->get();

      $numeroResultados = $resultados->count();

      if ($numeroResultados === 0) {
        return toastModal("No se encontraron resultados para los criterios seleccionados.", "warning");
      }

      session(['esquemaTarifario' => $resultados]);
      return toastModal("Resultados obtenidos: " . $numeroResultados, "success", route('esquemaTarifario.listaDatos'));
    } catch (Exception $e) {
      Log::error('Error al filtrar el esquema tarifario: ' . $e->getMessage());
      return toastModal("Error al filtrar el esquema tarifario", "error");
    }
  }

  public function listaEsquemaTarifario()
  {
    return view('reportes.pasajes.esquemaTarifario');
  }

  public function cargarDataEsquemaTarifario()
  {
    $esquemas = session('esquemaTarifario') ?? [];
    return $esquemas;
  }

  public function imprimirTiquetes($id)
  {
    try {
      $tiquete = DB::connection('sqlsrv')->selectOne("
      SELECT
          /*Información del Cliente*/
          COALESCE(ec.Nombre, CONCAT(p.Nombres, ' ', p.Apellido)) AS clienteEmpresa,
          COALESCE(pdpemp.Codigo, pdpas.Codigo) AS tipoDocumentoCliente,
          COALESCE(ec.CUIT, p.Documento) AS numeroDocumentoCliente,
          COALESCE(ec.Telefono, p.Telefonos) AS telefonoCliente,
          LOWER(COALESCE(ec.Email, p.Email)) AS correoCliente,

          /*Información del Pasajero*/
          CONCAT(p.Nombres, ' ', p.Apellido) AS pasajero,
          pdpas.Codigo AS tipoDocumentoPasajero,
          p.Documento AS numeroDocumentoPasajero,
          p.Telefonos AS telefonoPasajero,
          LOWER(p.Email) AS correoPasajero,

          /*Información del Pasaje*/
          tpjo.Nombre AS origenTiquete,
          tpjd.Nombre AS destinoTiquete,
          CASE
              WHEN vb.Butaca is null THEN 'sin puesto asignado'
              ELSE vb.Butaca
          END AS puestoTiquete,
          CASE
              WHEN v.FechaPartida is null THEN 'Sin Viaje Asignado'
              ELSE FORMAT(v.FechaPartida, 'dd/MM/yyyy')
          END AS fechaViaje,
          CASE
              WHEN v.FechaPartida is null THEN  'Sin Viaje Asignado'
              ELSE FORMAT(v.FechaPartida, 'hh:mm tt')
          END AS horaViaje2,
          CASE
              WHEN (SELECT FORMAT(vr.FechaPartida, 'HH:mm') AS horaViaje FROM ViajesRecorridos as vr WHERE vr.Viaje=pj.Viaje and vr.Terminal =tpjo.Id) IS NULL THEN 'Sin Viaje Asignado'
              ELSE (SELECT FORMAT(vr.FechaPartida, 'HH:mm') AS horaViaje FROM ViajesRecorridos as vr WHERE vr.Viaje=pj.Viaje and vr.Terminal =tpjo.Id)
          END AS horaViaje,
          CASE
              WHEN vto.Nombre IS NULL THEN 'Sin Viaje Asignado'
              ELSE vto.Nombre
          END AS 'ORIGENVIAJE',
          CASE
              WHEN vtd.Nombre IS NULL THEN 'Sin Viaje Asignado'
              ELSE vtd.Nombre
          END AS 'DESTINOVIAJE',
          FORMAT(po.FechaOperacion, 'dd/MM/yyyy') AS fechaExpedicionTiquete,
          FORMAT(po.FechaOperacion, 'hh:mm tt') AS horaExpedicionTiquete,
          pj.Numero AS numeroTiquete,
          pj.ImporteBase AS valorTiquete,
          pj.ImporteDescuentos AS descuentoTiquete,
          pj.ImporteFinal AS valorTotal,
          b.Nombre AS nombreBoleteria,
          /*Bus*/
          CASE
              WHEN co.Nombre IS NULL THEN 'Sin Viaje Asignado'
              ELSE REPLACE(co.Nombre, 'Int ', '')
          END AS internoBus,
          CASE
              WHEN co.Matricula IS NULL THEN 'Sin Viaje Asignado'
              ELSE UPPER(co.Matricula)
          END AS placaBus,
          CASE
              WHEN cs.NombreCorto IN ('PR', 'PRL') THEN 'Preferencial de Lujo'
              WHEN cs.NombreCorto = 'SPR' THEN 'Basico Sprinter'
              WHEN cs.NombreCorto = 'BUS' THEN 'Lujo Busetón'
              WHEN cs.NombreCorto = 'VAN' THEN 'Van'
              WHEN cs.NombreCorto IN ('DP') THEN 'Lujo Doble Piso'
              WHEN cs.NombreCorto IN ('DP+') THEN 'Lujo Doble Piso +'
              WHEN cs.NombreCorto = 'PR+' THEN 'Lujo Preferencial Pantallas'
              WHEN cs.NombreCorto = 'EXP' THEN 'EXPRESO'
              ELSE 'Otro'
          END AS servicioBus,

          /*Pagos*/
          CASE
              WHEN po.MedioPago = 4 THEN 'Crédito'
              WHEN po.MedioPago IN (6, 8, 9, 10, 22, 26, 32) THEN 'Tarjeta de crédito'
              WHEN po.MedioPago IN (7, 11) THEN 'Tarjeta débito'
              ELSE m.Nombre
          END AS formaPagoTiquete,
          CASE
              WHEN po.MedioPago = 4 THEN '30 días'
              WHEN po.MedioPago IN (1, 6, 7, 8, 9, 10, 11, 21, 22, 23, 25, 26, 28, 36) THEN 'No aplica'
              ELSE m.Nombre
          END AS plazoTiquete,
          m.Nombre AS medioPago,

          /*Identificadores adicionales*/
          pj.id AS idPasaje,
          pj.Viaje AS idViaje,
          pj.Persona AS persona
      FROM
          Pasajes AS pj WITH(NOLOCK)
          INNER JOIN PasajesOperaciones AS po WITH(NOLOCK) ON po.pasajenumero = pj.Numero
          INNER JOIN MediosPago m WITH(NOLOCK) ON m.Id = po.MedioPago
          INNER JOIN Boleterias AS b WITH(NOLOCK) ON b.Id = po.Boleteria
          INNER JOIN Terminales AS tpjo WITH(NOLOCK) ON tpjo.Id = pj.TerminalOrigen
          INNER JOIN Terminales AS tpjd WITH(NOLOCK) ON tpjd.Id = pj.TerminalDestino
          INNER JOIN Personas AS p WITH(NOLOCK) ON p.Id = pj.Persona
          INNER JOIN G_PaisesDocumentos AS pdpas WITH(NOLOCK) ON pdpas.PaisDocumentoID = p.DocumentoTipo
          LEFT JOIN EmpresasClientesPasajesOperaciones AS ecp WITH(NOLOCK) ON ecp.PasajeID = pj.Id
          LEFT JOIN EmpresasClientes AS ec WITH(NOLOCK) ON ec.EmpresaID = ecp.EmpresaClienteID
          LEFT JOIN G_PaisesDocumentos AS pdpemp WITH(NOLOCK) ON pdpemp.PaisDocumentoID = ec.PaisDocumentoId
          LEFT JOIN ViajesButacas AS vb WITH(NOLOCK) ON vb.PasajeNumero = pj.Numero
          LEFT JOIN Viajes AS v WITH(NOLOCK) ON v.id = pj.Viaje
          LEFT JOIN Terminales AS vto WITH(NOLOCK) ON vto.Id=v.TerminalOrigen
          LEFT JOIN Terminales AS vtd WITH(NOLOCK) ON vtd.Id=v.TerminalDestino
          LEFT JOIN CategoriasServicios cs  WITH(NOLOCK)  ON cs.Id = v.Categoria
          LEFT JOIN Coches co  WITH(NOLOCK)  ON co.Id = v.Coche
      WHERE
          po.Operacion = 0
          AND pj.Numero = '{$id}';
        ");

      if (empty($tiquete)) {
        return response("No se encontraron tiquetes para el ID proporcionado: {$id}", 404);
      }

      $seguroTiquete = DB::connection('sqlsrv')->selectOne("
        SELECT
            SUM(CASE
                WHEN pjoseg.ETConceptoOp = 4 THEN convert(int,pjoseg.ImporteOperacion)
                ELSE 0.0
            END) 'seguroviaje',
            SUM(CASE
                WHEN pjoseg.ETConceptoOp = 6 THEN pjoseg.ImporteOperacion
                ELSE 0.0
            END) 'estampilla'
        FROM
            PasajesOperaciones AS pjoseg WITH (NOLOCK)
      WHERE pjoseg.PasajeNumero = '{$id}';
      ");

      $cufe = DB::connection('sqlsrv')->selectOne("
       SELECT TOP 1
          PS.Id,
          PS.Numero,
          ps.Viaje,
          TDR.NumeroResolucion AS 'CUFE',
          td.Numero as 'NumeroFactura',
          tdp.DocumentoID,
          tdp.PasajeID
      FROM
          TAX_DocumentosResoluciones as TDR WITH (NOLOCK)
          LEFT JOIN TAX_DOCUMENTOS as TD WITH (NOLOCK) on td.DocumentoID=TDR.DocumentoID
          LEFT JOIN TAX_DocumentosPasajes as TDP WITH (NOLOCK) on TDP.DocumentoID=TDR.DocumentoID
          LEFT JOIN Pasajes as PS WITH (NOLOCK) on PS.Id=TDP.PasajeID
          LEFT JOIN Personas AS PE WITH (NOLOCK) on PE.Id=PS.Persona
      WHERE
          TD.DocumentoTipoID = 3 and
          PS.Numero = '{$id}'
          ORDER BY TD.Fecha DESC;
      ");

      // Generar contenido de QR
      $urlDian = "https://catalogo-vpfe.dian.gov.co/User/SearchDocument?DocumentKey=";
      $contenidoQr = $urlDian . ($cufe->CUFE ?? '');

      // Generar QR con Endroid
      $qrCode = QrCode::create($contenidoQr)
        ->setSize(150)
        ->setMargin(10);

      $writer = new PngWriter();
      $result = $writer->write($qrCode);

      // Convertir a base64 para usar en PDF
      $qrDataUri = $result->getDataUri();

      // Hora y fecha actual
      $fechaHoy = Carbon::now()->format('d/m/Y');
      $horaHoy  = Carbon::now()->format('H:i');

      // Generar PDF
      $pdf = Pdf::loadView('gestionpasajes.tiquetePDF', compact(
        'tiquete',
        'seguroTiquete',
        'cufe',
        'qrDataUri',
        'fechaHoy',
        'horaHoy'
      ));

      $pdf->setPaper('A4', 'portrait')
        ->setOption('isRemoteEnabled', true);

      return $pdf->stream('Tiquete-' . $id . '.pdf');
    } catch (Exception $e) {
      Log::error('Error al imprimir tiquetes para el viaje ID ' . $id . ': ' . $e->getMessage());
      return response("Error al imprimir tiquetes para el ID proporcionado: {$id}", 500);
    }
  }
}
