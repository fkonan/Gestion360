<?php

namespace App\Modules\SIG\Services;

use App\Models\GESTIONADMIN\SIG\DocumentosVersiones;
use App\Models\GESTIONADMIN\SIG\Ubicaciones;
use Illuminate\Support\Collection;

class EmisionesService
{
  public function obtenerDatosFormularioNuevaEmision(int $documentoId): array
  {
    $ubicacionesElabora = $this->obtenerUbicacionesPorTipo('E');
    $ubicacionesRevisa = $this->obtenerUbicacionesPorTipo('R');
    $ubicacionesAprueba = $this->obtenerUbicacionesPorTipo('A');

    $proximaVersion = DocumentosVersiones::where('documento_id', $documentoId)
      ->whereNotNull('version')
      ->max('version');
    $proximaVersion = $proximaVersion ? ($proximaVersion + 1) : 1;

    $ultimaAprobada = DocumentosVersiones::where('documento_id', $documentoId)
      ->where('estado', 'APROBADO')
      ->orderByDesc('version')
      ->first();

    return [
      'ubicacionesElabora' => $ubicacionesElabora,
      'ubicacionesRevisa' => $ubicacionesRevisa,
      'ubicacionesAprueba' => $ubicacionesAprueba,
      'proximaVersion' => $proximaVersion,
      'ultimaAprobada' => $ultimaAprobada,
    ];
  }

  public function obtenerUbicacionesFormulario(): array
  {
    return [
      'ubicacionesElabora' => $this->obtenerUbicacionesPorTipo('E'),
      'ubicacionesRevisa' => $this->obtenerUbicacionesPorTipo('R'),
      'ubicacionesAprueba' => $this->obtenerUbicacionesPorTipo('A'),
    ];
  }

  public function obtenerEmisionesDocumento(int $documentoId): Collection
  {
    return DocumentosVersiones::where('documento_id', $documentoId)
      ->whereIn('estado', ['APROBADO', 'HISTORICO'])
      ->orderByDesc('version')
      ->orderByDesc('id')
      ->get([
        'id',
        'version',
        'comentario_revision',
        'id_elabora',
        'id_revisa',
        'id_aprueba',
        'fecha_aprobacion',
        'estado',
        'fecha_elaboracion'
      ]);
  }

  public function obtenerUbicacionesEmisiones(Collection $versiones): Collection
  {
    $ids = $versiones->pluck('id_elabora')
      ->merge($versiones->pluck('id_revisa'))
      ->merge($versiones->pluck('id_aprueba'))
      ->filter()
      ->map(fn ($id) => trim((string) $id))
      ->filter()
      ->unique()
      ->all();

    return $ids
      ? Ubicaciones::whereIn('id', $ids)->get(['id', 'nombre'])->keyBy('id')
      : collect();
  }

  public function mapearEmisionesConNombres(Collection $versiones, Collection $ubicaciones): Collection
  {
    return $versiones->map(function ($version) use ($ubicaciones) {
      $version->elaboro_nombre = optional($ubicaciones->get(trim((string) $version->id_elabora)))->nombre;
      $version->reviso_nombre = optional($ubicaciones->get(trim((string) $version->id_revisa)))->nombre;
      $version->aprueba_nombre = optional($ubicaciones->get(trim((string) $version->id_aprueba)))->nombre;
      return $version;
    });
  }

  public function obtenerEmisionesPendientes(): Collection
  {
    $ultimosIds = DocumentosVersiones::orderByDesc('id')
      ->get(['id', 'documento_id', 'estado'])
      ->unique('documento_id')
      ->filter(fn ($v) => $v->estado === 'EN_REVISION')
      ->pluck('id');

    return DocumentosVersiones::with([
      'documento' => function ($query) {
        $query->select('id', 'codigo', 'nombre');
      }
    ])
      ->whereIn('id', $ultimosIds)
      ->orderByDesc('fecha_elaboracion')
      ->get(['id', 'documento_id', 'version', 'comentario_revision', 'id_elabora', 'id_revisa', 'id_aprueba', 'fecha_elaboracion', 'estado']);
  }

  public function mapearPendientes(Collection $versiones, Collection $ubicaciones): Collection
  {
    return $versiones->map(function ($version) use ($ubicaciones) {
      $obtenerNombre = function ($id) use ($ubicaciones) {
        $id = trim((string) $id);
        if ($id === '') {
          return null;
        }
        return optional($ubicaciones->get($id))->nombre;
      };

      return [
        'id' => $version->id,
        'documento_id' => $version->documento_id,
        'codigo' => $version->documento?->codigo ?? 'SIN CODIGO',
        'nombre' => $version->documento?->nombre,
        'version' => $version->version,
        'comentario_revision' => $version->comentario_revision,
        'elaboro' => $obtenerNombre($version->id_elabora),
        'reviso' => $obtenerNombre($version->id_revisa),
        'aprueba' => $obtenerNombre($version->id_aprueba),
        'fecha_elaboracion' => $version->fecha_elaboracion,
        'estado' => $version->estado,
        'tipo_solicitud' => $version->documento?->codigo ? 'EMISION' : 'NUEVO_DOCUMENTO',
      ];
    })->values();
  }

  private function obtenerUbicacionesPorTipo(string $tipo): Collection
  {
    return Ubicaciones::select('id', 'nombre')
      ->where('tipo', $tipo)
      ->orderBy('nombre')
      ->get();
  }
}
