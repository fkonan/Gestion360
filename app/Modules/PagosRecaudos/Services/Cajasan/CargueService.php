<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\PagosRecaudos\Models\ConPagosRecaudos;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CargueService
{
    private const IDENEMPRESA = 890200106;

    private const TIPOMOVIMIENTO = 1;

    private const ESTADO_CREADO = 'C';

    private const ROL_MODIFICA = 60;

    private const USRCARGUE = 6761;

    public function __construct(
        private UsuarioService $usuarioService
    ) {}

    public function obtenerOCrear(object $cajaActiva): int
    {
        $fechaHoy = now()->format('Y-n-d');
        $descripcion = 'CAJASAN '.$fechaHoy;

        $cargue = ConPagosRecaudos::where('descripcion', $descripcion)->first();
        if ($cargue) {
            return $cargue->id;
        }

        return self::crear($descripcion, $cajaActiva);
    }

    private function crear(string $descripcion, object $cajaActiva): int
    {
        try {
            $fechaActual = now();
            $userId = $this->usuarioService->obtenerUserId();
            $idGenerado = self::obtenerSiguienteId('SEC_PAGOSYRECAUDOS');

            $cargue = new ConPagosRecaudos;
            $cargue->id = $idGenerado;
            $cargue->idenempresa = self::IDENEMPRESA;
            $cargue->descripcion = $descripcion;
            $cargue->valortotal = 0;
            $cargue->tipomovimiento = self::TIPOMOVIMIENTO;
            $cargue->estado = self::ESTADO_CREADO;
            $cargue->fechacargue = $fechaActual;
            $cargue->usrcargue = self::USRCARGUE;
            $cargue->estborrado = 0;
            $cargue->fecmodifica = $fechaActual;
            $cargue->empmodifica = $cajaActiva->idsucursal;
            $cargue->usrmodifica = $userId;
            $cargue->rolmodifica = self::ROL_MODIFICA;
            $cargue->feccreacion = $fechaActual;
            $cargue->usrcreacion = $userId;
            $cargue->empcreacion = $cajaActiva->idsucursal;
            $cargue->save();

            return $cargue->id;
        } catch (Exception $e) {
            Log::error('Error al crear cargue: '.$e->getMessage());
            throw new Exception('Error al crear cargue.');
            /* throw $e; */
        }
    }

    public static function obtenerSiguienteId(string $secuencia): int
    {
        $result = DB::connection('oracle')
            ->select("SELECT {$secuencia}.NEXTVAL as id FROM DUAL");

        return $result[0]->id ?? throw new Exception("Error al obtener siguiente ID de $secuencia");
    }
}
