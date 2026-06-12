<?php

declare(strict_types=1);

return [
    'alerta_sla_dias' => (int) env('SARLAFT_ALERTA_SLA_DIAS', 1),
    'auto_escalar_riesgos' => ['vinculante'],
    'auto_estado' => env('SARLAFT_AUTO_ESTADO', 'en_revision'),
    'auto_user_id' => (int) env('SARLAFT_AUTO_USER_ID', 1),

    // Limites del endpoint de consulta de listas (POST /api/v1/listas/consultar).
    // Generosos por defecto: un unico cliente consulta por muchas sucursales.
    'consulta_rate_minuto' => (int) env('SARLAFT_CONSULTA_RATE_MINUTO', 300),
    'consulta_rate_dia' => (int) env('SARLAFT_CONSULTA_RATE_DIA', 30000),
];
