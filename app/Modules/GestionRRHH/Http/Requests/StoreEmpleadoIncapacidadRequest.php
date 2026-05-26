<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Rules\IncapacidadMaxima;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoIncapacidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $service = app(EmpleadoIncapacidadService::class);
        $payload = $service->normalizarPayload($this->all());

        $this->merge($payload);
    }

    public function rules(): array
    {
        $rules = array_merge(
            EmpleadoIncapacidadService::reglasCreacion(),
            EmpleadoIncapacidadDocumentoService::reglasAdjuntos(false)
        );

        $rules['fecha_fin'] = [
            'required',
            'date_format:Y-m-d',
            'after_or_equal:fecha_inicio',
            new IncapacidadMaxima($this->input('fecha_inicio')),
        ];

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(
            EmpleadoIncapacidadService::mensajesCreacion(),
            EmpleadoIncapacidadDocumentoService::mensajesAdjuntos()
        );
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validarCatalogos($validator);
            $this->validarAdjuntos($validator);
        });
    }

    private function validarCatalogos($validator): void
    {
        try {
            $error = app(EmpleadoIncapacidadService::class)->validarCatalogos($this->all());
        } catch (\Throwable) {
            $error = null;
        }

        if ($error !== null) {
            $validator->errors()->add('catalogos', $error);
        }
    }

    private function validarAdjuntos($validator): void
    {
        $adjuntos = $this->input('adjuntos', []);
        if (! is_array($adjuntos)) {
            return;
        }

        try {
            $causaId = trim((string) $this->input('causa_id', ''));
            $tiposValidos = $causaId !== ''
                ? app(EmpleadoIncapacidadDocumentoService::class)->obtenerIdsTiposDocumentoPorCausa($causaId)
                : app(EmpleadoIncapacidadDocumentoService::class)->obtenerIdsTiposDocumento();
        } catch (\Throwable) {
            $tiposValidos = [];
        }

        foreach ($adjuntos as $indice => $adjunto) {
            if (! is_array($adjunto)) {
                continue;
            }

            $tipoDocumentoId = trim((string) ($adjunto['tipo_documento_id'] ?? ''));
            $archivo = $this->file("adjuntos.{$indice}.archivo");
            $tieneTipo = $tipoDocumentoId !== '';
            $tieneArchivo = $archivo !== null;

            if (! $tieneTipo && ! $tieneArchivo) {
                continue;
            }

            if ($tieneArchivo && ! $tieneTipo) {
                $validator->errors()->add("adjuntos.{$indice}.tipo_documento_id", 'Debes seleccionar el tipo de documento del adjunto.');
            }

            if ($tieneTipo && $tiposValidos !== [] && ! in_array($tipoDocumentoId, $tiposValidos, true)) {
                $validator->errors()->add("adjuntos.{$indice}.tipo_documento_id", 'El tipo de documento no corresponde a la causa de incapacidad seleccionada.');
            }

        }
    }
}

