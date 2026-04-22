<?php

namespace Tests\Unit;

use App\Modules\SIG\Services\ConteoPaginasDocumentoService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ConteoPaginasDocumentoServiceTest extends TestCase
{
    private array $archivosTemporales = [];

    public function test_calcula_paginas_de_pdf(): void
    {
        $service = new ConteoPaginasDocumentoService();
        $archivo = $this->crearUploadedFileDesdeRuta(
            dirname(__DIR__, 2).'/storage/app/public/pdfs/documento_pruebas.pdf',
            'documento_pruebas.pdf',
            'application/pdf'
        );

        $resultado = $service->analizar($archivo);

        $this->assertTrue($resultado['automatico']);
        $this->assertFalse($resultado['requiere_manual']);
        $this->assertSame(1, $resultado['paginas']);
    }

    public function test_calcula_paginas_de_docx_desde_metadatos(): void
    {
        $service = new ConteoPaginasDocumentoService();
        $archivo = $this->crearDocxConPaginas(7);

        $resultado = $service->analizar($archivo);

        $this->assertTrue($resultado['automatico']);
        $this->assertFalse($resultado['requiere_manual']);
        $this->assertSame(7, $resultado['paginas']);
    }

    public function test_doc_requiere_ingreso_manual(): void
    {
        $service = new ConteoPaginasDocumentoService();
        $ruta = $this->crearArchivoTemporal('.doc', 'contenido plano');
        $archivo = $this->crearUploadedFileDesdeRuta($ruta, 'documento.doc', 'application/msword');

        $resultado = $service->analizar($archivo);

        $this->assertFalse($resultado['automatico']);
        $this->assertTrue($resultado['requiere_manual']);
        $this->assertNull($resultado['paginas']);
    }

    protected function tearDown(): void
    {
        foreach ($this->archivosTemporales as $ruta) {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        parent::tearDown();
    }

    private function crearDocxConPaginas(int $paginas): UploadedFile
    {
        $ruta = $this->crearArchivoTemporal('.docx');
        $zip = new ZipArchive();
        $abierto = $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->assertTrue($abierto === true, 'No se pudo crear el archivo DOCX temporal.');

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
</Types>
XML);
        $zip->addFromString('docProps/app.xml', sprintf(<<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <Pages>%d</Pages>
</Properties>
XML, $paginas));
        $zip->close();

        return $this->crearUploadedFileDesdeRuta(
            $ruta,
            'archivo.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }

    private function crearUploadedFileDesdeRuta(string $ruta, string $nombreOriginal, string $mime): UploadedFile
    {
        return new UploadedFile($ruta, $nombreOriginal, $mime, null, true);
    }

    private function crearArchivoTemporal(string $extension, string $contenido = ''): string
    {
        $base = tempnam(sys_get_temp_dir(), 'sig_');
        $ruta = $base.$extension;

        if (is_file($base)) {
            unlink($base);
        }

        file_put_contents($ruta, $contenido);
        $this->archivosTemporales[] = $ruta;

        return $ruta;
    }
}
