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
    /** @var \App\Models\User $user */
    $user = Auth::user();

    // 1. Áreas con permisos
    $areas = [
      'personas' => ['nombre' => 'RRHH', 'permiso' => Permisos::ADMINISTRACION_REPORTES_EMPLEADOS],
      'pasajes' => ['nombre' => 'Unidad pasajes', 'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES],
      'carga' => ['nombre' => 'Unidad carga', 'permiso' => Permisos::ADMINISTRACION_REPORTES_CARGA],
      'cartera' => ['nombre' => 'Cartera', 'permiso' => Permisos::ADMINISTRACION_REPORTES_CARTERA],
      'auditoria' => ['nombre' => 'Auditoría', 'permiso' => Permisos::ADMINISTRACION_REPORTES_AUDITORIA],
      'crudo' => ['nombre' => 'Crudo', 'permiso' => Permisos::ADMINISTRACION_REPORTES_CRUDO],
      'financiera' => ['nombre' => 'Financiera', 'permiso' => Permisos::ADMINISTRACION_REPORTES_FINANCIERA],
      'fundacion_de_la_mujer' => ['nombre' => 'Fundación de la Mujer', 'permiso' => Permisos::ADMINISTRACION_REPORTES_FUNDACION_DE_LA_MUJER],
      'contabilidad' => ['nombre' => 'Contabilidad', 'permiso' => Permisos::ADMINISTRACION_REPORTES_CONTABILIDAD],
      'ficha_tecnica' => ['nombre' => 'Ficha Técnica', 'permiso' => Permisos::ADMINISTRACION_REPORTES_FICHA_TECNICA],
      'giros_y_convenios_empresariales' => ['nombre' => 'Giros y Convenios Empresariales', 'permiso' => Permisos::ADMINISTRACION_REPORTES_GIROS_Y_CONVENIOS_EMPRESARIALES],
    ];

    // 2. Propiedades visuales por área
    $visual = [
      'personas' => [
        'titulo' => 'Reportes Personas',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye diversos reportes relacionados con empleados y conductores.',
        'icono' => 'fa-solid fa-users',
      ],
      'pasajes' => [
        'titulo' => 'Reportes Pasajes',
        'descripcion' => 'Consultar',
        'tooltip' => 'Reportes de pasajes, tiquetes y esquemas tarifarios.',
        'icono' => 'fa-solid fa-ticket-alt',
      ],
      'carga' => [
        'titulo' => 'Reportes Carga',
        'descripcion' => 'Consultar',
        'tooltip' => 'Reportes de análisis y gestión de carga.',
        'icono' => 'fa-solid fa-truck-loading',
      ],
      'cartera' => [
        'titulo' => 'Reportes Cartera',
        'descripcion' => 'Consultar',
        'tooltip' => 'Reportes de cartera, cobranzas y cuentas por cobrar.',
        'icono' => 'fa-solid fa-wallet',
      ],
      'auditoria' => [
        'titulo' => 'Reportes Auditoría',
        'descripcion' => 'Consultar',
        'tooltip' => 'Reportes relacionados con auditoría interna y controles.',
        'icono' => 'fa-solid fa-file-alt',
      ],
      'crudo' => [
        'titulo' => 'Reportes Crudo',
        'descripcion' => 'Consultar',
        'tooltip' => 'Flota dedicada, manifiestos, anticipos...',
        'icono' => 'fa-solid fa-gas-pump',
      ],
      'financiera' => [
        'titulo' => 'Reportes Financiera',
        'descripcion' => 'Consultar',
        'tooltip' => 'Gastos, ingresos, ventas por agencia y tipo de vehículo.',
        'icono' => 'fa-solid fa-file-invoice-dollar',
      ],
      'fundacion_de_la_mujer' => [
        'titulo' => 'Fundación de la Mujer',
        'descripcion' => 'Consultar',
        'tooltip' => 'Consultas generales de la Fundación de la Mujer.',
        'icono' => 'fa-solid fa-university',
      ],
      'contabilidad' => [
        'titulo' => 'Reportes Contabilidad',
        'descripcion' => 'Consultar',
        'tooltip' => 'Reportes contables y financieros.',
        'icono' => 'fa-solid fa-calculator',
      ],
      'ficha_tecnica' => [
        'titulo' => 'Reportes Ficha Técnica',
        'descripcion' => 'Consultar',
        'tooltip' => 'Documentos y relación de vehículos.',
        'icono' => 'fa-solid fa-id-card',
      ],
      'giros_y_convenios_empresariales' => [
        'titulo' => 'Reportes Giros y Convenios',
        'descripcion' => 'Consultar',
        'tooltip' => 'Consultas de Giros, Canales y Convenios.',
        'icono' => 'fa-solid fa-handshake',
      ],
    ];

    $result = [];

    foreach ($areas as $slug => $conf) {

      $areaNombre = $conf['nombre'];
      $permisoArea = $conf['permiso'];

      $accesoPorArea = $user->can($permisoArea);

      $reportesArea = Reporteador::where('area', $areaNombre)
        ->where('estado', 'ACTIVO')
        ->get();

      $accesoIndividual = $reportesArea->contains(
        fn($rep) =>
        $user->can("administracion.reportes.id_{$rep->id}")
      );

      if (!$accesoPorArea && !$accesoIndividual) {
        continue;
      }

      $result[] = [
        'titulo' => $visual[$slug]['titulo'],
        'descripcion' => $visual[$slug]['descripcion'],
        'tooltip' => $visual[$slug]['tooltip'],
        'ruta' => ['reportes.area', $slug],
        'icono' => $visual[$slug]['icono'],
      ];
    }
    return $result;
  }


  public function usuarioPuedeVerArea($slugArea, $permisoArea)
  {
    /** @var \App\Models\User $user */
    $user = Auth::user();

    // Permiso de área → acceso completo
    if ($user->can($permisoArea)) {
      return true;
    }

    // Buscar reportes de esta área
    $reportes = Reporteador::where('area', $slugArea)
      ->where('estado', 'ACTIVO')
      ->get();

    // Ver si el usuario tiene permiso individual a alguno
    foreach ($reportes as $r) {
      if ($user->can("administracion.reportes.id_{$r->id}")) {
        return true;
      }
    }

    return false;
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


    //1. OBTENER TODOS LOS REPORTES DEL ÁREA
    $reportesArea = Reporteador::where('area', $config['nombre'])
      ->where('estado', 'ACTIVO')
      ->get();

    //2. FILTRAR QUÉ REPORTES INDIVIDUALES EL USUARIO PUEDE VER
    $reportesConPermisoIndividual = $reportesArea->filter(function ($reporte) use ($user) {
      return $user->can("administracion.reportes.id_{$reporte->id}");
    });

    //3. SI EL USUARIO TIENE PERMISO DE ÁREA → MOSTRAR TODOS
    if ($user->can($config['permiso'])) {
      return ['reportes' => $reportesArea];
    }

    //4. SIN PERMISO DE ÁREA, PERO CON ALGÚN PERMISO INDIVIDUAL → MOSTRAR SOLO ESOS
    if ($reportesConPermisoIndividual->isNotEmpty()) {
      return ['reportes' => $reportesConPermisoIndividual->values()];
    }

    //5. SIN PERMISO DE ÁREA NI INDIVIDUAL → BLOQUEAR
    throw new AuthorizationException('No tienes permiso para acceder a estos reportes.');
  }
}
