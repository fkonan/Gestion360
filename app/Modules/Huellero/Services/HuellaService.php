<?php

namespace App\Modules\Huellero\Services;

use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Exceptions\FingerprintAlreadyExistsException;
use App\Modules\Huellero\Exceptions\InvalidFingerprintDataException;
use App\Modules\Huellero\Exceptions\PersonNotFoundException;
use App\Modules\Huellero\Models\PerIdentHuella;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HuellaService
{
    /**
     * Registrar una nueva huella dactilar
     */
    public function registrarHuella(int $personaId, string $dedo, string $templateHuella, int $usuarioCreacion): PerIdentHuella
    {
        // Verificar que la persona existe
        $persona = PerPersonas::find($personaId);
        if (! $persona) {
            throw new PersonNotFoundException("Persona con ID {$personaId} no encontrada");
        }

        // Verificar que no existe huella para este dedo
        $huellaExistente = PerIdentHuella::where('pe_id', $personaId)
            ->where('dedo', $dedo)
            ->where('estborrado', 0)
            ->first();

        if ($huellaExistente) {
            throw new FingerprintAlreadyExistsException("Ya existe una huella registrada para el dedo {$dedo}");
        }

        // Validar datos de la huella
        if (empty($templateHuella) || ! $this->validarTemplateHuella($templateHuella)) {
            throw new InvalidFingerprintDataException('Los datos de la huella son inválidos');
        }

        try {
            DB::beginTransaction();

            // Generar ID único para Oracle
            $nuevoId = $this->generarNuevoId();

            $huella = new PerIdentHuella([
                'id' => $nuevoId,
                'pe_id' => $personaId,
                'dedo' => $dedo,
                'huella' => $templateHuella,
                'feccaptura' => now(),
                'feccreacion' => now(),
                'usrcreacion' => $usuarioCreacion,
                'empcreacion' => $usuarioCreacion,
                'estborrado' => 0,
            ]);

            $huella->save();

            DB::commit();

            Log::info('Huella registrada exitosamente', [
                'persona_id' => $personaId,
                'dedo' => $dedo,
                'huella_id' => $nuevoId,
                'usuario_creacion' => $usuarioCreacion,
            ]);

            return $huella;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar huella', [
                'persona_id' => $personaId,
                'dedo' => $dedo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar una huella existente
     */
    public function actualizarHuella(int $huellaId, string $templateHuella, int $usuarioModificacion): PerIdentHuella
    {
        $huella = PerIdentHuella::find($huellaId);

        if (! $huella || ! $huella->estaActiva()) {
            throw new PersonNotFoundException("Huella con ID {$huellaId} no encontrada");
        }

        if (! $this->validarTemplateHuella($templateHuella)) {
            throw new InvalidFingerprintDataException('Los datos de la huella son inválidos');
        }

        try {
            $huella->update([
                'huella' => $templateHuella,
                'feccaptura' => now(),
                'fecmodifica' => now(),
                'usrmodifica' => $usuarioModificacion,
                'empmodifica' => $usuarioModificacion,
            ]);

            Log::info('Huella actualizada exitosamente', [
                'huella_id' => $huellaId,
                'usuario_modificacion' => $usuarioModificacion,
            ]);

            return $huella->fresh();

        } catch (\Exception $e) {
            Log::error('Error al actualizar huella', [
                'huella_id' => $huellaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar una huella (soft delete)
     */
    public function eliminarHuella(int $huellaId, int $usuarioModificacion): bool
    {
        $huella = PerIdentHuella::find($huellaId);

        if (! $huella || ! $huella->estaActiva()) {
            throw new PersonNotFoundException("Huella con ID {$huellaId} no encontrada");
        }

        try {
            $huella->update([
                'estborrado' => 1,
                'fecmodifica' => now(),
                'usrmodifica' => $usuarioModificacion,
                'empmodifica' => $usuarioModificacion,
            ]);

            Log::info('Huella eliminada exitosamente', [
                'huella_id' => $huellaId,
                'usuario_modificacion' => $usuarioModificacion,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error al eliminar huella', [
                'huella_id' => $huellaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtener huellas de una persona
     */
    public function obtenerHuellasPersona(int $personaId): \Illuminate\Database\Eloquent\Collection
    {
        return PerIdentHuella::where('pe_id', $personaId)
            ->activas()
            ->orderBy('dedo')
            ->get();
    }

    /**
     * Buscar persona por template de huella
     */
    public function buscarPersonaPorHuella(string $templateBusqueda): ?PerPersonas
    {
        // En un sistema real, esto debería usar algoritmos de matching biométrico
        // Por ahora, simulamos una búsqueda exacta
        $huella = PerIdentHuella::where('huella', $templateBusqueda)
            ->activas()
            ->first();

        return $huella ? $huella->persona : null;
    }

    /**
     * Verificar si una huella pertenece a una persona
     */
    public function verificarHuellaPersona(int $personaId, string $templateVerificacion): bool
    {
        $huellas = $this->obtenerHuellasPersona($personaId);

        foreach ($huellas as $huella) {
            if ($this->compararTemplates($huella->huella, $templateVerificacion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener estadísticas de huellas
     */
    public function obtenerEstadisticas(): array
    {
        return [
            'total_personas_con_huellas' => PerPersonas::whereHas('huellas')->count(),
            'total_huellas_registradas' => PerIdentHuella::activas()->count(),
            'huellas_por_dedo' => PerIdentHuella::activas()
                ->select('dedo', DB::raw('COUNT(*) as total'))
                ->groupBy('dedo')
                ->get()
                ->pluck('total', 'dedo')
                ->toArray(),
            'registros_recientes' => PerIdentHuella::activas()
                ->where('feccreacion', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /**
     * Validar template de huella
     */
    private function validarTemplateHuella(string $template): bool
    {
        // Validaciones básicas
        if (strlen($template) < 100) {
            return false;
        }

        // Verificar que es base64 válido
        if (base64_decode($template, true) === false) {
            return false;
        }

        return true;
    }

    /**
     * Comparar dos templates de huella
     */
    private function compararTemplates(string $template1, string $template2): bool
    {
        // En un sistema real, esto debería usar algoritmos de matching biométrico
        // Por ahora, simulamos una comparación exacta
        return $template1 === $template2;
    }

    /**
     * Generar nuevo ID para Oracle
     */
    private function generarNuevoId(): int
    {
        $maxId = PerIdentHuella::max('id') ?? 0;

        return $maxId + 1;
    }
}
