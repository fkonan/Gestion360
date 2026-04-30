<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mock que simula el endpoint de un sistema externo (ej. Logtrans, Remesas).
 *
 * Cuando tengamos las URLs reales, simplemente actualizamos pull_endpoint
 * en sarlaft_sistemas_consumidores y este mock deja de usarse.
 *
 * Parámetros de query:
 *  - fecha_desde  (Y-m-d o ISO-8601)
 *  - fecha_hasta  (Y-m-d o ISO-8601, opcional — default: hoy)
 *  - sistema      (string — permite devolver datos distintos por sistema)
 */
class MockSistemaExternoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sistema = strtolower((string) ($request->query('sistema', 'logtrans')));
        $fechaDesde = (string) ($request->query('fecha_desde', now()->subDay()->format('Y-m-d')));
        $fechaHasta = (string) ($request->query('fecha_hasta', now()->format('Y-m-d')));

        $datos = match ($sistema) {
            'odin'    => $this->datosOdin($fechaDesde, $fechaHasta),
            '360'     => $this->datos360($fechaDesde, $fechaHasta),
            'fics'    => $this->datosFics($fechaDesde, $fechaHasta),
            'remesas' => $this->datosRemesas($fechaDesde, $fechaHasta),
            default   => $this->datosLogtrans($fechaDesde, $fechaHasta),
        };

        return response()->json(['data' => $datos]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datosLogtrans(string $fechaDesde, string $fechaHasta): array
    {
        return [
            [
                'tipo_operacion'   => 'pasaje',
                'referencia'       => 'TKT-' . date('Y') . '-001',
                'fecha_operacion'  => $fechaDesde . ' 08:15:00',
                'monto'            => 85000,
                'descripcion'      => 'Venta de tiquete - Mock Logtrans',
                'tipo_documento'   => 'CC',
                'numero_documento' => '123456789',
                'nombre'           => 'JUAN CARLOS PEREZ GOMEZ',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'ONU Consolidada',
                'sistema_origen'   => 'Logtrans',
                'contexto'         => [
                    'agencia' => 'cucuta',
                    'origen'  => 'Bogotá',
                    'destino' => 'Medellín',
                ],
            ],
            [
                'tipo_operacion'   => 'pasaje',
                'referencia'       => 'TKT-' . date('Y') . '-002',
                'fecha_operacion'  => $fechaDesde . ' 09:30:00',
                'monto'            => 120000,
                'descripcion'      => 'Venta de tiquete - Mock Logtrans',
                'tipo_documento'   => 'CC',
                'numero_documento' => '987654321',
                'nombre'           => 'MARIA LOPEZ RODRIGUEZ',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'OFAC',
                'sistema_origen'   => 'Logtrans',
                'contexto'         => [
                    'agencia' => 'bogota',
                    'origen'  => 'Bogotá',
                    'destino' => 'Cali',
                ],
            ],
            [
                'tipo_operacion'   => 'encomienda',
                'referencia'       => 'ENC-' . date('Y') . '-055',
                'fecha_operacion'  => $fechaDesde . ' 11:00:00',
                'monto'            => 45000,
                'descripcion'      => 'Envío encomienda - Mock Logtrans',
                'tipo_documento'   => 'NIT',
                'numero_documento' => '900123456-7',
                'nombre'           => 'TRANSPORTES EJEMPLO SAS',
                'tipo_lista'       => 'interna',
                'lista_nombre'     => 'copetran',
                'sistema_origen'   => 'Logtrans',
                'contexto'         => [
                    'agencia'         => 'bucaramanga',
                    'tipo_encomienda' => 'carga',
                    'peso_kg'         => 12,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datosRemesas(string $fechaDesde, string $fechaHasta): array
    {
        return [
            [
                'tipo_operacion'   => 'remesa_envio',
                'referencia'       => 'REM-' . date('Y') . '-201',
                'fecha_operacion'  => $fechaDesde . ' 10:00:00',
                'monto'            => 2000000,
                'descripcion'      => 'Envío de remesa - Mock Remesas',
                'tipo_documento'   => 'CE',
                'numero_documento' => '556677',
                'nombre'           => 'PEDRO ALVARADO NUÑEZ',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'ONU Consolidada',
                'sistema_origen'   => 'Remesas',
                'contexto'         => [
                    'agencia'      => 'cucuta',
                    'pais_destino' => 'Venezuela',
                    'ciudad'       => 'San Cristóbal',
                ],
            ],
            [
                'tipo_operacion'   => 'remesa_cobro',
                'referencia'       => 'REM-' . date('Y') . '-202',
                'fecha_operacion'  => $fechaDesde . ' 14:30:00',
                'monto'            => 800000,
                'descripcion'      => 'Cobro de remesa - Mock Remesas',
                'tipo_documento'   => 'CC',
                'numero_documento' => '111222333',
                'nombre'           => 'ANA GUTIERREZ VARGAS',
                'tipo_lista'       => 'interna',
                'lista_nombre'     => 'copetran',
                'sistema_origen'   => 'Remesas',
                'contexto'         => [
                    'agencia'     => 'pamplona',
                    'pais_origen' => 'Venezuela',
                    'ciudad'      => 'Caracas',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datosOdin(string $fechaDesde, string $fechaHasta): array
    {
        return [
            [
                'tipo_operacion'   => 'venta_tiquete',
                'referencia'       => 'ODIN-' . date('Y') . '-301',
                'fecha_operacion'  => $fechaDesde . ' 07:45:00',
                'monto'            => 95000,
                'descripcion'      => 'Venta tiquete en línea - Mock Odin',
                'tipo_documento'   => 'CC',
                'numero_documento' => '445566778',
                'nombre'           => 'CARLOS MENDEZ PEÑA',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'OFAC',
                'sistema_origen'   => 'Odin',
                'contexto'         => [
                    'canal'   => 'web',
                    'origen'  => 'Bucaramanga',
                    'destino' => 'Bogotá',
                ],
            ],
            [
                'tipo_operacion'   => 'venta_tiquete',
                'referencia'       => 'ODIN-' . date('Y') . '-302',
                'fecha_operacion'  => $fechaDesde . ' 09:10:00',
                'monto'            => 110000,
                'descripcion'      => 'Venta tiquete app móvil - Mock Odin',
                'tipo_documento'   => 'CC',
                'numero_documento' => '332211009',
                'nombre'           => 'LUCIA TORRES FAJARDO',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'ONU Consolidada',
                'sistema_origen'   => 'Odin',
                'contexto'         => [
                    'canal'   => 'app',
                    'origen'  => 'Cúcuta',
                    'destino' => 'Medellín',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datos360(string $fechaDesde, string $fechaHasta): array
    {
        return [
            [
                'tipo_operacion'   => 'encomienda',
                'referencia'       => '360-' . date('Y') . '-401',
                'fecha_operacion'  => $fechaDesde . ' 08:00:00',
                'monto'            => 35000,
                'descripcion'      => 'Envío encomienda urbana - Mock 360',
                'tipo_documento'   => 'NIT',
                'numero_documento' => '800900100-5',
                'nombre'           => 'DISTRIBUCIONES NORTE SAS',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'ONU Consolidada',
                'sistema_origen'   => '360',
                'contexto'         => [
                    'agencia'         => 'bucaramanga',
                    'tipo_encomienda' => 'documentos',
                    'peso_kg'         => 0.5,
                ],
            ],
            [
                'tipo_operacion'   => 'giro',
                'referencia'       => '360-' . date('Y') . '-402',
                'fecha_operacion'  => $fechaDesde . ' 13:20:00',
                'monto'            => 500000,
                'descripcion'      => 'Giro nacional - Mock 360',
                'tipo_documento'   => 'CC',
                'numero_documento' => '192837465',
                'nombre'           => 'ROBERTO SILVA MORENO',
                'tipo_lista'       => 'interna',
                'lista_nombre'     => 'copetran',
                'sistema_origen'   => '360',
                'contexto'         => [
                    'agencia'      => 'cucuta',
                    'ciudad_destino' => 'Barranquilla',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datosFics(string $fechaDesde, string $fechaHasta): array
    {
        return [
            [
                'tipo_operacion'   => 'cambio_divisas',
                'referencia'       => 'FICS-' . date('Y') . '-501',
                'fecha_operacion'  => $fechaDesde . ' 10:30:00',
                'monto'            => 1500000,
                'descripcion'      => 'Cambio USD a COP - Mock FICS',
                'tipo_documento'   => 'CE',
                'numero_documento' => '778899001',
                'nombre'           => 'DMITRI VOLKOV PETROV',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'ONU Consolidada',
                'sistema_origen'   => 'FICS',
                'contexto'         => [
                    'agencia'        => 'cucuta',
                    'moneda_origen'  => 'USD',
                    'moneda_destino' => 'COP',
                    'tasa'           => 4100.50,
                ],
            ],
            [
                'tipo_operacion'   => 'transferencia_internacional',
                'referencia'       => 'FICS-' . date('Y') . '-502',
                'fecha_operacion'  => $fechaDesde . ' 15:00:00',
                'monto'            => 3000000,
                'descripcion'      => 'Transferencia int. saliente - Mock FICS',
                'tipo_documento'   => 'CC',
                'numero_documento' => '654321098',
                'nombre'           => 'SOFIA RAMIREZ BLANCO',
                'tipo_lista'       => 'vinculante',
                'lista_nombre'     => 'OFAC',
                'sistema_origen'   => 'FICS',
                'contexto'         => [
                    'agencia'      => 'bogota',
                    'pais_destino' => 'Panamá',
                    'banco_destino' => 'Banco General',
                ],
            ],
        ];
    }
}
