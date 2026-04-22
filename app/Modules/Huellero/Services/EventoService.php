<?php

namespace App\Modules\Huellero\Services;

use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Exceptions\PersonNotFoundException;
use App\Modules\Huellero\Models\PerPersonasEventos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventoService
{
    private $huellaService;

    public function __construct(HuellaService $huellaService)
    {
        $this->huellaService = $huellaService;
    }

    /**
     * Registrar evento de entrada/salida automático
     */
    public function registrarEventoAutomatico(int $personaId, string $tipoEvento): PerPersonasEventos
    {
        // Verificar que la persona existe
        $persona = PerPersonas::find($personaId);
        if (! $persona) {
            throw new PersonNotFoundException("Persona con ID {$personaId} no encontrada");
        }

        $evento = $tipoEvento === 'entrada' ? PerPersonasEventos::EVENTO_ENTRADA : PerPersonasEventos::EVENTO_SALIDA;
        $anotacion = $tipoEvento === 'entrada' ? 'Automatico.- ENTRADA' : 'Automatico.- SALIDA';

        return $this->crearEvento($personaId, $evento, PerPersonasEventos::TIPO_AUTOMATICO, $anotacion);
    }

    /**
     * Registrar evento manual por error del sistema
     */
    public function registrarEventoManual(int $personaId, string $tipoEvento, string $razonError, int $usuarioCreacion, ?string $observacionAdicional = null): PerPersonasEventos
    {
        // Verificar que la persona existe
        $persona = PerPersonas::find($personaId);
        if (! $persona) {
            throw new PersonNotFoundException("Persona con ID {$personaId} no encontrada");
        }

        $evento = $tipoEvento === 'entrada' ? PerPersonasEventos::EVENTO_ENTRADA : PerPersonasEventos::EVENTO_SALIDA;
        $anotacion = $tipoEvento === 'entrada' ? 'Manual.- ENTRADA' : 'Manual.- SALIDA';

        // Construir observación basada en la razón del error
        $observacion = 'Registro Manual: '.$this->obtenerMensajeError($razonError);
        if ($observacionAdicional) {
            $observacion .= ' - '.$observacionAdicional;
        }

        return $this->crearEvento($personaId, $evento, PerPersonasEventos::TIPO_MANUAL, $anotacion, $observacion, $usuarioCreacion);
    }

    /**
     * Procesar evento por lectura de huella
     */
    public function procesarEventoPorHuella(string $templateHuella): array
    {
        // Buscar persona por la huella
        $persona = $this->huellaService->buscarPersonaPorHuella($templateHuella);

        if (! $persona) {
            Log::warning('Huella no reconocida en el sistema');

            return [
                'success' => false,
                'message' => 'Huella no reconocida',
                'persona' => null,
                'evento' => null,
            ];
        }

        // Determinar tipo de evento basado en el último registro
        $ultimoEvento = $persona->ultimoEvento();
        $tipoEvento = 'entrada'; // Por defecto entrada

        if ($ultimoEvento && $ultimoEvento->esEntrada()) {
            $tipoEvento = 'salida';
        }

        // Registrar evento automático
        $evento = $this->registrarEventoAutomatico($persona->id, $tipoEvento);

        Log::info('Evento registrado por lectura de huella');

        return [
            'success' => true,
            'message' => "Acceso registrado: {$tipoEvento}",
            'persona' => $persona,
            'evento' => $evento,
        ];
    }

    /**
     * Obtener eventos del día actual
     */
    public function obtenerEventosHoy(): \Illuminate\Database\Eloquent\Collection
    {
        return PerPersonasEventos::with('persona')
            ->activos()
            ->hoy()
            ->orderBy('fechaevento', 'desc')
            ->get();
    }

    /**
     * Obtener eventos de una persona en un rango de fechas
     */
    public function obtenerEventosPersona(int $personaId, ?\Carbon\Carbon $fechaInicio = null, ?\Carbon\Carbon $fechaFin = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = PerPersonasEventos::where('pe_id', $personaId)
            ->activos()
            ->orderBy('fechaevento', 'desc');

        if ($fechaInicio) {
            $query->where('fechaevento', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->where('fechaevento', '<=', $fechaFin);
        }

        return $query->get();
    }

    /**
     * Obtener estadísticas de eventos
     */
    public function obtenerEstadisticas(?\Carbon\Carbon $fecha = null): array
    {
        $fecha = $fecha ?? today();
        $eventosHoy = PerPersonasEventos::activos()
            ->whereDate('fechaevento', $fecha);

        return [
            // 'total_eventos_hoy' => (clone $eventosHoy)->count(),
            'entradas_hoy' => (clone $eventosHoy)->entradas()->count(),
            'salidas_hoy' => (clone $eventosHoy)->salidas()->count(),
            // 'eventos_automaticos' => (clone $eventosHoy)->automaticos()->count(),
            'eventos_manuales' => (clone $eventosHoy)->manuales()->count(),
            // 'personas_actualmente_dentro' => $this->contarPersonasDentro(),
            // 'errores_por_tipo' => $this->obtenerErroresPorTipo($fecha)
        ];
    }

    /**
     * Obtener reporte de asistencia
     */
    public function obtenerReporteAsistencia(\Carbon\Carbon $fechaInicio, \Carbon\Carbon $fechaFin): array
    {
        $eventos = PerPersonasEventos::with('persona')
            ->activos()
            ->whereBetween('fechaevento', [$fechaInicio, $fechaFin])
            ->orderBy('pe_id')
            ->orderBy('fechaevento')
            ->get();

        $reporte = [];

        foreach ($eventos->groupBy('pe_id') as $personaId => $eventosPersona) {
            $persona = $eventosPersona->first()->persona;

            $reporte[] = [
                'persona' => $persona,
                'eventos' => $eventosPersona,
                'total_entradas' => $eventosPersona->where('evento', PerPersonasEventos::EVENTO_ENTRADA)->count(),
                'total_salidas' => $eventosPersona->where('evento', PerPersonasEventos::EVENTO_SALIDA)->count(),
                'errores' => $eventosPersona->where('tiporegistro', PerPersonasEventos::TIPO_MANUAL)->count(),
            ];
        }

        return $reporte;
    }

    /**
     * Crear un nuevo evento
     */
    private function crearEvento(int $personaId, string $evento, int $tipoRegistro, string $anotacion, ?string $observacion = null, ?int $usuarioCreacion = null): PerPersonasEventos
    {
        try {
            DB::beginTransaction();

            // Generar ID único para Oracle
            $nuevoId = $this->generarNuevoId();

            $eventoModel = new PerPersonasEventos([
                'id' => $nuevoId,
                'pe_id' => $personaId,
                'fechaevento' => now(),
                'evento' => $evento,
                'tiporegistro' => $tipoRegistro,
                'anotacion' => $anotacion,
                'observacion' => $observacion,
                'feccreacion' => now(),
                'usrcreacion' => $usuarioCreacion ?? 0,
                'empcreacion' => $usuarioCreacion ?? 0,
                'estborrado' => 0,
            ]);

            $eventoModel->save();

            DB::commit();

            Log::info('Evento creado exitosamente');

            return $eventoModel;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear evento', [
                'evento' => $evento,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtener mensaje de error según la razón
     */
    private function obtenerMensajeError(string $razonError): string
    {
        $mensajes = [
            'sistema_no_disponible' => 'no hubo sistema',
            'error_lectura_huella' => 'huellero no marco',
            'error_lector' => 'ERROR EN EL LECTOR',
            'error_comunicacion' => 'falla de comunicacion con el servidor',
            'huella_no_leida' => 'NO LEYO HUELLA',
            'falla_sistema' => 'se presenta fallas en el sistema',
        ];

        return $mensajes[$razonError] ?? 'error de sistema';
    }

    /**
     * Contar personas actualmente dentro
     */
    private function contarPersonasDentro(): int
    {
        // Obtener último evento de cada persona
        $year = date('Y');
        $ultimosEventos = PerPersonasEventos::select('pe_id', 'evento')
            ->activos()
            ->whereIn('id', function ($query) use ($year) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('PER_PERSONASEVENTOS')
                    ->where('estborrado', 0)
                    ->whereRaw("FECHAEVENTO >= DATE '".($year)."-01-01'")
                    ->whereRaw("FECHAEVENTO <  DATE '".($year)."-12-31'")
                    ->groupBy('pe_id');
            })
            ->where('evento', PerPersonasEventos::EVENTO_ENTRADA)
            ->count();

        return $ultimosEventos;
    }

    /**
     * Obtener errores por tipo
     */
    private function obtenerErroresPorTipo(\Carbon\Carbon $fecha): array
    {
        $errores = PerPersonasEventos::activos()
            ->manuales()
            ->whereDate('fechaevento', $fecha)
            ->get();

        $tiposError = [];

        foreach ($errores as $error) {
            $tipoError = $error->mensaje_error ?? 'Error no clasificado';
            $tiposError[$tipoError] = ($tiposError[$tipoError] ?? 0) + 1;
        }

        return $tiposError;
    }

    /**
     * Generar nuevo ID para Oracle
     */
    private function generarNuevoId(): int
    {
        $maxId = PerPersonasEventos::max('id') ?? 0;

        return $maxId + 1;
    }
}
