<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verificación SSL
    |--------------------------------------------------------------------------
    | En desarrollo local (Laragon/Windows) puede fallar la verificación SSL.
    | Desactivar solo en entornos de desarrollo.
    */
    'verificar_ssl' => env('LISTAS_VERIFICAR_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | OFAC Sanctions List Service API
    |--------------------------------------------------------------------------
    */
    'ofac_api_base' => 'https://sanctionslistservice.ofac.treas.gov',

    /*
    |--------------------------------------------------------------------------
    | Listas Vinculantes
    |--------------------------------------------------------------------------
    */
    'onu' => [
        'nombre' => 'ONU Consolidada',
        'url' => 'https://scsanctions.un.org/resources/xml/en/consolidated.xml',
        'formato' => 'xml',
        'parser' => 'onu',
        'frecuencia' => 'diaria',
    ],

    'ofac_sdn' => [
        'nombre' => 'OFAC SDN',
        'url' => 'https://sanctionslistservice.ofac.treas.gov/api/download/SDN.XML',
        'formato' => 'xml',
        'parser' => 'ofac',
        'frecuencia' => 'diaria',
    ],

    'ofac_consolidated' => [
        'nombre' => 'OFAC Consolidated',
        'url' => 'https://sanctionslistservice.ofac.treas.gov/api/download/CONSOLIDATED.XML',
        'formato' => 'xml',
        'parser' => 'ofac',
        'frecuencia' => 'diaria',
    ],

    'union_europea' => [
        'nombre' => env('LISTA_UE_NOMBRE', 'Union Europea Consolidada'),
        'url' => env('LISTA_UE_URL', 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content'),
        'formato' => 'xml',
        'parser' => 'eu',
        'frecuencia' => 'diaria',
        'token' => env('LISTA_UE_TOKEN'),
        'usuario' => env('LISTA_UE_USUARIO'),
        'contrasena' => env('LISTA_UE_CONTRASENA'),
        'headers' => array_filter([
            'X-Client-Id' => env('LISTA_UE_CLIENT_ID'),
        ]),
    ],

];
