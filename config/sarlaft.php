<?php

declare(strict_types=1);

return [
    'suppress_alert_on_bloquear' => (bool) env('SARLAFT_SUPPRESS_ALERT_ON_BLOQUEAR', true),
    'suppress_alert_on_permitir_permanente' => (bool) env('SARLAFT_SUPPRESS_ALERT_ON_PERMITIR_PERMANENTE', true),
    'retencion_negativas_dias' => (int) env('SARLAFT_RETENCION_NEGATIVAS_DIAS', 180),
    'archive_chunk' => (int) env('SARLAFT_ARCHIVE_CHUNK', 5000),
    'alerta_sla_dias' => (int) env('SARLAFT_ALERTA_SLA_DIAS', 1),
    'auto_escalar_riesgos' => ['alto', 'critico'],
    'auto_estado' => env('SARLAFT_AUTO_ESTADO', 'en_revision'),
    'auto_decision' => env('SARLAFT_AUTO_DECISION', 'bloquear'),
    'auto_atender_lista_negra_interna' => (bool) env('SARLAFT_AUTO_ATENDER_LISTA_NEGRA_INTERNA', true),
    'auto_crear_alerta_atendida' => (bool) env('SARLAFT_AUTO_CREAR_ALERTA_ATENDIDA', true),
    'auto_user_id' => (int) env('SARLAFT_AUTO_USER_ID', 1),
];
