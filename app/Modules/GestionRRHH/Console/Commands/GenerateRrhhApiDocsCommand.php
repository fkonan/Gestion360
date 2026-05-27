<?php

namespace App\Modules\GestionRRHH\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class GenerateRrhhApiDocsCommand extends Command
{
    /**
     * Endpoints principales a documentar para integracion funcional.
     *
     * @var array<string, array{nombre:string,proposito:string,errores?:array<int,string>}>
     */
    private const MAIN_ENDPOINTS = [
        'POST /auth/token' => [
            'nombre' => 'Auth',
            'proposito' => 'Obtener token de acceso para consumir la API.',
            'errores' => [
                '401: credenciales invalidas.',
                '403: scope no permitido para el cliente.',
            ],
        ],
        'POST /empleados/permisos/radicar' => [
            'nombre' => 'Radicar permiso',
            'proposito' => 'Registrar una solicitud de permiso.',
            'errores' => [
                '422: validacion (fechas/horas/motivo/campos requeridos).',
                '403: token sin scope de permisos.',
            ],
        ],
        'POST /empleados/incapacidades/radicar' => [
            'nombre' => 'Radicar incapacidad',
            'proposito' => 'Registrar una solicitud de incapacidad.',
            'errores' => [
                '422: validacion (causa, diagnostico, eps, arl, fechas, adjuntos).',
                '403: token sin scope de incapacidades.',
            ],
        ],
        'POST /empleados/vacaciones/radicar' => [
            'nombre' => 'Radicar vacaciones',
            'proposito' => 'Registrar una solicitud de vacaciones.',
            'errores' => [
                '422: validacion (fecha_inicio, fecha_fin y carta obligatoria).',
                '403: token sin scope de permisos.',
            ],
        ],
        'GET /empleados/novedades' => [
            'nombre' => 'Listar solicitudes',
            'proposito' => 'Consultar solicitudes/novedades con filtros.',
            'errores' => [
                '422: validacion de filtros.',
                '403: consulta de tercero sin permisos RRHH.',
            ],
        ],
        'POST /empleados/solicitudes/{idNovedad}/anular' => [
            'nombre' => 'Anular solicitud',
            'proposito' => 'Anular una solicitud vigente del empleado.',
            'errores' => [
                '404: solicitud no encontrada.',
                '422: estado no anulable o motivo faltante.',
            ],
        ],
        'GET /empleados/solicitudes/equipo-jefe' => [
            'nombre' => 'Listar solicitudes empleados - JEFE',
            'proposito' => 'Consultar bandeja de solicitudes del equipo del jefe.',
            'errores' => [
                '403: actor sin permisos de jefe.',
                '422: estado_flujo invalido para bandeja jefe.',
            ],
        ],
        'POST /empleados/solicitudes/{idNovedad}/gestionar-jefe' => [
            'nombre' => 'Gestionar solicitud - JEFE',
            'proposito' => 'Aprobar o rechazar solicitud por parte del jefe.',
            'errores' => [
                '404: solicitud no encontrada.',
                '422: accion invalida o motivo_rechazo faltante.',
                '403: actor sin permisos de jefe.',
            ],
        ],
        'POST /empleados/solicitudes/{idNovedad}/gestionar-rrhh' => [
            'nombre' => 'Gestionar solicitud - RRHH',
            'proposito' => 'Gestionar solicitud por parte de RRHH.',
            'errores' => [
                '404: solicitud no encontrada.',
                '422: accion invalida o motivo_rechazo faltante.',
                '403: actor sin permisos RRHH.',
            ],
        ],
    ];

    protected $signature = 'docs:rrhh-api {--base-url= : Base URL para ejemplos, p.ej. https://autogestion.copetran.com.co/gestion}';

    protected $description = 'Genera documentacion API RRHH (OpenAPI JSON + Markdown + HTML + PDF).';

    public function handle(): int
    {
        $this->components->info('Exportando OpenAPI con Scramble...');
        $exitCode = Artisan::call('scramble:export', [], $this->output);

        if ($exitCode !== self::SUCCESS) {
            $this->components->error('No fue posible exportar OpenAPI con Scramble.');

            return self::FAILURE;
        }

        $exportPath = base_path((string) config('scramble.export_path', 'scramble-openapi-gestionrrhh-v2.json'));
        if (! File::exists($exportPath)) {
            $this->components->error('No se encontro el archivo OpenAPI exportado: '.$exportPath);

            return self::FAILURE;
        }

        $openApi = json_decode((string) File::get($exportPath), true);
        if (! is_array($openApi) || ! isset($openApi['paths']) || ! is_array($openApi['paths'])) {
            $this->components->error('El archivo OpenAPI no tiene una estructura valida.');

            return self::FAILURE;
        }

        $docsDir = base_path('docs');
        $openApiDir = $docsDir.DIRECTORY_SEPARATOR.'openapi';
        $openApiTarget = $openApiDir.DIRECTORY_SEPARATOR.'gestionrrhh-v2.json';
        $mdTarget = $docsDir.DIRECTORY_SEPARATOR.'gestionrrhh-api-novedades.md';
        $htmlTarget = $docsDir.DIRECTORY_SEPARATOR.'gestionrrhh-api-novedades.html';
        $pdfTarget = $docsDir.DIRECTORY_SEPARATOR.'gestionrrhh-api-novedades.pdf';

        File::ensureDirectoryExists($docsDir);
        File::ensureDirectoryExists($openApiDir);
        File::copy($exportPath, $openApiTarget);

        $baseUrl = $this->option('base-url');
        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            $baseUrl = rtrim((string) config('app.url'), '/');
        } else {
            $baseUrl = rtrim($baseUrl, '/');
        }

        $markdown = $this->buildMarkdown($openApi, $baseUrl);
        $html = $this->buildHtml($openApi, $baseUrl);

        File::put($mdTarget, $markdown);
        File::put($htmlTarget, $html);

        try {
            Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->save($pdfTarget);
        } catch (Throwable $exception) {
            $this->components->error('No fue posible generar PDF: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('OpenAPI: <info>'.$openApiTarget.'</info>');
        $this->line('Markdown: <info>'.$mdTarget.'</info>');
        $this->line('HTML: <info>'.$htmlTarget.'</info>');
        $this->line('PDF: <info>'.$pdfTarget.'</info>');
        $this->components->info('Documentacion generada correctamente.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{method:string,path:string,operation:array<string,mixed>}>
     */
    private function collectOperations(array $openApi): array
    {
        $indexed = [];
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];

        foreach (($openApi['paths'] ?? []) as $path => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            foreach ($allowedMethods as $method) {
                if (! isset($pathItem[$method]) || ! is_array($pathItem[$method])) {
                    continue;
                }

                $key = strtoupper($method).' /'.ltrim((string) $path, '/');
                $indexed[$key] = [
                    'method' => strtoupper($method),
                    'path' => (string) $path,
                    'operation' => $pathItem[$method],
                ];
            }
        }

        $operations = [];
        foreach (array_keys(self::MAIN_ENDPOINTS) as $mainKey) {
            if (isset($indexed[$mainKey])) {
                $operations[] = $indexed[$mainKey];
            }
        }

        return $operations;
    }

    private function buildMarkdown(array $openApi, string $baseUrl): string
    {
        $lines = [];
        $operations = $this->collectOperations($openApi);

        $lines[] = '# API RRHH - Guia Funcional de Consumo (v2)';
        $lines[] = '';
        $lines[] = 'Generado: '.now()->format('Y-m-d H:i:s');
        $lines[] = '';
        $lines[] = 'Base URL de pruebas: `'.$baseUrl.'/api/v2`';
        $lines[] = '';
        $lines[] = 'Autenticacion: `Authorization: Bearer <token>` y `Accept: application/json`.';
        $lines[] = '';
        $lines[] = 'Campos canonicos: `documento_actor`, `documento_persona`, `nombre_actor`.';
        $lines[] = 'En esta guia no se documentan aliases legacy.';
        $lines[] = '';
        $lines[] = '## Inicio Rapido';
        $lines[] = '';
        $lines[] = '1. Solicita token en `POST /auth/token`.';
        $lines[] = '2. Usa el token en `Authorization: Bearer <token>`.';
        $lines[] = '3. Consume endpoints de radicacion, consulta y gestion segun el rol.';
        $lines[] = '';
        $lines[] = '## Endpoints cubiertos';
        $lines[] = '';
        $lines[] = '| # | Endpoint | Metodo |';
        $lines[] = '|---|---|---|';
        $index = 1;
        foreach (self::MAIN_ENDPOINTS as $key => $meta) {
            [$method, $path] = explode(' ', $key, 2);
            $lines[] = '| '.$index.' | '.$meta['nombre'].' (`'.$path.'`) | `'.$method.'` |';
            $index++;
        }
        $lines[] = '';

        $counter = 1;
        foreach ($operations as $op) {
            $operation = $op['operation'];
            $meta = $this->endpointMeta($op['method'], $op['path']);
            $title = $meta['nombre'];

            $lines[] = '## '.$counter.'. '.$title;
            $lines[] = '';
            $lines[] = '**Metodo y ruta:** `'.$op['method'].' '.$op['path'].'`';
            $lines[] = '';
            $lines[] = '**Para que sirve:** '.$meta['proposito'];
            $lines[] = '';
            $lines[] = '**URL completa:** `'.$baseUrl.'/api/v2/'.$this->normalizePathForUrl($op['path']).'`';
            $lines[] = '';

            $parameters = $this->extractParameters($operation, $openApi);
            if ($parameters !== []) {
                $lines[] = '**Parametros**';
                $lines[] = '';
                $lines[] = '| Campo | Ubicacion | Tipo | Requerido | Descripcion |';
                $lines[] = '|---|---|---|---|---|';
                foreach ($parameters as $parameter) {
                    $lines[] = '| `'.$parameter['name'].'` | '.$parameter['in'].' | '.$parameter['type'].' | '.$parameter['required'].' | '.$parameter['description'].' |';
                }
                $lines[] = '';
            }

            $requestBody = $this->extractRequestBodyFields($operation, $openApi);
            if ($requestBody !== []) {
                $lines[] = '**Body (`'.$requestBody['content_type'].'`)**';
                $lines[] = '';
                $lines[] = '| Campo | Tipo | Requerido | Descripcion |';
                $lines[] = '|---|---|---|---|';
                foreach ($requestBody['fields'] as $field) {
                    $lines[] = '| `'.$field['name'].'` | '.$field['type'].' | '.$field['required'].' | '.$field['description'].' |';
                }
                $lines[] = '';
            }

            $responses = $this->extractResponses($operation);
            if ($responses !== []) {
                $lines[] = '**Respuesta esperada**';
                $lines[] = '';
                $lines[] = '| HTTP | Descripcion |';
                $lines[] = '|---|---|';
                foreach ($responses as $response) {
                    $lines[] = '| `'.$response['status'].'` | '.$response['description'].' |';
                }
                $lines[] = '';
            }

            if (! empty($meta['errores']) && is_array($meta['errores'])) {
                $lines[] = '**Errores comunes**';
                foreach ($meta['errores'] as $error) {
                    $lines[] = '- '.$error;
                }
                $lines[] = '';
            }

            $example = $this->endpointExample($op['method'], $op['path']);
            if (is_array($example) && $example !== []) {
                if (! empty($example['request'])) {
                    $lines[] = '**Ejemplo Request**';
                    $lines[] = '';
                    $lines[] = '```http';
                    $lines[] = rtrim((string) $example['request']);
                    $lines[] = '```';
                    $lines[] = '';
                }

                if (! empty($example['response'])) {
                    $lines[] = '**Ejemplo Response (200)**';
                    $lines[] = '';
                    $lines[] = '```json';
                    $lines[] = rtrim((string) $example['response']);
                    $lines[] = '```';
                    $lines[] = '';
                }
            }

            $counter++;
        }

        return implode("\n", $lines)."\n";
    }

    private function buildHtml(array $openApi, string $baseUrl): string
    {
        $operations = $this->collectOperations($openApi);
        $generatedAt = now()->format('Y-m-d H:i:s');
        $items = [];
        $counter = 1;
        $summaryItems = [];

        $n = 1;
        foreach (self::MAIN_ENDPOINTS as $key => $meta) {
            [$method, $path] = explode(' ', $key, 2);
            $summaryItems[] = '<tr><td>'.$n.'</td><td>'.$this->e($meta['nombre']).'</td><td><code>'.$this->e($method).'</code></td><td><code>'.$this->e($path).'</code></td></tr>';
            $n++;
        }

        foreach ($operations as $op) {
            $operation = $op['operation'];
            $meta = $this->endpointMeta($op['method'], $op['path']);
            $title = $meta['nombre'];

            $section = [];
            $section[] = '<section class="endpoint">';
            $section[] = '<h2>'.$counter.'. '.$this->e($title).'</h2>';
            $section[] = '<p><span class="pill pill-method">'.$this->e($op['method']).'</span> <code>'.$this->e($op['path']).'</code></p>';
            $section[] = '<p><strong>Para que sirve:</strong> '.$this->e($meta['proposito']).'</p>';
            $section[] = '<p><strong>URL completa:</strong> <code>'.$this->e($baseUrl.'/api/v2/'.$this->normalizePathForUrl($op['path'])).'</code></p>';

            $parameters = $this->extractParameters($operation, $openApi);
            if ($parameters !== []) {
                $section[] = '<h3>Parametros</h3>';
                $section[] = '<table><thead><tr><th>Campo</th><th>Ubicacion</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr></thead><tbody>';
                foreach ($parameters as $parameter) {
                    $section[] = '<tr><td><code>'.$this->e($parameter['name']).'</code></td><td>'.$this->e($parameter['in']).'</td><td>'.$this->e($parameter['type']).'</td><td>'.$this->e($parameter['required']).'</td><td>'.$this->e($parameter['description']).'</td></tr>';
                }
                $section[] = '</tbody></table>';
            }

            $requestBody = $this->extractRequestBodyFields($operation, $openApi);
            if ($requestBody !== []) {
                $section[] = '<h3>Body ('.$this->e($requestBody['content_type']).')</h3>';
                $section[] = '<table><thead><tr><th>Campo</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr></thead><tbody>';
                foreach ($requestBody['fields'] as $field) {
                    $section[] = '<tr><td><code>'.$this->e($field['name']).'</code></td><td>'.$this->e($field['type']).'</td><td>'.$this->e($field['required']).'</td><td>'.$this->e($field['description']).'</td></tr>';
                }
                $section[] = '</tbody></table>';
            }

            $responses = $this->extractResponses($operation);
            if ($responses !== []) {
                $section[] = '<h3>Respuesta esperada</h3>';
                $section[] = '<table><thead><tr><th>HTTP</th><th>Descripcion</th></tr></thead><tbody>';
                foreach ($responses as $response) {
                    $section[] = '<tr><td><code>'.$this->e($response['status']).'</code></td><td>'.$this->e($response['description']).'</td></tr>';
                }
                $section[] = '</tbody></table>';
            }

            if (! empty($meta['errores']) && is_array($meta['errores'])) {
                $section[] = '<h3>Errores comunes</h3>';
                $section[] = '<ul>';
                foreach ($meta['errores'] as $error) {
                    $section[] = '<li>'.$this->e((string) $error).'</li>';
                }
                $section[] = '</ul>';
            }

            $example = $this->endpointExample($op['method'], $op['path']);
            if (is_array($example) && $example !== []) {
                if (! empty($example['request'])) {
                    $section[] = '<h3>Ejemplo Request</h3>';
                    $section[] = '<pre><code>'.$this->e((string) $example['request']).'</code></pre>';
                }

                if (! empty($example['response'])) {
                    $section[] = '<h3>Ejemplo Response (200)</h3>';
                    $section[] = '<pre><code>'.$this->e((string) $example['response']).'</code></pre>';
                }
            }

            $section[] = '</section>';
            $items[] = implode("\n", $section);
            $counter++;
        }

        return '<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>API RRHH - Guia Funcional v2</title>
  <style>
    @page { size: A4 portrait; margin: 12mm; }
    body { font-family: "Segoe UI", Arial, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.45; }
    h1 { font-size: 24px; margin: 0 0 8px; color: #0b1f44; }
    h2 { font-size: 16px; margin: 0 0 8px; color: #0b1f44; }
    h3 { font-size: 15px; margin: 10px 0 6px; color: #0f172a; }
    p { margin: 6px 0; }
    .header { background: #f8fafc; border: 1px solid #dbeafe; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
    .meta { color: #334155; margin: 2px 0; }
    .pill { display: inline-block; padding: 3px 8px; border-radius: 999px; font-weight: 700; font-size: 11px; letter-spacing: 0; }
    .pill-method { background: #dbeafe; color: #1d4ed8; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: Consolas, monospace; font-size: 12px; }
    .summary { margin: 10px 0 14px; }
    .endpoint { margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; page-break-inside: avoid; background: #fff; }
    table { width: 100%; border-collapse: collapse; margin: 6px 0 10px; font-size: 12px; }
    th, td { border: 1px solid #dbe4f0; padding: 7px; text-align: left; vertical-align: top; }
    th { background: #f8fafc; color: #0f172a; font-weight: 700; }
    tr:nth-child(even) td { background: #fafcff; }
    ul { margin: 4px 0 8px 16px; padding: 0; }
    li { margin: 2px 0; }
    pre { background: #0f172a; color: #e2e8f0; padding: 10px; border-radius: 8px; overflow-wrap: anywhere; white-space: pre-wrap; font-size: 11px; }
    pre code { background: transparent; color: inherit; padding: 0; }
  </style>
</head>
<body>
  <div class="header">
    <h1>API RRHH - Guia Funcional de Consumo (v2)</h1>
    <p class="meta">Generado: '.$this->e($generatedAt).'</p>
    <p class="meta">Base URL de pruebas: <code>'.$this->e($baseUrl.'/api/v2').'</code></p>
    <p class="meta">Autenticacion: <code>Authorization: Bearer &lt;token&gt;</code> y <code>Accept: application/json</code></p>
    <p class="meta">Campos canonicos: <code>documento_actor</code>, <code>documento_persona</code>, <code>nombre_actor</code>.</p>
  </div>
  <section class="summary">
    <h2>Inicio Rapido</h2>
    <ol>
      <li>Solicita token en <code>POST /auth/token</code>.</li>
      <li>Usa el token en <code>Authorization: Bearer &lt;token&gt;</code>.</li>
      <li>Consume endpoints de radicacion, consulta y gestion segun el rol.</li>
    </ol>
  </section>
  <section class="summary">
    <h2>Resumen de endpoints</h2>
    <table><thead><tr><th>#</th><th>Operacion</th><th>Metodo</th><th>Ruta</th></tr></thead><tbody>'.implode("\n", $summaryItems).'</tbody></table>
  </section>
  '.implode("\n", $items).'
</body>
</html>';
    }

    /**
     * @return array<int, array{name:string,in:string,type:string,required:string,description:string}>
     */
    private function extractParameters(array $operation, array $openApi): array
    {
        $result = [];
        $parameters = $operation['parameters'] ?? [];
        $components = $openApi['components'] ?? [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            if (isset($parameter['$ref'])) {
                $parameter = $this->resolveRef((string) $parameter['$ref'], $components);
                if (! is_array($parameter)) {
                    continue;
                }
            }

            $schema = is_array($parameter['schema'] ?? null) ? $parameter['schema'] : [];
            $fieldName = (string) ($parameter['name'] ?? '');
            $fieldName = $this->canonicalFieldName($fieldName);

            if ($this->isLegacyOnlyField($fieldName)) {
                continue;
            }

            $result[] = [
                'name' => $fieldName,
                'in' => (string) ($parameter['in'] ?? ''),
                'type' => $this->schemaType($schema),
                'required' => ! empty($parameter['required']) ? 'si' : 'no',
                'description' => $this->clean((string) ($parameter['description'] ?? '')),
            ];
        }

        $result = $this->mergeFieldRows($result);
        usort($result, fn (array $a, array $b): int => strcmp($b['required'], $a['required']));

        return $result;
    }

    /**
     * @return array{content_type:string,fields:array<int, array{name:string,type:string,required:string,description:string}>}|array{}
     */
    private function extractRequestBodyFields(array $operation, array $openApi): array
    {
        $requestBody = $operation['requestBody'] ?? null;
        $components = $openApi['components'] ?? [];

        if (is_array($requestBody) && isset($requestBody['$ref'])) {
            $requestBody = $this->resolveRef((string) $requestBody['$ref'], $components);
        }

        if (! is_array($requestBody)) {
            return [];
        }

        $content = is_array($requestBody['content'] ?? null) ? $requestBody['content'] : [];
        if ($content === []) {
            return [];
        }

        $preferred = ['multipart/form-data', 'application/json', 'application/x-www-form-urlencoded'];
        $contentType = '';
        foreach ($preferred as $candidate) {
            if (isset($content[$candidate])) {
                $contentType = $candidate;
                break;
            }
        }
        if ($contentType === '') {
            $contentType = (string) array_key_first($content);
        }

        $schema = is_array($content[$contentType]['schema'] ?? null) ? $content[$contentType]['schema'] : [];
        $resolved = $this->resolveSchema($schema, $components);
        if (! is_array($resolved)) {
            return [];
        }

        $fields = [];
        $required = is_array($resolved['required'] ?? null) ? $resolved['required'] : [];
        $requiredCanonical = collect($required)
            ->map(fn ($item) => $this->canonicalFieldName((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $properties = is_array($resolved['properties'] ?? null) ? $resolved['properties'] : [];
        foreach ($properties as $fieldName => $fieldSchema) {
            if (! is_array($fieldSchema)) {
                continue;
            }

            $fieldName = $this->canonicalFieldName((string) $fieldName);
            if ($this->isLegacyOnlyField($fieldName)) {
                continue;
            }

            $fieldSchema = $this->resolveSchema($fieldSchema, $components);
            $fields[] = [
                'name' => $fieldName,
                'type' => $this->schemaType(is_array($fieldSchema) ? $fieldSchema : []),
                'required' => in_array($fieldName, $requiredCanonical, true) ? 'si' : 'no',
                'description' => $this->clean((string) ($fieldSchema['description'] ?? '')),
            ];
        }

        $fields = $this->mergeFieldRows($fields);
        usort($fields, fn (array $a, array $b): int => strcmp($b['required'], $a['required']));

        return [
            'content_type' => $contentType,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<int, array{status:string,description:string}>
     */
    private function extractResponses(array $operation): array
    {
        $result = [];
        $responses = $operation['responses'] ?? [];
        if (! is_array($responses)) {
            return $result;
        }

        foreach ($responses as $status => $response) {
            if (! is_array($response)) {
                continue;
            }

            $result[] = [
                'status' => (string) $status,
                'description' => $this->clean((string) ($response['description'] ?? 'Sin descripcion')),
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>
     */
    private function resolveSchema(array $schema, array $components): array
    {
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $resolved = $this->resolveRef($schema['$ref'], $components);
            if (is_array($resolved)) {
                return $resolved;
            }
        }

        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            $merged = ['type' => 'object', 'properties' => [], 'required' => []];
            foreach ($schema['allOf'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $resolved = $this->resolveSchema($item, $components);
                $merged['properties'] = array_merge($merged['properties'], (array) ($resolved['properties'] ?? []));
                $merged['required'] = array_values(array_unique(array_merge(
                    (array) $merged['required'],
                    (array) ($resolved['required'] ?? [])
                )));
            }

            return $merged;
        }

        return $schema;
    }

    /**
     * @return mixed
     */
    private function resolveRef(string $ref, array $components)
    {
        if (! str_starts_with($ref, '#/components/')) {
            return null;
        }

        $path = str_replace('#/components/', '', $ref);
        $parts = explode('/', $path);
        $cursor = $components;

        foreach ($parts as $part) {
            if (! is_array($cursor) || ! array_key_exists($part, $cursor)) {
                return null;
            }
            $cursor = $cursor[$part];
        }

        return $cursor;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function schemaType(array $schema): string
    {
        if (isset($schema['type']) && is_string($schema['type'])) {
            return $schema['type'];
        }

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $ref = explode('/', $schema['$ref']);

            return 'ref:'.end($ref);
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            return 'enum';
        }

        return 'mixed';
    }

    private function clean(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value === '' ? '-' : str_replace('|', '/', $value);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function normalizePathForUrl(string $path): string
    {
        return ltrim($path, '/');
    }

    private function canonicalFieldName(string $name): string
    {
        $name = trim($name);

        return match ($name) {
            'documento_usuario', 'documento_radica' => 'documento_actor',
            'identificacion', 'identificacion_persona' => 'documento_persona',
            'nombre_usuario' => 'nombre_actor',
            default => $name,
        };
    }

    private function isLegacyOnlyField(string $name): bool
    {
        return in_array($name, [
            'documento_usuario',
            'documento_radica',
            'identificacion',
            'identificacion_persona',
            'nombre_usuario',
        ], true);
    }

    /**
     * @param  array<int, array{name:string,type:string,required:string,description:string}|array{name:string,in:string,type:string,required:string,description:string}>  $rows
     * @return array<int, array<string, string>>
     */
    private function mergeFieldRows(array $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }

            if (! isset($merged[$name])) {
                $merged[$name] = $row;
                continue;
            }

            $current = $merged[$name];
            $merged[$name]['required'] = ($current['required'] ?? 'no') === 'si' || ($row['required'] ?? 'no') === 'si'
                ? 'si'
                : 'no';

            if (($current['description'] ?? '-') === '-' && ($row['description'] ?? '-') !== '-') {
                $merged[$name]['description'] = $row['description'];
            }
        }

        return array_values($merged);
    }

    /**
     * @return array{request?:string,response?:string}
     */
    private function endpointExample(string $method, string $path): array
    {
        $key = strtoupper($method).' /'.ltrim($path, '/');

        return match ($key) {
            'POST /auth/token' => [
                'request' => "POST /api/v2/auth/token\nContent-Type: application/json\n\n{\n  \"grant_type\": \"client_credentials\",\n  \"client_id\": \"app_movil\",\n  \"client_secret\": \"***\",\n  \"scope\": \"empleados.novedades.admin\"\n}",
                'response' => "{\n  \"ok\": true,\n  \"data\": {\n    \"token_type\": \"Bearer\",\n    \"access_token\": \"eyJ...\",\n    \"expires_in\": 3600,\n    \"scope\": \"empleados.novedades.admin\"\n  }\n}",
            ],
            'POST /empleados/permisos/radicar' => [
                'request' => "POST /api/v2/empleados/permisos/radicar\nAuthorization: Bearer <token>\nContent-Type: multipart/form-data\n\n- documento_actor: 1007408720\n- documento_persona: 1007408720\n- fecha_permiso: 2026-05-20\n- hora_salida: 08:00\n- hora_ingreso: 12:00\n- motivo: CITA_MEDICA_FAMILIARES\n- actividad: Cita medica\n- adjuntos[0][tipo_documento_id]: <uuid>\n- adjuntos[0][archivo]: <file>",
                'response' => "{\n  \"ok\": true,\n  \"message\": \"Permiso radicado correctamente.\",\n  \"data\": {\n    \"id_novedad\": \"uuid\",\n    \"estado\": \"RADICADO\",\n    \"notificacion_email_enviada\": true\n  }\n}",
            ],
            'POST /empleados/incapacidades/radicar' => [
                'request' => "POST /api/v2/empleados/incapacidades/radicar\nAuthorization: Bearer <token>\nContent-Type: multipart/form-data\n\n- documento_actor: 1007408720\n- documento_persona: 1007408720\n- causa_id: <uuid>\n- diagnostico_id: <uuid>\n- eps_id: <uuid>\n- arl_id: <uuid>\n- tipo_incapacidad: INICIO\n- fecha_inicio: 2026-05-20\n- fecha_fin: 2026-05-22\n- observacion: Incapacidad por control medico\n- adjuntos[0][tipo_documento_id]: <uuid>\n- adjuntos[0][archivo]: <file>",
                'response' => "{\n  \"ok\": true,\n  \"message\": \"Incapacidad radicada correctamente.\",\n  \"data\": {\n    \"id_novedad\": \"uuid\",\n    \"estado\": \"RADICADO\",\n    \"notificacion_email_enviada\": true\n  }\n}",
            ],
            'POST /empleados/vacaciones/radicar' => [
                'request' => "POST /api/v2/empleados/vacaciones/radicar\nAuthorization: Bearer <token>\nContent-Type: multipart/form-data\n\n- documento_actor: 1007408720\n- documento_persona: 1007408720\n- fecha_inicio: 2026-06-01\n- fecha_fin: 2026-06-10\n- observacion: Solicitud anual\n- carta: <file>",
                'response' => "{\n  \"ok\": true,\n  \"message\": \"Vacaciones radicadas correctamente.\",\n  \"data\": {\n    \"id_novedad\": \"uuid\",\n    \"estado\": \"RADICADO\",\n    \"notificacion_email_enviada\": true\n  }\n}",
            ],
            'GET /empleados/novedades' => [
                'request' => "GET /api/v2/empleados/novedades?documento_actor=1007408720&documento_persona=1007408720&estado=RADICADO\nAuthorization: Bearer <token>",
                'response' => "{\n  \"ok\": true,\n  \"data\": [\n    {\n      \"id_novedad\": \"uuid\",\n      \"tipo\": \"Vacaciones\",\n      \"estado\": \"RADICADO\"\n    }\n  ],\n  \"meta\": {\n    \"current_page\": 1,\n    \"per_page\": 20,\n    \"total\": 1,\n    \"count\": 1\n  }\n}",
            ],
            'POST /empleados/solicitudes/{idNovedad}/anular' => [
                'request' => "POST /api/v2/empleados/solicitudes/{idNovedad}/anular\nAuthorization: Bearer <token>\nContent-Type: application/json\n\n{\n  \"documento_actor\": \"1007408720\",\n  \"motivo_anulacion\": \"Solicitud anulada por el empleado\"\n}",
                'response' => "{\n  \"ok\": true,\n  \"message\": \"Solicitud anulada correctamente.\",\n  \"data\": {\n    \"id_novedad\": \"uuid\",\n    \"estado\": \"ANULADO\"\n  }\n}",
            ],
            'GET /empleados/solicitudes/equipo-jefe' => [
                'request' => "GET /api/v2/empleados/solicitudes/equipo-jefe?documento_actor=91078541&estado_flujo=RADICADO\nAuthorization: Bearer <token>",
                'response' => "{\n  \"ok\": true,\n  \"data\": [\n    {\n      \"id_novedad\": \"uuid\",\n      \"estado\": \"RADICADO\",\n      \"documento_empleado\": \"1007408720\"\n    }\n  ],\n  \"meta\": {\n    \"current_page\": 1,\n    \"per_page\": 20,\n    \"total\": 1,\n    \"count\": 1\n  }\n}",
            ],
            'POST /empleados/solicitudes/{idNovedad}/gestionar-jefe' => [
                'request' => "POST /api/v2/empleados/solicitudes/{idNovedad}/gestionar-jefe\nAuthorization: Bearer <token>\nContent-Type: application/json\n\n{\n  \"documento_actor\": \"91078541\",\n  \"nombre_actor\": \"Jefe Ejemplo\",\n  \"accion\": \"APROBAR\"\n}",
                'response' => "{\n  \"ok\": true,\n  \"message\": \"Solicitud aprobada correctamente.\",\n  \"data\": {\n    \"id_novedad\": \"uuid\",\n    \"accion\": \"APROBAR\",\n    \"estado\": \"JEFE_APROBADO\"\n  }\n}",
            ],
            'POST /empleados/solicitudes/{idNovedad}/gestionar-rrhh' => [
                'request' => "POST /api/v2/empleados/solicitudes/{idNovedad}/gestionar-rrhh\nAuthorization: Bearer <token>\nContent-Type: application/json\n\n{\n  \"documento_actor\": \"12345678\",\n  \"nombre_actor\": \"RRHH Ejemplo\",\n  \"accion\": \"APROBAR\"\n}",
                'response' => "{\n  \"ok\": true,\n  \"message\": \"Solicitud gestionada por RRHH correctamente.\",\n  \"data\": {\n    \"id_novedad\": \"uuid\",\n    \"accion\": \"APROBAR\",\n    \"estado\": \"APROBADO\"\n  }\n}",
            ],
            default => [],
        };
    }

    /**
     * @return array{nombre:string,proposito:string,errores?:array<int,string>}
     */
    private function endpointMeta(string $method, string $path): array
    {
        $key = strtoupper($method).' /'.ltrim($path, '/');

        return self::MAIN_ENDPOINTS[$key] ?? [
            'nombre' => $key,
            'proposito' => 'Operacion de la API.',
        ];
    }
}
