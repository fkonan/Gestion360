<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\GestionRRHH\Models\PrsTipoBloqueo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class CopLevantamientoService
{
    public function __construct(
        private readonly CopDescansoBloqueoHandler $descansoHandler,
        private readonly CopPreoperacionalApiBloqueoHandler $preoperacionalHandler
    ) {}

    public function consultarBloqueos(string $identificacion): array
    {
        $persona = $this->buscarConductor($identificacion);

        if (! $persona) {
            return [
                'persona' => null,
                'bloqueos' => [],
            ];
        }

        $tipos = PrsTipoBloqueo::query()
            ->activos()
            ->with('campos')
            ->orderBy('nombre')
            ->get();

        $bloqueos = [];

        foreach ($tipos as $tipoBloqueo) {
            $handler = $this->resolverHandler($tipoBloqueo);
            if (! $handler) {
                continue;
            }

            $estado = $handler->detect($tipoBloqueo, $identificacion);
            if (! ($estado['bloqueado'] ?? false)) {
                continue;
            }

            $presentation = $handler->preparePresentation(
                $tipoBloqueo,
                $identificacion,
                $tipoBloqueo->campos,
                $estado
            );

            $bloqueos[] = [
                'tipo' => $tipoBloqueo,
                'estado' => $estado,
                'campos' => $presentation['campos'] ?? new Collection(),
                'contexto' => $presentation['contexto'] ?? [],
            ];
        }

        return [
            'persona' => [
                'id' => $persona->id,
                'identificacion' => $persona->identificacion,
                'nombre' => $persona->nombreCompleto(),
            ],
            'bloqueos' => $bloqueos,
        ];
    }

    public function ejecutar(PrsTipoBloqueo $tipoBloqueo, string $identificacion, array $payload): array
    {
        $tipoBloqueo->loadMissing('campos');
        $handler = $this->resolverHandler($tipoBloqueo);

        if (! $handler) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'El tipo de bloqueo no tiene un handler configurado.',
            ];
        }

        $validation = $this->validarPayload($tipoBloqueo, $payload);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'status' => 'validation_error',
                'errors' => $validation['errors'],
            ];
        }

        return $handler->execute($tipoBloqueo, $identificacion, $validation['payload']);
    }

    public function buscarTipoPorCodigo(int|string $codigo): ?PrsTipoBloqueo
    {
        return PrsTipoBloqueo::query()
            ->with('campos')
            ->where(function ($query) use ($codigo) {
                $query->where('codigo', (string) $codigo);

                if (is_numeric($codigo)) {
                    $query->orWhere('id', (int) $codigo);
                }
            })
            ->first();
    }

    private function buscarConductor(string $identificacion): ?PerPersonas
    {
        return PerPersonas::where('identificacion', $identificacion)
            ->where('estado', 'ACTIVO')
            ->where('estborrado', 0)
            ->whereIn('tipdocumento', [1])
            ->first();
    }

    private function resolverHandler(PrsTipoBloqueo $tipoBloqueo): ?CopBloqueoHandlerContract
    {
        return match ($tipoBloqueo->resolverHandlerKey()) {
            $this->descansoHandler->key() => $this->descansoHandler,
            $this->preoperacionalHandler->key() => $this->preoperacionalHandler,
            default => null,
        };
    }

    private function validarPayload(PrsTipoBloqueo $tipoBloqueo, array $payload): array
    {
        $rules = [];
        $attributes = [];

        foreach ($tipoBloqueo->campos as $campo) {
            if (! $campo->estado) {
                continue;
            }

            $rule = $campo->reglas_laravel;
            if (empty($rule) && $campo->requerido) {
                $rule = 'required';
            }

            if (! empty($rule)) {
                $rules[$campo->nombre_campo] = $rule;
            }

            $attributes[$campo->nombre_campo] = $campo->label;
        }

        $validator = Validator::make($payload, $rules, [], $attributes);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return [
            'valid' => true,
            'payload' => $validator->validated(),
        ];
    }
}
