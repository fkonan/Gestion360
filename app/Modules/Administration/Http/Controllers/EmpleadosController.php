<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmpleadosController extends Controller
{
    public function index()
    {
        return view('administration::empleados.index');
    }

    public function novedades(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'persona' => 'nullable|string|max:120',
            'solo_activas' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('empleados.novedades')
                ->withErrors($validator)
                ->withInput();
        }

        $filtros = [
            'persona' => trim((string) $request->query('persona', '')),
            'solo_activas' => (string) $request->query('solo_activas', '0') === '1',
        ];

        $ahora = now();

        $novedadesQuery = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES as n')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->leftJoin('PRS_PERSONAS as p', 'p.numero_documento', '=', 'n.id_persona')
            ->select([
                'n.id',
                'n.id_persona',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.estado',
                'n.observacion',
                't.descripcion as tipo_novedad',
                'p.nombres as persona_nombres',
                'p.primer_apellido as persona_primer_apellido',
                'p.segundo_apellido as persona_segundo_apellido',
            ]);

        if ($filtros['persona'] !== '') {
            $textoPersona = $filtros['persona'];
            $textoPersonaUpper = mb_strtoupper($textoPersona, 'UTF-8');

            $novedadesQuery->where(function ($query) use ($textoPersona, $textoPersonaUpper) {
                $query->where('n.id_persona', 'like', "%{$textoPersona}%")
                    ->orWhereRaw(
                        "UPPER(TRIM(NVL(p.nombres, '') || ' ' || NVL(p.primer_apellido, '') || ' ' || NVL(p.segundo_apellido, ''))) LIKE ?",
                        ['%'.$textoPersonaUpper.'%']
                    );
            });
        }

        if ($filtros['solo_activas']) {
            $novedadesQuery
                ->where('n.estado', 'APROBADO')
                ->where('n.fecha_inicio', '<=', $ahora)
                ->where(function ($query) use ($ahora) {
                    $query->whereNull('n.fecha_fin')
                        ->orWhere('n.fecha_fin', '>=', $ahora);
                });
        }

        $novedades = $novedadesQuery
            ->orderByDesc('n.fecha_inicio')
            ->paginate(25)
            ->withQueryString();

        $novedades->getCollection()->transform(function ($novedad) {
            $novedad->persona_nombre = $this->construirNombrePersona(
                (string) ($novedad->persona_nombres ?? ''),
                (string) ($novedad->persona_primer_apellido ?? ''),
                (string) ($novedad->persona_segundo_apellido ?? '')
            );

            $novedad->inicio_fecha = $this->formatearFechaSolo($novedad->fecha_inicio);
            $novedad->inicio_hora = $this->formatearHora12DesdeValor($novedad->fecha_inicio);
            $novedad->fin_fecha = $this->formatearFechaSolo($novedad->fecha_fin);
            $novedad->fin_hora = $this->formatearHora12DesdeValor($novedad->fecha_fin);
            $novedad->hora_inicio_24 = $this->formatearHora24DesdeValor($novedad->fecha_inicio);
            $novedad->hora_fin_24 = $this->formatearHora24DesdeValor($novedad->fecha_fin);

            return $novedad;
        });

        return view('administration::empleados.novedades', compact(
            'novedades',
            'filtros'
        ));
    }

    public function actualizarHoras(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
        ], [
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio no tiene formato válido.',
            'hora_fin.date_format' => 'La hora de fin no tiene formato válido.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $novedad = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES')
            ->select('id', 'fecha_inicio', 'fecha_fin')
            ->where('id', $id)
            ->first();

        if (! $novedad) {
            return redirect()->back()->with('error', 'No se encontró la novedad a actualizar.');
        }

        try {
            $fechaInicio = Carbon::parse($novedad->fecha_inicio);
            $fechaInicio->setTimeFromTimeString((string) $request->input('hora_inicio'));

            $datosUpdate = [
                'fecha_inicio' => $fechaInicio,
                'fecha_modifica' => now(),
            ];

            $horaFinInput = trim((string) $request->input('hora_fin', ''));
            if ($horaFinInput !== '') {
                $fechaFinBase = $novedad->fecha_fin ? Carbon::parse($novedad->fecha_fin) : $fechaInicio->copy();
                $fechaFinBase->setTimeFromTimeString($horaFinInput);
                $datosUpdate['fecha_fin'] = $fechaFinBase;
            }

            $usuarioModifica = $request->user()?->persona?->PerNumDoc
                ?? $request->user()?->IdUsuario
                ?? null;
            if ($usuarioModifica !== null) {
                $datosUpdate['usuario_modifica'] = (string) $usuarioModifica;
            }

            DB::connection('oracle-360')
                ->table('EMP_NOVEDADES')
                ->where('id', $id)
                ->update($datosUpdate);

            return redirect()->back()->with('success', 'Horas de la novedad actualizadas correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error actualizando horas de novedad de empleado', [
                'novedad_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'No fue posible actualizar las horas de la novedad.');
        }
    }

    private function construirNombrePersona(string $nombres, string $primerApellido, string $segundoApellido): string
    {
        $nombreCompleto = trim(collect([$nombres, $primerApellido, $segundoApellido])
            ->filter(fn ($valor) => trim($valor) !== '')
            ->implode(' '));

        return $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre';
    }

    private function formatearFecha(mixed $fecha): string
    {
        if (! $fecha) {
            return 'N/A';
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return (string) $fecha;
        }
    }

    private function formatearFechaSolo(mixed $fecha): string
    {
        try {
            return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    private function formatearHora12DesdeValor(mixed $fecha): string
    {
        try {
            return $fecha ? Carbon::parse($fecha)->format('h:i A') : 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    private function formatearHora24DesdeValor(mixed $fecha): string
    {
        try {
            return $fecha ? Carbon::parse($fecha)->format('H:i') : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
