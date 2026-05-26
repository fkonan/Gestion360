<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'no-captcha' => [
        'sitekey' => env('NOCAPTCHA_SITEKEY'),
        'secret' => env('NOCAPTCHA_SECRET'),
    ],

    'fingerprint' => [
        'url' => env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5055'),
        'key' => env('FINGERPRINT_SERVICE_KEY'),
    ],

    'camera' => [
        'url' => env('CAMERA_SERVICE_URL'),
        'key' => env('CAMERA_SERVICE_KEY'),
        'allowed_cidrs' => array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            explode(',', (string) env('CAMERA_ALLOWED_CIDRS', '172.16.0.0/12,127.0.0.1/32,::1/128'))
        ))),
    ],

    'attendance_events' => [
        'key' => env('ATTENDANCE_EVENTS_API_KEY'),
    ],

    'api_jwt' => [
        'secret' => env('API_JWT_SECRET'),
        'issuer' => env('API_JWT_ISSUER', env('APP_URL', 'autogestion')),
        'audience' => env('API_JWT_AUDIENCE', 'autogestion-api'),
        'ttl_minutes' => env('API_JWT_TTL_MINUTES', 60),
        'leeway_seconds' => env('API_JWT_LEEWAY_SECONDS', 30),
        'default_scope' => env('API_JWT_DEFAULT_SCOPE', ''),
        'admin_scopes' => preg_split(
            '/\s+/',
            trim((string) env('API_JWT_ADMIN_SCOPES', 'empleados.novedades.admin'))
        ) ?: [],
        'clients' => [
            [
                'id' => env('API_JWT_CLIENT_ID'),
                'secret' => env('API_JWT_CLIENT_SECRET'),
                'scopes' => preg_split(
                    '/\s+/',
                    trim((string) env('API_JWT_CLIENT_SCOPES', ''))
                ) ?: [],
            ],
        ],
    ],

    'employee_permits' => [
        'key' => env('EMPLOYEE_PERMITS_API_KEY'),
        'pdf_template_path' => env('EMPLOYEE_PERMITS_PDF_TEMPLATE_PATH', base_path('app/Modules/GestionRRHH/Resources/Templates/Permisos/FormatoPermisoSalida.pdf')),
        'notifications' => [
            'radicado_email' => env('EMPLOYEE_PERMITS_NOTIFICATION_EMAIL', 'desarrollo3@copetran.com'),
            'rrhh_email' => env('EMPLOYEE_PERMITS_RRHH_NOTIFICATION_EMAIL', 'desarrollo3@copetran.com'),
            'force_notification_email' => env('EMPLOYEE_PERMITS_FORCE_NOTIFICATION_EMAIL', true),
            'magic_link_ttl_minutes' => env('EMPLOYEE_PERMITS_MAGIC_LINK_TTL_MINUTES', 20),
            'force_magic_link_for_testing' => env('EMPLOYEE_PERMITS_FORCE_MAGIC_LINK_FOR_TESTING', false),
            'queue_connection' => env('EMPLOYEE_PERMITS_MAIL_QUEUE_CONNECTION', 'database-admin'),
            'queue_name' => env('EMPLOYEE_PERMITS_MAIL_QUEUE_NAME', 'rrhh-mails'),
        ],
        'performance' => [
            'enabled' => env('EMPLOYEE_PERMITS_PERF_LOG_ENABLED', false),
            'threshold_ms' => env('EMPLOYEE_PERMITS_PERF_LOG_THRESHOLD_MS', 0),
            'log_level' => env('EMPLOYEE_PERMITS_PERF_LOG_LEVEL', 'info'),
        ],
        'attachments' => [
            'base_url' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_URL'),
            'upload_path' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_UPLOAD_PATH', '/api/v1/documentos/empleados-permisos'),
            'token' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_TOKEN'),
            'auth_mode' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_AUTH_MODE', 'bearer'),
            'auth_header' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_AUTH_HEADER', 'X-Api-Key'),
            'file_field' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_FILE_FIELD', 'archivo'),
            'connect_timeout' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_CONNECT_TIMEOUT', 8),
            'timeout' => env('EMPLOYEE_PERMITS_ATTACHMENTS_API_TIMEOUT', 30),
        ],
    ],

    'documental' => [
        'disk' => env('DOCUMENTAL_DISK', 'documental_sftp'),
        'base_directory' => env('DOCUMENTAL_BASE_DIRECTORY', 'ArchivoDigital'),
        'public_base_url' => env('DOCUMENTAL_PUBLIC_BASE_URL', 'https://cdn.copetran.com.co'),
    ],

    'employee_incapacities' => [
        'attachments' => [
            'base_url' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_URL', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_URL')),
            'upload_path' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_UPLOAD_PATH', '/api/v1/documentos/empleados-incapacidades'),
            'token' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_TOKEN', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_TOKEN')),
            'auth_mode' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_AUTH_MODE', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_AUTH_MODE', 'bearer')),
            'auth_header' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_AUTH_HEADER', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_AUTH_HEADER', 'X-Api-Key')),
            'file_field' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_FILE_FIELD', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_FILE_FIELD', 'archivo')),
            'connect_timeout' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_CONNECT_TIMEOUT', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_CONNECT_TIMEOUT', 8)),
            'timeout' => env('EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_TIMEOUT', env('EMPLOYEE_PERMITS_ATTACHMENTS_API_TIMEOUT', 30)),
        ],
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
