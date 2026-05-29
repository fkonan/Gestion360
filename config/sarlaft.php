<?php

declare(strict_types=1);

return [
    'alerta_sla_dias' => (int) env('SARLAFT_ALERTA_SLA_DIAS', 1),
    'auto_escalar_riesgos' => ['alto', 'critico'],
    'auto_estado' => env('SARLAFT_AUTO_ESTADO', 'en_revision'),
    'auto_user_id' => (int) env('SARLAFT_AUTO_USER_ID', 1),
];
