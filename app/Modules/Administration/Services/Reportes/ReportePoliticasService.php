<?php

namespace App\Modules\Administration\Services\Reportes;

use App\Modules\Administration\Models\FirmaPoliticas;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReportePoliticasService
{
    /**
     * Obtiene las firmas de políticas filtradas y agrupadas
     */
    public function obtenerFirmas(array $filtros): Collection
    {
        try {
            $fechaInicio = $filtros['fechaInicio'];
            $fechaFin = $filtros['fechaFin'];
            $tipoFiltro = $filtros['tipoFiltro'] ?? 'todos';
            $valorFiltro = $filtros['valorFiltro'] ?? null;

            // Consulta base
            $query = FirmaPoliticas::whereBetween('FirFecReg', [$fechaInicio, $fechaFin])
                ->where('PoliticaId', '!=', 2)
                ->orderBy('FirFecReg', 'desc')
                ->orderBy('FirHorReg', 'desc');

            // Aplicar filtros
            if ($tipoFiltro === 'identificacion') {
                $query->where('DocCon', $valorFiltro);
            } elseif ($tipoFiltro === 'codigo') {
                $query->where('CodCon', $valorFiltro);
            } elseif ($tipoFiltro !== 'todos') {
                throw new Exception('Tipo de filtro no válido');
            }

            $data = $query->get();

            if ($data->isEmpty()) {
                throw new Exception('No se han encontrado registros para las fechas seleccionadas');
            }

            // Agrupar y formatear datos
            return $this->agruparFirmas($data);
        } catch (Exception $e) {
            Log::error('Error al obtener firmas de políticas: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Agrupa y formatea las firmas según la política
     */
    private function agruparFirmas(Collection $data): Collection
    {
        return $data
            ->groupBy(fn ($item) => $item->DocCon.'|'.$item->CodCon)
            ->flatMap(function ($grupo) {
                $politicasEspeciales = $grupo->whereIn('PoliticaId', [1, 3, 5]);
                $otrasPoliticas = $grupo->whereNotIn('PoliticaId', [1, 3, 5]);

                $resultado = collect();

                $formatear = fn ($item, $nombrePolitica) => [
                    'IdFirma' => $item->IdFirma,
                    'Código' => $item->CodCon,
                    'Documento' => $item->DocCon,
                    'Nombre del Empleado' => $item->NomCon,
                    'Fecha de Firma' => $item->FirFecReg.' '.$item->FirHorReg,
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
    }
}
