<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    // Documentar solo API v2 en esta etapa.
    'api_path' => 'api/v2',

    'api_domain' => null,

    // Archivo OpenAPI exportado para compartir/integrar.
    'export_path' => 'scramble-openapi-gestionrrhh-v2.json',

    'info' => [
        'version' => env('API_VERSION', '2.0.0'),
        'description' => 'API de Gestion RRHH (Permisos, Incapacidades, Vacaciones y Novedades).',
    ],

    'ui' => [
        'title' => 'Gestion RRHH API v2',
        'theme' => 'system',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    'servers' => [
        'Local' => 'api/v2',
    ],

    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
