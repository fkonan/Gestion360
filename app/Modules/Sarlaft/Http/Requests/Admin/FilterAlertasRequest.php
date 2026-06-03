<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterAlertasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search', ''));
        $estado = trim((string) $this->input('estado', ''));
        $riesgo = trim((string) $this->input('riesgo', ''));
        $tab = trim((string) $this->input('tab', ''));

        $this->merge([
            'search' => $search !== '' ? $search : null,
            'estado' => $estado !== '' ? $estado : null,
            'riesgo' => $riesgo !== '' ? $riesgo : null,
            'tab' => in_array($tab, ['pendientes', 'cerradas'], true) ? $tab : 'pendientes',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:120',
            'estado' => 'nullable|string|in:pendiente,en_revision,atendida,descartada',
            'riesgo' => 'nullable|string|in:bajo,medio,alto,critico,vinculante,restrictiva',
            'tab' => 'nullable|string|in:pendientes,cerradas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'La busqueda no debe superar 120 caracteres.',
            'estado.in' => 'El estado seleccionado no es valido.',
            'riesgo.in' => 'El nivel de riesgo seleccionado no es valido.',
        ];
    }
}
