<?php

namespace App\Modules\Administration\Services;

use Carbon\Carbon;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class EmpleadoPermisoPdfService
{
    private const TEXTO_PENDIENTE = 'PENDIENTE';
    private const TEXTO_NO_DISPONIBLE = 'N/D';
    private const META_IP_EQUIPO = 'IP_EQUIPO';
    private const META_MOTIVO_CODIGO = 'MOTIVO_CODIGO';
    private const META_OTRO_MOTIVO = 'OTRO_MOTIVO';

    private const RECTS = [
        'nombre' => [49.92, 614.98, 240.41, 24.84],
        'cedula' => [291.05, 614.98, 91.46, 24.84],
        'codigo' => [383.23, 614.98, 63.12, 24.84],
        'seccion' => [446.83, 614.98, 112.82, 24.84],
        'fecha_permiso' => [49.92, 552.82, 130.94, 24.84],
        'hora_salida' => [181.58, 552.82, 94.58, 24.84],
        'hora_ingreso' => [276.89, 552.82, 105.62, 24.84],
        'sexo' => [383.23, 552.82, 98.42, 24.84],
        'edad' => [482.38, 552.82, 77.28, 24.84],
        'otros_detalle' => [326.45, 325.73, 233.21, 28.32],
        'actividad' => [49.8, 242.45, 509.98, 51.48],
        'firma_empleado' => [314.21, 185.54, 245.81, 39.74],
        'firma_jefe' => [314.21, 140.18, 245.81, 39.84],
        'firma_rrhh' => [314.21, 94.94, 245.81, 39.72],
    ];

    private const RECTS_MOTIVO = [
        'REUNION_ESCOLAR' => [51.6, 477.19, 26.06, 28.32],
        'CITA_MEDICA_FAMILIARES' => [304.49, 477.19, 21.24, 28.32],
        'ESTUDIO' => [51.6, 448.15, 26.06, 28.32],
        'ACTIVIDAD_LABORAL_EXTERNA' => [304.49, 448.15, 21.24, 28.32],
        'MEDICINA_GENERAL' => [51.6, 418.99, 26.06, 28.32],
        'MEDICINA_ESPECIALIZADA' => [304.49, 418.99, 21.24, 28.32],
        'TERAPIAS' => [51.6, 389.95, 26.06, 28.32],
        'ODONTOLOGIA' => [304.49, 389.95, 21.24, 28.32],
        'URGENCIA_O_CITA_PRIORITARIA' => [51.6, 360.77, 26.06, 28.34],
        'ACCIDENTE_DE_TRABAJO' => [304.49, 360.77, 21.24, 28.34],
        'EXAMENES' => [51.6, 331.73, 26.06, 28.32],
        'OTROS' => [304.49, 331.73, 21.24, 28.32],
    ];

    public function generarDesdeDetalle(array $detalle): string
    {
        $templatePath = $this->resolverRutaPlantilla();

        $pdf = new Fpdi('P', 'pt');
        $pageCount = $pdf->setSourceFile($templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('La plantilla del permiso no contiene paginas.');
        }

        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);
        $width = (float) $size['width'];
        $height = (float) $size['height'];
        $orientation = $width > $height ? 'L' : 'P';

        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage($orientation, [$width, $height]);
        $pdf->useTemplate($templateId, 0, 0, $width, $height, true);

        $permiso = (object) ($detalle['permiso'] ?? []);
        $novedad = (object) ($detalle['novedad'] ?? []);
        $persona = (array) ($detalle['persona'] ?? []);

        $fechaInicio = $this->parsearFecha($novedad->fecha_inicio ?? null);
        $fechaFin = $this->parsearFecha($novedad->fecha_fin ?? null);

        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['nombre'], $this->normalizarTexto($persona['nombre'] ?? ''));
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['cedula'], $this->normalizarTexto($persona['identificacion'] ?? ''));
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['codigo'], $this->normalizarTexto($persona['codigo'] ?? ''));
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['seccion'], $this->normalizarTexto($persona['seccion'] ?? ''));
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['fecha_permiso'], $fechaInicio?->format('d/m/Y') ?? '');
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['hora_salida'], $fechaInicio?->format('h:i A') ?? '');
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['hora_ingreso'], $fechaFin?->format('h:i A') ?? '');
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['sexo'], $this->normalizarTexto($persona['sexo'] ?? ''));
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['edad'], $this->normalizarTexto($persona['edad'] ?? ''));

        $motivoCodigo = $this->resolverMotivoCodigo($permiso, $novedad);
        $this->marcarMotivo($pdf, $height, $motivoCodigo);

        if ($motivoCodigo === 'OTROS') {
            $this->escribirTextoEnRectangulo(
                $pdf,
                $height,
                self::RECTS['otros_detalle'],
                $this->resolverOtroMotivo($permiso, $novedad),
                8
            );
        }

        $actividad = $this->normalizarTexto($permiso->actividad ?? '') ?: $this->normalizarTexto($novedad->observacion ?? '');
        $this->escribirTextoEnRectangulo(
            $pdf,
            $height,
            self::RECTS['actividad'],
            $actividad,
            9,
            'L',
            true
        );

        $firmaEmpleado = $this->construirFirmaEmpleado($permiso, $novedad, $persona);
        $firmaJefe = $this->construirFirmaAprobador(
            $permiso->jefe_aprobado_por_nombre ?? null,
            $permiso->jefe_aprobado_por_documento ?? null
        );
        $firmaRrhh = $this->construirFirmaAprobador(
            $permiso->rrhh_aprobado_por_nombre ?? null,
            $permiso->rrhh_aprobado_por_documento ?? null
        );

        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['firma_empleado'], $firmaEmpleado, 8, 'L', true);
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['firma_jefe'], $firmaJefe, 8, 'L', true);
        $this->escribirTextoEnRectangulo($pdf, $height, self::RECTS['firma_rrhh'], $firmaRrhh, 8, 'L', true);

        return $pdf->Output('S');
    }

    private function resolverRutaPlantilla(): string
    {
        $configPath = trim((string) config('services.employee_permits.pdf_template_path', ''));

        $candidatas = [
            $configPath,
            base_path('app/Modules/Administration/Resources/Templates/FormatoPermisoSalida.pdf'),
            storage_path('app/plantillas/FormatoPermisoSalida.pdf'),
            'C:\Users\desarrollo3\Downloads\FormatoPermisoSalida.pdf',
        ];

        foreach ($candidatas as $ruta) {
            $ruta = trim((string) $ruta);
            if ($ruta !== '' && file_exists($ruta)) {
                return $ruta;
            }
        }

        throw new RuntimeException('No se encontro la plantilla PDF del permiso. Configura EMPLOYEE_PERMITS_PDF_TEMPLATE_PATH.');
    }

    private function parsearFecha(mixed $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolverMotivoCodigo(object $permiso, object $novedad): ?string
    {
        $motivo = strtoupper(trim((string) ($permiso->motivo_catalogo ?? '')));
        if ($motivo !== '' && isset(self::RECTS_MOTIVO[$motivo])) {
            return $motivo;
        }

        $observacion = (string) ($novedad->observacion ?? '');
        if (preg_match('/'.self::META_MOTIVO_CODIGO.'=([A-Z_]+)/', $observacion, $match)) {
            $motivoLegacy = strtoupper(trim((string) ($match[1] ?? '')));
            if ($motivoLegacy !== '' && isset(self::RECTS_MOTIVO[$motivoLegacy])) {
                return $motivoLegacy;
            }
        }

        return null;
    }

    private function resolverOtroMotivo(object $permiso, object $novedad): string
    {
        $otro = $this->normalizarTexto($permiso->otro_motivo ?? '');
        if ($otro !== '') {
            return $otro;
        }

        $observacion = (string) ($novedad->observacion ?? '');
        if (preg_match('/'.self::META_OTRO_MOTIVO.'=([^|]+)/', $observacion, $match)) {
            return $this->normalizarTexto((string) ($match[1] ?? ''));
        }

        return '';
    }

    private function construirFirmaEmpleado(object $permiso, object $novedad, array $persona): string
    {
        $nombre = $this->normalizarTexto($persona['nombre'] ?? '');
        $cedula = $this->normalizarTexto($persona['identificacion'] ?? '');
        $ip = $this->normalizarTexto($permiso->firma_empleado_ip ?? '');
        $observacion = (string) ($novedad->observacion ?? '');

        if ($ip === '' && preg_match('/'.self::META_IP_EQUIPO.'=([^|]+)/', $observacion, $match)) {
            $ip = $this->normalizarTexto((string) ($match[1] ?? ''));
        }

        $lineas = [
            $nombre !== '' ? $nombre : self::TEXTO_PENDIENTE,
            $cedula !== '' ? $cedula : self::TEXTO_NO_DISPONIBLE,
            'IP: '.($ip !== '' ? $ip : self::TEXTO_NO_DISPONIBLE),
        ];

        return implode("\n", $lineas);
    }

    private function construirFirmaAprobador(mixed $nombre, mixed $documento): string
    {
        $texto = trim($this->normalizarTexto($nombre ?? self::TEXTO_PENDIENTE)." ".$this->normalizarTexto($documento ?? ''));

        return $texto !== '' ? $texto : self::TEXTO_PENDIENTE;
    }

    private function marcarMotivo(Fpdi $pdf, float $pageHeight, ?string $motivoCodigo): void
    {
        if (! $motivoCodigo || ! isset(self::RECTS_MOTIVO[$motivoCodigo])) {
            return;
        }

        [$x, $yBottom, $w, $h] = self::RECTS_MOTIVO[$motivoCodigo];
        $yTop = $this->ySuperiorDesdeInferior($pageHeight, $yBottom, $h);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetXY($x, $yTop + 7);
        $pdf->Cell($w, 10, 'X', 0, 0, 'C');
    }

    private function escribirTextoEnRectangulo(
        Fpdi $pdf,
        float $pageHeight,
        array $rect,
        string $texto,
        int $fontSize = 9,
        string $align = 'L',
        bool $multiline = false
    ): void {
        $texto = $multiline
            ? $this->normalizarTextoMultilinea($texto)
            : $this->normalizarTexto($texto);
        if ($texto === '') {
            return;
        }

        [$x, $yBottom, $w, $h] = $rect;
        $yTop = $this->ySuperiorDesdeInferior($pageHeight, (float) $yBottom, (float) $h);
        $padX = 2.4;
        $padY = 1.8;
        $lineHeight = $fontSize + 1.4;
        $maxW = max(((float) $w - ($padX * 2)), 1.0);
        $maxH = max(((float) $h - ($padY * 2)), 1.0);

        $pdf->SetFont('Arial', '', $fontSize);
        if ($multiline) {
            $pdf->SetXY((float) $x + $padX, $yTop + $padY);
            $pdf->MultiCell($maxW, $lineHeight, $this->aLatin1($texto), 0, $align);

            return;
        }

        $yTexto = $yTop + max((($maxH - $lineHeight) / 2), 0.0);
        $pdf->SetXY((float) $x + $padX, $yTexto);
        $pdf->Cell($maxW, $lineHeight, $this->aLatin1($texto), 0, 0, $align);
    }

    private function ySuperiorDesdeInferior(float $pageHeight, float $yBottom, float $height): float
    {
        return $pageHeight - ($yBottom + $height);
    }

    private function normalizarTexto(mixed $valor): string
    {
        $texto = trim((string) $valor);
        $texto = preg_replace('/\s+/', ' ', $texto) ?: '';

        return $texto;
    }

    private function normalizarTextoMultilinea(mixed $valor): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", (string) $valor);
        $lineas = explode("\n", $texto);
        $lineas = array_map(function (string $linea): string {
            $linea = trim($linea);

            return preg_replace('/[ \t]+/', ' ', $linea) ?: '';
        }, $lineas);
        $lineas = array_values(array_filter($lineas, fn (string $linea): bool => $linea !== ''));

        return implode("\n", $lineas);
    }

    private function aLatin1(string $texto): string
    {
        $resultado = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);

        return $resultado === false ? $texto : $resultado;
    }
}
