<?php

namespace App\Http\Controllers;

use App\Models\GESTIONPASAJES\MunicipioPasajes;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GestionPasajesController extends Controller
{
    public function formBuscarViaje(){
        $municipios = MunicipioPasajes::with('departamento')
            ->orderBy('MunNom', 'asc')
            ->get();

        return view('gestionpasajes.formBuscarViaje',compact('municipios'));
    }

    public function filtrarViajes(Request $request){

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

        try{
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

            if($resultados->isEmpty()){
                return toast('No se encontraron viajes para los criterios seleccionados.', 'warning');
            }

            return view('gestionpasajes.resultadoViajes', compact('resultados'));

        }catch(Exception $e){
            Log::error('Error al filtrar los viajes: ' . $e->getMessage());
            return toast('Error al filtrar los viajes', 'danger');
        }
    }

    public function formEsquemaTarifario(){
        $municipios = MunicipioPasajes::with('departamento')
            ->orderBy('MunNom', 'asc')
            ->get();

        return view('reportes.formEsquemaTarifario',compact('municipios'));
    }

    public function filtrarEsquemaTarifario(Request $request){
        try{
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
                ->where('t_o.Nombre', 'like', '%'.$terminalOrigen.'%')
                ->where('t_d.Nombre', 'like', '%'.$terminalDestino.'%') 
                ->orderBy('ett.Fecha_Ini', 'asc')
                ->get();
            
            $numeroResultados = $resultados->count();

            if($numeroResultados === 0){
                return toastModal("No se encontraron resultados para los criterios seleccionados.", "warning");
            }

            session(['esquemaTarifario' => $resultados]);
            return toastModal("Resultados obtenidos: ". $numeroResultados, "success", route('esquemaTarifario.listaDatos')); 
        }catch(Exception $e){
            Log::error('Error al filtrar el esquema tarifario: ' . $e->getMessage());
            return toastModal("Error al filtrar el esquema tarifario", "error"); 
        }
    }

    public function listaEsquemaTarifario(){
        return view('reportes.reporteEsqTarifario');
    }

    public function cargarDataEsquemaTarifario(){
        $esquemas = session('esquemaTarifario') ?? [] ;
        return $esquemas;
    }
}
