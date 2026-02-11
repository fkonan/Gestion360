<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema de Huellas Dactilares
    |--------------------------------------------------------------------------
    */

    // URL del servicio Digital Persona Web SDK
    'websdk_url' => env('FINGERPRINT_WEBSDK_URL', 'https://127.0.0.1:52181'),

    // Timeout para conexiones con el servicio
    'timeout' => env('FINGERPRINT_TIMEOUT', 30),

    // Configuración de calidad mínima de huella
    'min_quality' => env('FINGERPRINT_MIN_QUALITY', 50),

    // Formatos de captura soportados
    'supported_formats' => [
        'PngImage' => 'Imagen PNG',
        'Raw' => 'Datos Raw',
        'Intermediate' => 'Feature Set',
        'Compressed' => 'WSQ Comprimido'
    ],

    // Configuración de dedos
    'dedos' => [
        '01' => 'Pulgar derecho',
        '02' => 'Índice derecho',
        '03' => 'Medio derecho',
        '04' => 'Anular derecho',
        '05' => 'Meñique derecho',
        '06' => 'Pulgar izquierdo',
        '07' => 'Índice izquierdo',
        '08' => 'Medio izquierdo',
        '09' => 'Anular izquierdo',
        '10' => 'Meñique izquierdo'
    ],

    // Tipos de error para registros manuales
    'tipos_error' => [
        'sistema_no_disponible' => 'Sistema no disponible',
        'error_lectura_huella' => 'Error en lectura de huella',
        'error_lector' => 'Falla en el lector',
        'error_comunicacion' => 'Error de comunicación',
        'huella_no_leida' => 'Huella no leída',
        'falla_sistema' => 'Falla del sistema'
    ],

    // Configuración de logging
    'logging' => [
        'enabled' => env('FINGERPRINT_LOGGING', true),
        'level' => env('FINGERPRINT_LOG_LEVEL', 'info'),
        'retention_days' => env('FINGERPRINT_LOG_RETENTION', 30)
    ],

    // Configuración de seguridad
    'security' => [
        'encrypt_templates' => env('FINGERPRINT_ENCRYPT_TEMPLATES', true),
        'audit_enabled' => env('FINGERPRINT_AUDIT_ENABLED', true),
        'max_attempts' => env('FINGERPRINT_MAX_ATTEMPTS', 3),
        'lockout_time' => env('FINGERPRINT_LOCKOUT_TIME', 300) // 5 minutos
    ]
];
