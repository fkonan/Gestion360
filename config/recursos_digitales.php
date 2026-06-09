<?php

return [
    'cdn' => [
        // Si es true, usa el disco configurado en services.documental.disk (SFTP u otro).
        'use_documental_disk' => env('RECURSOS_APPMOVIL_CDN_USE_DOCUMENTAL_DISK', false),

        // Habilita carga de banners al directorio CDN del mismo servidor.
        'banner_upload_enabled' => env('RECURSOS_APPMOVIL_CDN_BANNER_ENABLED', false),

        // Habilita carga de documentos al directorio CDN del mismo servidor.
        'docs_upload_enabled' => env('RECURSOS_APPMOVIL_CDN_DOCS_ENABLED', true),

        // Ruta absoluta del servidor donde existe el directorio "cdn/".
        // Solo aplica cuando use_documental_disk = false.
        'root_path' => env('RECURSOS_APPMOVIL_CDN_ROOT_PATH', '/var/www/html'),

        // Subruta relativa dentro de root_path para banners.
        'banner_relative_path' => env('RECURSOS_APPMOVIL_CDN_BANNER_RELATIVE_PATH', 'cdn/app/banner/img'),

        // Subruta relativa base para documentos (se completa con carpeta por tipo).
        'docs_relative_path' => env('RECURSOS_APPMOVIL_CDN_DOCS_RELATIVE_PATH', 'cdn/app/docs'),

        // Carpeta por defecto cuando un tipo de documento no esta mapeado.
        'docs_default_folder' => env('RECURSOS_APPMOVIL_CDN_DOCS_DEFAULT_FOLDER', 'politicas'),

        // Lista blanca de carpetas permitidas bajo docs.
        'docs_allowed_folders' => [
            'carga',
            'clubviajero',
            'cumplimiento',
            'mensajeria',
            'pasajes',
            'politicas',
        ],

        // Mapeo de tipo -> carpeta docs.
        'docs_by_type' => [
            3 => 'pasajes',
            4 => 'carga',
            5 => 'mensajeria',
            6 => 'politicas',
            7 => 'clubviajero',
            8 => 'pasajes',
            9 => 'pasajes',
            10 => 'pasajes',
            15 => 'cumplimiento',
        ],

        // URL base publica para construir la URL final almacenada en DB.
        // Si use_documental_disk = true, se toma desde services.documental.public_base_url.
        'public_base_url' => env('RECURSOS_APPMOVIL_CDN_PUBLIC_BASE_URL', ''),
    ],
];
