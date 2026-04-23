<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\PoliticaSarlaft;

class PoliticaSarlaftService
{
    /**
     * @return array{
     *   sla_dias_alerta: int,
     *   auto_escalar_riesgos: array<int, string>,
     *   auto_estado: string,
     *   auto_atender_lista_negra_interna: bool,
     *   auto_crear_alerta_atendida: bool,
     *   auto_user_id: ?int
     * }
     */
    public function obtener(): array
    {
        $defaults = $this->defaults();
        $politica = PoliticaSarlaft::query()->find(1);

        if ($politica === null) {
            return $defaults;
        }

        return [
            'sla_dias_alerta' => max((int) $politica->sla_dias_alerta, 1),
            'auto_escalar_riesgos' => $this->normalizarRiesgos((array) $politica->auto_escalar_riesgos),
            'auto_estado' => $this->valorCadena($politica->auto_estado, $defaults['auto_estado']),
            'auto_atender_lista_negra_interna' => (bool) $politica->auto_atender_lista_negra_interna,
            'auto_crear_alerta_atendida' => (bool) $politica->auto_crear_alerta_atendida,
            'auto_user_id' => $this->normalizarAutoUserId($politica->auto_user_id),
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function guardar(array $datos, ?int $updatedBy): PoliticaSarlaft
    {
        $payload = [
            'sla_dias_alerta' => max((int) ($datos['sla_dias_alerta'] ?? 1), 1),
            'auto_escalar_riesgos' => $this->normalizarRiesgos((array) ($datos['auto_escalar_riesgos'] ?? [])),
            'auto_estado' => $this->valorCadena($datos['auto_estado'] ?? null, 'en_revision'),
            'auto_atender_lista_negra_interna' => (bool) ($datos['auto_atender_lista_negra_interna'] ?? true),
            'auto_crear_alerta_atendida' => (bool) ($datos['auto_crear_alerta_atendida'] ?? true),
            'auto_user_id' => $this->normalizarAutoUserId($datos['auto_user_id'] ?? null),
            'updated_by' => $updatedBy,
        ];

        return PoliticaSarlaft::query()->updateOrCreate(
            ['id' => 1],
            $payload,
        );
    }

    /**
     * @return array{
     *   sla_dias_alerta: int,
     *   auto_escalar_riesgos: array<int, string>,
     *   auto_estado: string,
     *   auto_atender_lista_negra_interna: bool,
     *   auto_crear_alerta_atendida: bool,
     *   auto_user_id: ?int
     * }
     */
    private function defaults(): array
    {
        return [
            'sla_dias_alerta' => max((int) config('sarlaft.alerta_sla_dias', 1), 1),
            'auto_escalar_riesgos' => $this->normalizarRiesgos((array) config('sarlaft.auto_escalar_riesgos', ['alto', 'critico'])),
            'auto_estado' => (string) config('sarlaft.auto_estado', 'en_revision'),
            'auto_atender_lista_negra_interna' => (bool) config('sarlaft.auto_atender_lista_negra_interna', true),
            'auto_crear_alerta_atendida' => (bool) config('sarlaft.auto_crear_alerta_atendida', true),
            'auto_user_id' => $this->normalizarAutoUserId(config('sarlaft.auto_user_id', 1)),
        ];
    }

    /**
     * @param  array<int, mixed>  $riesgos
     * @return array<int, string>
     */
    private function normalizarRiesgos(array $riesgos): array
    {
        $permitidos = ['bajo', 'medio', 'alto', 'critico'];
        $normalizados = array_values(array_unique(array_filter(array_map(
            static fn (mixed $riesgo): string => is_string($riesgo) ? strtolower(trim($riesgo)) : '',
            $riesgos
        ), static fn (string $riesgo): bool => in_array($riesgo, $permitidos, true))));

        return $normalizados !== [] ? $normalizados : ['alto', 'critico'];
    }

    private function normalizarAutoUserId(mixed $userId): ?int
    {
        $valor = is_numeric($userId) ? (int) $userId : null;

        return ($valor !== null && $valor > 0) ? $valor : null;
    }

    private function valorCadena(mixed $valor, string $fallback): string
    {
        if (! is_string($valor)) {
            return $fallback;
        }

        $limpio = trim($valor);

        return $limpio !== '' ? $limpio : $fallback;
    }

}
