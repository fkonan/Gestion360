<?php

use App\Constants\Permisos;

return [
    'connections' => [
        'default' => env('REPORTEADOR_CONNECTION_DEFAULT', 'mysql-gestion-admin-reportes'),
        'origenes' => [
            'FICS' => env('REPORTEADOR_CONNECTION_FICS', 'sqlsrv-lectura-reportes'),
            'LOGTRANS' => env('REPORTEADOR_CONNECTION_LOGTRANS', 'oracle-reportes'),
            'GESTION_PASAJES' => env('REPORTEADOR_CONNECTION_GESTION_PASAJES', 'mysql-gestion-pasajes-reportes'),
        ],
    ],

    // Configuracion de respaldo para rango de fechas del reporteador.
    // - default_meses: limite global mientras el reporte no tenga max_meses_consulta en BD.
    // - sin_limite y max_meses_por_reporte: compatibilidad temporal con configuracion legacy.
    'reportes_rango_fechas' => [
        'default_meses' => 1,
        'sin_limite' => [9],
        'max_meses_por_reporte' => [
            7 => 6,
            65 => 1,
        ],
    ],

    'areas' => [
        'personas' => [
            'slug' => 'personas',
            'nombre_bd' => 'RRHH',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_EMPLEADOS,
            'titulo' => 'Reportes Personas',
            'descripcion' => 'Consultar',
            'tooltip' => 'Incluye diversos reportes relacionados con empleados y conductores.',
            'icono' => 'fa-solid fa-users',
            'ruta' => 'reportes.area',
        ],
        'pasajes' => [
            'slug' => 'pasajes',
            'nombre_bd' => 'Unidad pasajes',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES,
            'titulo' => 'Reportes Pasajes',
            'descripcion' => 'Consultar',
            'tooltip' => 'Reportes de pasajes, tiquetes y esquemas tarifarios.',
            'icono' => 'fa-solid fa-ticket-alt',
            'ruta' => 'reportes.area',
        ],
        'carga' => [
            'slug' => 'carga',
            'nombre_bd' => 'Unidad carga',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_CARGA,
            'titulo' => 'Reportes Carga',
            'descripcion' => 'Consultar',
            'tooltip' => 'Reportes de análisis y gestión de carga.',
            'icono' => 'fa-solid fa-truck-loading',
            'ruta' => 'reportes.area',
        ],
        'cartera' => [
            'slug' => 'cartera',
            'nombre_bd' => 'Cartera',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_CARTERA,
            'titulo' => 'Reportes Cartera',
            'descripcion' => 'Consultar',
            'tooltip' => 'Reportes de cartera, cobranzas y cuentas por cobrar.',
            'icono' => 'fa-solid fa-wallet',
            'ruta' => 'reportes.area',
        ],
        'auditoria' => [
            'slug' => 'auditoria',
            'nombre_bd' => 'Auditoria',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_AUDITORIA,
            'titulo' => 'Reportes Auditoría',
            'descripcion' => 'Consultar',
            'tooltip' => 'Reportes relacionados con auditoría interna y controles.',
            'icono' => 'fa-solid fa-file-alt',
            'ruta' => 'reportes.area',
        ],
        'crudo' => [
            'slug' => 'crudo',
            'nombre_bd' => 'Crudo',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_CRUDO,
            'titulo' => 'Reportes Crudo',
            'descripcion' => 'Consultar',
            'tooltip' => 'Flota dedicada, manifiestos, anticipos...',
            'icono' => 'fa-solid fa-gas-pump',
            'ruta' => 'reportes.area',
        ],
        'financiera' => [
            'slug' => 'financiera',
            'nombre_bd' => 'Financiera',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_FINANCIERA,
            'titulo' => 'Reportes Financiera',
            'descripcion' => 'Consultar',
            'tooltip' => 'Gastos, ingresos, ventas por agencia y tipo de vehículo.',
            'icono' => 'fa-solid fa-file-invoice-dollar',
            'ruta' => 'reportes.area',
        ],
        'fundacion_de_la_mujer' => [
            'slug' => 'fundacion_de_la_mujer',
            'nombre_bd' => 'Fundacion de la mujer',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_FUNDACION_DE_LA_MUJER,
            'titulo' => 'Fundación de la Mujer',
            'descripcion' => 'Consultar',
            'tooltip' => 'Consultas generales de la Fundación de la Mujer.',
            'icono' => 'fa-solid fa-university',
            'ruta' => 'reportes.area',
        ],
        'contabilidad' => [
            'slug' => 'contabilidad',
            'nombre_bd' => 'Contabilidad',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_CONTABILIDAD,
            'titulo' => 'Reportes Contabilidad',
            'descripcion' => 'Consultar',
            'tooltip' => 'Reportes contables y financieros.',
            'icono' => 'fa-solid fa-calculator',
            'ruta' => 'reportes.area',
        ],
        'ficha_tecnica' => [
            'slug' => 'ficha_tecnica',
            'nombre_bd' => 'Ficha tecnica',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_FICHA_TECNICA,
            'titulo' => 'Reportes Ficha Técnica',
            'descripcion' => 'Consultar',
            'tooltip' => 'Documentos y relación de vehículos.',
            'icono' => 'fa-solid fa-id-card',
            'ruta' => 'reportes.area',
        ],
        'giros_y_convenios_empresariales' => [
            'slug' => 'giros_y_convenios_empresariales',
            'nombre_bd' => 'Giros y convenios empresariales',
            'permiso' => Permisos::ADMINISTRACION_REPORTES_GIROS_Y_CONVENIOS_EMPRESARIALES,
            'titulo' => 'Reportes Giros y Convenios',
            'descripcion' => 'Consultar',
            'tooltip' => 'Consultas de Giros, Canales y Convenios.',
            'icono' => 'fa-solid fa-handshake',
            'ruta' => 'reportes.area',
        ],
    ],

];
