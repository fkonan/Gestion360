<?php

namespace App\Modules\Administration\Services\Reportes;

use App\Constants\Permisos;
use App\Models\GESTIONADMIN\Reporteador;
use App\Models\GESTIONPASAJES\FirmaPoliticas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class ReportesService
{
  protected $apiReportes;

  public function __construct(ApiReportes $apiReportes)
  {
    $this->apiReportes = $apiReportes;
  }

  public function obtenerDatosReporte(array $params)
  {
    // Normalizamos el id para la API
    if (isset($params['id'])) {
      $params['idReporte'] = $params['id'];
      unset($params['id']);
    }

    $reporte = Reporteador::findOrFail($params['idReporte']);

    // Consultar API
    $data = $this->apiReportes->obtenerReporte($params);

    if (!$data) {
      return [];
    }

    // Incrementar contador de consultas
    //$reporte->increment('total_consultas');

    // Formatos especiales por reporte
    $data = $this->formatoEspecialReporte($reporte->id, $data);

    return $data;
  }

  public function formatoEspecialReporte(int $idReporte, array $data)
  {
    switch ($idReporte) {
      // Reporte 19: Conductores y empleados sin firma políticas
      /*
        Este reporte muestra los conductores y empleados que no tienen firma en las políticas.
        Por lo que se requiere cargar desde la base de datos de gestión de pasajes cuales son los empleados que tienen firma.
      */
      case 19:
        $empleadosConFirma = FirmaPoliticas::select('DocCon')
          ->where('FirFecReg', '>=', '2025-10-01') // Fecha desde la cual se consideran las firmas
          ->distinct()
          ->pluck('DocCon')
          ->toArray();

        // Filtrar los datos para excluir los empleados que tienen firma
        $data = array_values(array_filter($data, function ($item) use ($empleadosConFirma) {
          return !in_array($item['IDENTIFICACION'], $empleadosConFirma);
        }));
      default:
        return $data;
    }
  }

  public function tiposReporte()
  {
    $reportes = [
      [
        'titulo' => 'Reportes Personas',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye diversos reportes relacionados con la gestión y actividad de los empleados y conductores.',
        'ruta' => ['reportes.area', 'personas'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_EMPLEADOS,
        'icono' => 'fa-solid fa-users'
      ],
      [
        'titulo' => 'Reportes Pasajes',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de pasajes, tiquetes y esquemas tarifarios.',
        'ruta' => ['reportes.area', 'pasajes'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES,
        'icono' => 'fa-solid fa-ticket-alt'
      ],
      [
        'titulo' => 'Reportes Carga',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de carga.',
        'ruta' => ['reportes.area', 'carga'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARGA,
        'icono' => 'fa-solid fa-truck-loading'
      ],
      [
        'titulo' => 'Reportes Cartera',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de cartera, cobranzas y cuentas por cobrar.',
        'ruta' => ['reportes.area', 'cartera'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARTERA,
        'icono' => 'fa-solid fa-wallet'
      ],
      [
        'titulo' => 'Reportes Auditoria',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de auditoría.',
        'ruta' => ['reportes.area', 'auditoria'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_AUDITORIA,
        'icono' => 'fa-solid fa-file-alt'
      ],
      [
        'titulo' => 'Reportes Crudo',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la operación de crudo.',
        'ruta' => ['reportes.area', 'crudo'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CRUDO,
        'icono' => 'fa-solid fa-file-alt'
      ]
    ];

    return $reportes;
  }

  public function obtenerReportesPorArea(string $area): array
  {
    $areas = [
      'personas' => [
        'nombre' => 'RRHH',
        'vista' => 'reportes.personas.index',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_EMPLEADOS,
      ],
      'pasajes' => [
        'nombre' => 'Unidad pasajes',
        'vista' => 'reportes.pasajes.index',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES,
      ],
      'carga' => [
        'nombre' => 'Unidad carga',
        'vista' => 'reportes.carga.index',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARGA,
      ],
      'cartera' => [
        'nombre' => 'Cartera',
        'vista' => 'reportes.cartera.index',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARTERA,
      ],
      'auditoria' => [
        'nombre' => 'Auditoría',
        'vista' => 'reportes.auditoria.index',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_AUDITORIA,
      ],
      'crudo' => [
        'nombre' => 'Crudo',
        'vista' => 'reportes.crudo.index',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CRUDO,
      ],
    ];

    if (!isset($areas[$area])) {
      abort(404, 'Área no encontrada');
    }

    $config = $areas[$area];

    /** @var \App\Models\User $user */
    $user = Auth::user();

    if (!$user->can($config['permiso'])) {
      throw new AuthorizationException('No tienes permiso para acceder a este reporte');
    }

    $reportes = Reporteador::where('area', $config['nombre'])
      ->where('estado', 'ACTIVO')
      ->get();

    return [
      'vista' => $config['vista'],
      'reportes' => $reportes,
    ];
  }
}
