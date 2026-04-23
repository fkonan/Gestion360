<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use App\Modules\Sarlaft\Models\ListaNegraInterna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListaNegraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $uniqueRule = Rule::unique('mysql-sarlaft.sarlaft_lista_negra_interna', 'numero_documento')
            ->where('tipo_documento', $this->input('tipo_documento'));

        $estado = (string) $this->input('estado', 'activo');
        $esEdicion = $this->route('lista_negra') !== null;
        $registroActual = $this->route('lista_negra');
        $registroListaNegra = $registroActual instanceof ListaNegraInterna ? $registroActual : null;
        $requiereEvidenciaInclusion = ! $esEdicion
            || ($registroListaNegra !== null && ! is_array($registroListaNegra->evidencia_inclusion));
        $requiereEvidenciaRetiro = $estado === 'inactivo'
            && (! $registroListaNegra || ! is_array($registroListaNegra->evidencia_retiro));

        if ($this->route('lista_negra')) {
            $uniqueRule->ignore($this->route('lista_negra'));
        }

        return [
            'tipo_entidad' => 'required|string|in:persona,organizacion',
            'tipo_documento' => 'required|string|in:CC,NIT,CE,PA',
            'numero_documento' => ['required', 'string', 'max:50', $uniqueRule],
            'nombres' => 'required|string|max:500',
            'motivo' => 'required|string|max:2000',
            'estado' => 'sometimes|string|in:activo,inactivo',
            'archivo_evidencia_inclusion' => [
                $requiereEvidenciaInclusion ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
                'max:10240',
            ],
            'motivo_retiro' => $estado === 'inactivo' ? 'required|string|max:2000' : 'nullable|string|max:2000',
            'archivo_evidencia_retiro' => [
                $requiereEvidenciaRetiro ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo_evidencia_inclusion.required' => 'Debes adjuntar evidencia de inclusion.',
            'motivo_retiro.required' => 'Debes registrar el motivo de retiro para inactivar el registro.',
            'archivo_evidencia_retiro.required' => 'Debes adjuntar evidencia de retiro para inactivar el registro.',
        ];
    }
}
