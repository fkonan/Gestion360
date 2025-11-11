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
        'tooltip' => 'Conceptos por anticipo, flota dedicada, manifiestos, plantilla flota dedicada entre otros.',
        'ruta' => ['reportes.area', 'crudo'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CRUDO,
        'icono' => 'fa-solid fa-gas-pump'
      ],
      [
        'titulo' => 'Reportes Financiera',
        'descripcion' => 'Consultar',
        'tooltip' => 'Gastos, ingresos, ventas por agencia y tipo de vehiculo.',
        'ruta' => ['reportes.area', 'financiera'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_FINANCIERA,
        'icono' => 'fa-solid fa-file-invoice-dollar'
      ],
      [
        'titulo' => 'Fundación de la Mujer',
        'descripcion' => 'Consultar',
        'tooltip' => 'Consultas generales sobre la Fundación de la Mujer.',
        'ruta' => ['reportes.area', 'fundacion_de_la_mujer'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_FUNDACION_DE_LA_MUJER,
        'icono' => 'fa-solid fa-university'
      ],
      [
        'titulo' => 'Reportes Contabilidad',
        'descripcion' => 'Consultar',
        'tooltip' => 'Consultas generales sobre la Contabilidad.',
        'ruta' => ['reportes.area', 'contabilidad'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CONTABILIDAD,
        'icono' => 'fa-solid fa-calculator'
      ],
      [
        'titulo' => 'Reportes Ficha Técnica',
        'descripcion' => 'Consultar',
        'tooltip' => 'Consulta de documentos, relación vehículos asociados, total parque automotor.',
        'ruta' => ['reportes.area', 'ficha_tecnica'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_FICHA_TECNICA,
        'icono' => 'fa-solid fa-id-card'
      ],
      [
        'titulo' => 'Reportes Giros y Convenios',
        'descripcion' => 'Consultar',
        'tooltip' => 'Consulta de información relacionada con los procesos de Giros, Convenios y Canales.',
        'ruta' => ['reportes.area', 'giros_y_convenios_empresariales'],
        'permiso' => Permisos::ADMINISTRACION_REPORTES_GIROS_Y_CONVENIOS_EMPRESARIALES,
        'icono' => 'fa-solid fa-handshake'
      ],
    ];

    return $reportes;
  }

  public function obtenerReportesPorArea(string $area): array
  {
    $areas = [
      'personas' => [
        'nombre' => 'RRHH',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_EMPLEADOS,
      ],
      'pasajes' => [
        'nombre' => 'Unidad pasajes',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES,
      ],
      'carga' => [
        'nombre' => 'Unidad carga',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARGA,
      ],
      'cartera' => [
        'nombre' => 'Cartera',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARTERA,
      ],
      'auditoria' => [
        'nombre' => 'Auditoría',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_AUDITORIA,
      ],
      'crudo' => [
        'nombre' => 'Crudo',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CRUDO,
      ],
      'financiera' => [
        'nombre' => 'Financiera',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_FINANCIERA,
      ],
      'fundacion_de_la_mujer' => [
        'nombre' => 'Fundación de la Mujer',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_FUNDACION_DE_LA_MUJER,
      ],
      'contabilidad' => [
        'nombre' => 'Contabilidad',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CONTABILIDAD,
      ],
      'ficha_tecnica' => [
        'nombre' => 'Ficha Técnica',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_FICHA_TECNICA,
      ],
      'giros_y_convenios_empresariales' => [
        'nombre' => 'Giros y Convenios Empresariales',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_GIROS_Y_CONVENIOS_EMPRESARIALES,
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
      'reportes' => $reportes
    ];
  }
}
