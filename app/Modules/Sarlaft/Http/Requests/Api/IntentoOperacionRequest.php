<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IntentoOperacionRequest extends FormRequest
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
        return [
            'tipo_operacion' => 'required|string|in:pasaje,carga,contrato,compra,pago,venta',
            'referencia_externa' => 'nullable|string|max:100',
            'fecha_operacion' => 'required|date',
            'origen' => 'nullable|string|max:200',
            'destino' => 'nullable|string|max:200',
            'monto' => 'nullable|numeric|min:0',
            'moneda' => 'nullable|string|size:3',
            'descripcion' => 'nullable|string|max:500',
            'contexto' => 'nullable|array',
            'personas' => 'required|array|min:1',
            'personas.*.tipo_documento' => 'required|string|max:20',
            'personas.*.numero_documento' => 'required|string|max:50',
            'personas.*.nombre' => 'nullable|string|max:300',
            'personas.*.rol' => 'required|string|in:pasajero,remitente,destinatario,contratista,proveedor,cliente,representante_legal',
            'personas.*.tipo_lista' => 'required|string|in:vinculante,restrictiva',
            'personas.*.lista_nombre' => 'required|string|max:150',
            'personas.*.detalle_coincidencia' => 'nullable|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_operacion.in' => 'El tipo de operación debe ser: pasaje, carga, contrato, compra, pago o venta.',
            'personas.required' => 'Debe reportar al menos una persona con coincidencia.',
            'personas.*.tipo_lista.in' => 'El tipo de lista debe ser "vinculante" o "restrictiva".',
            'personas.*.rol.in' => 'El rol debe ser: pasajero, remitente, destinatario, contratista, proveedor, cliente o representante_legal.',
        ];
    }
}
