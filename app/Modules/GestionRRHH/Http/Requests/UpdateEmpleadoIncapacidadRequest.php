<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpleadoIncapacidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $service = app(EmpleadoIncapacidadService::class);
        $payload = $service->normalizarPayload($this->all());
        $payload['identificacion'] = trim((string) ($this->input('identificacion') ?? ''));

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'causa_id' => 'required|string|max:100',
            'diagnostico_id' => 'required|string|max:100',
            'eps_id' => 'required|string|max:100',
            'arl_id' => 'required|string|max:100',
            'tipo_incapacidad' => 'required|string|in:'.implode(',', array_keys(EmpleadoIncapacidadService::opcionesTipoIncapacidad())),
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'observacion' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return EmpleadoIncapacidadService::mensajesCreacion();
    }
}
