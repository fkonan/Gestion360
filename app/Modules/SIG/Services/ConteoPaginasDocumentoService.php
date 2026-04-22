<?php

namespace App\Modules\SIG\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use setasign\Fpdi\Fpdi;
use Throwable;
use ZipArchive;

class ConteoPaginasDocumentoService
{
    public function analizar(UploadedFile $archivo): array
    {
        $extension = $this->obtenerExtension($archivo);

        return match ($extension) {
            'pdf' => $this->analizarPdf($archivo),
            'docx' => $this->analizarDocx($archivo),
            'doc' => [
                'extension' => 'doc',
                'paginas' => null,
                'automatico' => false,
                'requiere_manual' => true,
                'mensaje' => 'Para archivos .doc el numero de paginas debe ingresarse manualmente.',
            ],
            default => [
                'extension' => $extension,
                'paginas' => null,
                'automatico' => false,
                'requiere_manual' => true,
                'mensaje' => 'No fue posible identificar el tipo de archivo para calcular las paginas.',
            ],
        };
    }

    private function analizarPdf(UploadedFile $archivo): array
    {
        $paginas = $this->contarPaginasPdf($archivo);

        if ($paginas !== null) {
            return [
                'extension' => 'pdf',
                'paginas' => $paginas,
                'automatico' => true,
                'requiere_manual' => false,
                'mensaje' => "Se calcularon automaticamente {$paginas} paginas del PDF.",
            ];
        }

        return [
            'extension' => 'pdf',
            'paginas' => null,
            'automatico' => false,
            'requiere_manual' => true,
            'mensaje' => 'No fue posible calcular automaticamente las paginas del PDF. Puedes ingresarlas manualmente.',
        ];
    }

    private function analizarDocx(UploadedFile $archivo): array
    {
        $paginas = $this->contarPaginasDocx($archivo);

        if ($paginas !== null) {
            return [
                'extension' => 'docx',
                'paginas' => $paginas,
                'automatico' => true,
                'requiere_manual' => false,
                'mensaje' => "Se calcularon automaticamente {$paginas} paginas del documento Word.",
            ];
        }

        return [
            'extension' => 'docx',
            'paginas' => null,
            'automatico' => false,
            'requiere_manual' => true,
            'mensaje' => 'No fue posible calcular automaticamente las paginas del archivo DOCX. Puedes ingresarlas manualmente.',
        ];
    }

    private function contarPaginasPdf(UploadedFile $archivo): ?int
    {
        $ruta = $archivo->getRealPath();
        if (! $ruta) {
            return null;
        }

        try {
            $pdf = new Fpdi();
            $paginas = $pdf->setSourceFile($ruta);

            return $paginas > 0 ? (int) $paginas : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function contarPaginasDocx(UploadedFile $archivo): ?int
    {
        $ruta = $archivo->getRealPath();
        if (! $ruta || ! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive();
        $estado = $zip->open($ruta);
        if ($estado !== true) {
            return null;
        }

        try {
            $paginas = $this->leerPaginasDesdePropiedades($zip);
            if ($paginas !== null) {
                return $paginas;
            }

            return $this->leerPaginasDesdeSaltosRenderizados($zip);
        } finally {
            $zip->close();
        }
    }

    private function leerPaginasDesdePropiedades(ZipArchive $zip): ?int
    {
        $contenido = $zip->getFromName('docProps/app.xml');
        if ($contenido === false) {
            return null;
        }

        return $this->extraerEnteroXml($contenido, 'Pages');
    }

    private function leerPaginasDesdeSaltosRenderizados(ZipArchive $zip): ?int
    {
        $contenido = $zip->getFromName('word/document.xml');
        if ($contenido === false || trim($contenido) === '') {
            return null;
        }

        $dom = new DOMDocument();
        if (@$dom->loadXML($contenido) === false) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $saltos = (int) $xpath->evaluate('count(//*[local-name()="lastRenderedPageBreak"])');

        return $saltos > 0 ? $saltos + 1 : null;
    }

    private function extraerEnteroXml(string $contenido, string $nombreNodo): ?int
    {
        $dom = new DOMDocument();
        if (@$dom->loadXML($contenido) === false) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $valor = trim((string) $xpath->evaluate(sprintf('string(//*[local-name()="%s"][1])', $nombreNodo)));

        if (! preg_match('/^\d+$/', $valor)) {
            return null;
        }

        $numero = (int) $valor;

        return $numero > 0 ? $numero : null;
    }

    private function obtenerExtension(UploadedFile $archivo): string
    {
        $extension = $archivo->getClientOriginalExtension();

        if ($extension === '') {
            $extension = $archivo->extension();
        }

        return strtolower(trim((string) $extension));
    }
}
