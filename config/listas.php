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

    // Union Europea - Requiere autenticación, no disponible para descarga automática
    // 'eu_consolidated' => [
    //     'nombre' => 'Union Europea Consolidada',
    //     'url' => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content',
    //     'formato' => 'xml',
    //     'parser' => 'eu',
    //     'frecuencia' => 'diaria',
    // ],

];
