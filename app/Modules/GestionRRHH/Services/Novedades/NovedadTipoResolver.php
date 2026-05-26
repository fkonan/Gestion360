<?php

namespace App\Modules\GestionRRHH\Services\Novedades;

class NovedadTipoResolver
{
    public const TIPO_PERMISO = 'PERMISO';
    public const TIPO_PERMISO_PERMANENTE = 'PERMISO_PERMANENTE';
    public const TIPO_INCAPACIDAD = 'INCAPACIDAD';
    public const TIPO_VACACION = 'VACACION';

    public static function normalizar(string $tablaOCodigo, string $descripcion = ''): string
    {
        $tablaOCodigo = strtoupper(trim($tablaOCodigo));
        $descripcion = strtoupper(trim($descripcion));

        $valor = $tablaOCodigo !== '' ? $tablaOCodigo : $descripcion;
        if ($valor === '') {
            return '';
        }

        return match ($valor) {
            'EMP_PERMISOS', 'PERMISOS', 'PERMISO' => self::TIPO_PERMISO,
            'EMP_PERMISOS_PERMANENTES', 'PERMISOS_PERMANENTES', 'PERMISO_PERMANENTE', 'PERMISO PERMANENTE' => self::TIPO_PERMISO_PERMANENTE,
            'EMP_INCAPACIDADES', 'INCAPACIDADES', 'INCAPACIDAD' => self::TIPO_INCAPACIDAD,
            'EMP_VACACIONES', 'VACACIONES', 'VACACION' => self::TIPO_VACACION,
            default => $valor,
        };
    }

    public static function tablaPorTipo(string $tipo): ?string
    {
        return match (self::normalizar($tipo)) {
            self::TIPO_PERMISO => 'EMP_PERMISOS',
            self::TIPO_PERMISO_PERMANENTE => 'EMP_PERMISOS_PERMANENTES',
            self::TIPO_INCAPACIDAD => 'EMP_INCAPACIDADES',
            self::TIPO_VACACION => 'EMP_VACACIONES',
            default => null,
        };
    }
}
