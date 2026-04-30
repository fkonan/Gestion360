<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePoliticaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_atender_lista_negra_interna' => $this->boolean('auto_atender_lista_negra_interna'),
            'auto_crear_alerta_atendida' => $this->boolean('auto_crear_alerta_atendida'),
            'auto_user_id' => $this->filled('auto_user_id') ? (int) $this->input('auto_user_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sla_dias_alerta' => 'required|integer|min:1|max:30',
            'auto_escalar_riesgos' => 'required|array|min:1',
            'auto_escalar_riesgos.*' => 'required|string|in:bajo,medio,alto,critico',
            'auto_estado' => 'required|string|in:pendiente,en_revision,atendida,descartada',
            'auto_atender_lista_negra_interna' => 'required|boolean',
            'auto_crear_alerta_atendida' => 'required|boolean',
            'auto_user_id' => 'nullable|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sla_dias_alerta.required' => 'El SLA de alertas es obligatorio.',
            'sla_dias_alerta.min' => 'El SLA de alertas debe ser al menos 1 dia.',
            'sla_dias_alerta.max' => 'El SLA de alertas no debe superar 30 dias.',
            'auto_escalar_riesgos.required' => 'Debes seleccionar al menos un nivel de riesgo para autoescalamiento.',
            'auto_escalar_riesgos.min' => 'Debes seleccionar al menos un nivel de riesgo para autoescalamiento.',
            'auto_escalar_riesgos.*.in' => 'Uno de los niveles de riesgo seleccionados no es valido.',
            'auto_estado.in' => 'El estado automatico seleccionado no es valido.',
            'auto_user_id.min' => 'El usuario tecnico debe ser un identificador valido.',
        ];
    }
}
