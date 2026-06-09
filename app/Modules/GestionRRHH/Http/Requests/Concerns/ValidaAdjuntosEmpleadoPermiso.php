<?php

namespace App\Modules\GestionRRHH\Http\Requests\Concerns;

use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;

trait ValidaAdjuntosEmpleadoPermiso
{
    protected function agregarValidacionAdjuntos($validator): void
    {
        $validator->after(function ($validator) {
            $adjuntos = $this->input('adjuntos', []);
            if (! is_array($adjuntos)) {
                return;
            }

            try {
                $tiposValidos = app(EmpleadoPermisoDocumentoService::class)->obtenerIdsTiposDocumento();
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

                if ($tieneTipo && ! $tieneArchivo) {
                    $validator->errors()->add("adjuntos.{$indice}.archivo", 'Debes cargar un archivo para el tipo de documento seleccionado.');
                }

                if ($tieneArchivo && ! $tieneTipo) {
                    $validator->errors()->add("adjuntos.{$indice}.tipo_documento_id", 'Debes seleccionar el tipo de documento del adjunto.');
                }

                if ($tieneTipo && $tiposValidos !== [] && ! in_array($tipoDocumentoId, $tiposValidos, true)) {
                    $validator->errors()->add("adjuntos.{$indice}.tipo_documento_id", 'El tipo de documento seleccionado no existe.');
                }
            }
        });
    }
}

