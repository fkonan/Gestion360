<?php

namespace App\Modules\GestionWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionWeb\Models\RecursosDigitales;
use App\Modules\GestionWeb\Models\TipoImagenes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RecursosDigitalesAdminController extends Controller
{
    private const ESTADO_ACTIVO = 'ACTIVO';

    private const ESTADO_INACTIVO = 'INACTIVO';

    private const STORAGE_BASE_DIRECTORY = 'gestionweb/appmovil';

    private const MEDIA_TYPE_IDS = [1, 2];

    private const LINK_TYPE_IDS = [11, 12, 13, 14];

    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    public function index()
    {
        $managedTypes = $this->managedTypes();
        $groupedTypes = collect($this->familyCatalog())
            ->map(function (array $familyConfig, string $familyKey) use ($managedTypes) {
                $types = $managedTypes
                    ->where('resource_family', $familyKey)
                    ->values();

                return [
                    ...$familyConfig,
                    'key' => $familyKey,
                    'types' => $types,
                    'total_types' => $types->count(),
                    'types_with_resources' => $types->where('has_resources', true)->count(),
                    'total_resources' => (int) $types->sum('total_recursos'),
                    'active_resources' => (int) $types->sum('activos_count'),
                    'inactive_resources' => (int) $types->sum('inactivos_count'),
                ];
            })
            ->filter(fn (array $group) => $group['types']->isNotEmpty())
            ->values();

        $summary = [
            'total_types' => $managedTypes->count(),
            'types_with_resources' => $managedTypes->where('has_resources', true)->count(),
            'total_resources' => (int) $managedTypes->sum('total_recursos'),
            'active_resources' => (int) $managedTypes->sum('activos_count'),
        ];

        return view('gestionweb::appmovil.recursosdigitales.index', compact(
            'groupedTypes',
            'managedTypes',
            'summary'
        ));
    }

    public function show(int $tipo)
    {
        $currentType = $this->findManagedTypeOrFail($tipo);
        $typeConfig = $currentType->type_config;
        $placeholderPreview = $this->placeholderPreviewUrl((int) $currentType->IdTipoRecurso);

        $recursos = $this->resourcesQuery((int) $currentType->IdTipoRecurso)
            ->get()
            ->map(fn (RecursosDigitales $recurso) => $this->decorateResource($recurso, $typeConfig));

        $resumen = [
            'total' => $recursos->count(),
            'activos' => $recursos->where('es_activo', true)->count(),
            'inactivos' => $recursos->where('es_activo', false)->count(),
        ];

        return view('gestionweb::appmovil.recursosdigitales.show', compact(
            'currentType',
            'placeholderPreview',
            'recursos',
            'resumen',
            'typeConfig'
        ));
    }

    public function create(int $tipo)
    {
        $currentType = $this->findManagedTypeOrFail($tipo);

        return view('gestionweb::appmovil.recursosdigitales.form', $this->buildFormData(null, (int) $currentType->IdTipoRecurso));
    }

    public function store(Request $request, int $tipo)
    {
        $currentType = $this->findManagedTypeOrFail($tipo);
        $typeId = (int) $currentType->IdTipoRecurso;
        $typeConfig = $currentType->type_config;
        $validator = $this->resourceValidator($request, false, $typeId, $typeConfig);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFileUrl = null;

        try {
            if ($typeConfig['allows_file_upload'] && $request->hasFile('archivo')) {
                $uploadedFileUrl = $this->storeResourceFile($request->file('archivo'), $typeId);
            }

            $orden = $this->normalizeRequestedOrder($typeId, (int) $request->input('orden'), false);
            $url = $uploadedFileUrl ?: $this->nullableTrimmedValue($request->input('url_externa'));

            DB::connection($this->connectionName())->transaction(function () use ($request, $typeId, $orden, $url) {
                $this->shiftOrdersForCreate($typeId, $orden);

                RecursosDigitales::create([
                    'TextoAuxiliar' => $this->nullableTrimmedValue($request->input('textoAuxiliar')),
                    'URL' => $url,
                    'Tipo' => $typeId,
                    'Orden' => $orden,
                    'Estado' => $this->normalizeEstado($request->input('estado')),
                ]);
            });

            return toastModal(
                ucfirst($typeConfig['singular_label']).' registrado correctamente',
                'success',
                route('recursos-digitales.show', ['tipo' => $typeId])
            );
        } catch (Exception $e) {
            if ($uploadedFileUrl) {
                $this->deleteLocalResourceFile($uploadedFileUrl);
            }

            Log::error('Error al registrar recurso digital de app movil: '.$e->getMessage());

            return toastModal(
                'Error al registrar el '.$typeConfig['singular_label'],
                'error',
                route('recursos-digitales.show', ['tipo' => $typeId])
            );
        }
    }

    public function edit(int $id)
    {
        $recurso = $this->findManagedResourceOrFail($id);

        return view('gestionweb::appmovil.recursosdigitales.form', $this->buildFormData($recurso, (int) $recurso->Tipo));
    }

    public function update(Request $request, int $id)
    {
        $recurso = $this->findManagedResourceOrFail($id);
        $typeId = (int) $recurso->Tipo;
        $currentType = $this->findManagedTypeOrFail($typeId);
        $typeConfig = $currentType->type_config;
        $validator = $this->resourceValidator($request, true, $typeId, $typeConfig);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFileUrl = null;
        $previousUrl = null;

        try {
            if ($typeConfig['allows_file_upload'] && $request->hasFile('archivo')) {
                $uploadedFileUrl = $this->storeResourceFile($request->file('archivo'), $typeId);
            }

            $shouldUpdateUrl = ($typeConfig['allows_file_upload'] && $request->hasFile('archivo'))
                || filled(trim((string) $request->input('url_externa')));
            $newUrl = $uploadedFileUrl ?: $this->nullableTrimmedValue($request->input('url_externa'));
            $nuevoOrden = $this->normalizeRequestedOrder($typeId, (int) $request->input('orden'), true);

            DB::connection($this->connectionName())->transaction(function () use ($request, $recurso, $typeId, $shouldUpdateUrl, $newUrl, $nuevoOrden, &$previousUrl) {
                $this->shiftOrdersForUpdate($typeId, $recurso, $nuevoOrden);

                $payload = [
                    'TextoAuxiliar' => $this->nullableTrimmedValue($request->input('textoAuxiliar')),
                    'Orden' => $nuevoOrden,
                    'Estado' => $this->normalizeEstado($request->input('estado')),
                ];

                if ($shouldUpdateUrl && $newUrl) {
                    $previousUrl = $recurso->URL;
                    $payload['URL'] = $newUrl;
                }

                $recurso->update($payload);
            });

            if ($previousUrl && $previousUrl !== $newUrl) {
                $this->deleteLocalResourceFile($previousUrl);
            }

            return toastModal(
                ucfirst($typeConfig['singular_label']).' actualizado correctamente',
                'success',
                route('recursos-digitales.show', ['tipo' => $typeId])
            );
        } catch (Exception $e) {
            if ($uploadedFileUrl) {
                $this->deleteLocalResourceFile($uploadedFileUrl);
            }

            Log::error('Error al actualizar recurso digital de app movil: '.$e->getMessage());

            return toastModal(
                'Error al actualizar el '.$typeConfig['singular_label'],
                'error',
                route('recursos-digitales.show', ['tipo' => $typeId])
            );
        }
    }

    public function cambiarEstado(int $id)
    {
        try {
            $recurso = $this->findManagedResourceOrFail($id);
            $typeConfig = $this->findManagedTypeOrFail((int) $recurso->Tipo)->type_config;
            $estadoActual = $this->normalizeEstado($recurso->Estado);
            $recurso->Estado = $estadoActual === self::ESTADO_ACTIVO
                ? self::ESTADO_INACTIVO
                : self::ESTADO_ACTIVO;
            $recurso->save();

            return response()->json([
                'message' => ucfirst($typeConfig['singular_label']).' actualizado a '.$recurso->Estado,
                'type' => 'success',
                'estado' => $recurso->Estado,
            ]);
        } catch (Exception $e) {
            Log::error('Error al cambiar estado de recurso digital de app movil: '.$e->getMessage());

            return response()->json([
                'message' => 'No fue posible cambiar el estado del recurso',
                'type' => 'danger',
            ], 500);
        }
    }

    private function buildFormData(?RecursosDigitales $recurso = null, ?int $typeId = null): array
    {
        $selectedTypeId = $recurso ? (int) $recurso->Tipo : (int) $typeId;
        $currentType = $this->findManagedTypeOrFail($selectedTypeId);
        $typeConfig = $currentType->type_config;
        $placeholderPreview = $this->placeholderPreviewUrl($selectedTypeId);

        if ($recurso) {
            $recurso = $this->decorateResource($recurso, $typeConfig);
        }

        return [
            'currentType' => $currentType,
            'modo' => $recurso ? 'editar' : 'crear',
            'placeholderPreview' => $placeholderPreview,
            'previewUrl' => $recurso?->preview_url ?? $placeholderPreview,
            'recurso' => $recurso,
            'selectedTypeId' => $selectedTypeId,
            'siguienteOrden' => $recurso ? (int) $recurso->Orden : $this->nextOrder($selectedTypeId),
            'typeConfig' => $typeConfig,
            'formAction' => $recurso
                ? route('recursos-digitales.update', ['id' => $recurso->IdRecurso])
                : route('recursos-digitales.store', ['tipo' => $selectedTypeId]),
            'httpMethod' => $recurso ? 'PUT' : 'POST',
            'urlActual' => $recurso?->URL,
        ];
    }

    private function resourceValidator(Request $request, bool $isEdit, int $typeId, array $typeConfig)
    {
        $textRule = $typeConfig['text_required'] ? 'required|string|max:200' : 'nullable|string|max:200';

        $validator = Validator::make($request->all(), [
            'tipo' => 'required|integer|in:'.$typeId,
            'textoAuxiliar' => $textRule,
            'archivo' => $typeConfig['file_rule'],
            'url_externa' => 'nullable|url|max:2048',
            'orden' => 'required|integer|min:1',
            'estado' => 'required|in:'.self::ESTADO_ACTIVO.','.self::ESTADO_INACTIVO,
        ], [
            'tipo.required' => 'El tipo de recurso es obligatorio.',
            'tipo.in' => 'El tipo de recurso seleccionado no coincide con la vista actual.',
            'textoAuxiliar.required' => 'Este campo es obligatorio para este tipo de recurso.',
            'textoAuxiliar.max' => 'El texto no puede superar los 200 caracteres.',
            'archivo.image' => 'El archivo debe ser una imagen valida.',
            'archivo.mimes' => $typeConfig['file_mimes_message'],
            'archivo.max' => $typeConfig['file_max_message'],
            'url_externa.url' => 'La URL ingresada no tiene un formato valido.',
            'url_externa.max' => 'La URL no puede superar los 2048 caracteres.',
            'orden.required' => 'El orden es obligatorio.',
            'orden.integer' => 'El orden debe ser un numero entero.',
            'orden.min' => 'El orden minimo permitido es 1.',
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es valido.',
        ]);

        $validator->after(function ($validator) use ($request, $isEdit, $typeConfig) {
            $hasFile = $request->hasFile('archivo');
            $hasUrl = filled(trim((string) $request->input('url_externa')));

            if (! $typeConfig['allows_file_upload'] && $hasFile) {
                $validator->errors()->add('archivo', 'Este tipo de recurso solo permite configurar una URL.');
            }

            if ($typeConfig['allows_file_upload'] && $hasFile && $hasUrl) {
                $validator->errors()->add('archivo', 'Use un archivo o una URL externa, no ambas al mismo tiempo.');
                $validator->errors()->add('url_externa', 'Use un archivo o una URL externa, no ambas al mismo tiempo.');
            }

            if (! $isEdit && ! $hasFile && ! $hasUrl) {
                $message = $typeConfig['allows_file_upload']
                    ? 'Debe cargar un '.$typeConfig['singular_label'].' o indicar una URL externa.'
                    : 'Debe indicar una URL para el '.$typeConfig['singular_label'].'.';

                $validator->errors()->add($typeConfig['allows_file_upload'] ? 'archivo' : 'url_externa', $message);
            }
        });

        return $validator;
    }

    private function managedTypes(): Collection
    {
        return TipoImagenes::query()
            ->withCount([
                'recursosDigitales as total_recursos',
                'recursosDigitales as activos_count' => fn ($query) => $query->whereRaw('UPPER(Estado) = ?', [self::ESTADO_ACTIVO]),
                'recursosDigitales as inactivos_count' => fn ($query) => $query->whereRaw('UPPER(Estado) = ?', [self::ESTADO_INACTIVO]),
            ])
            ->orderBy('IdTipoRecurso')
            ->get()
            ->map(fn (TipoImagenes $type) => $this->decorateType($type));
    }

    private function findManagedTypeOrFail(int $typeId): TipoImagenes
    {
        $type = TipoImagenes::query()
            ->withCount([
                'recursosDigitales as total_recursos',
                'recursosDigitales as activos_count' => fn ($query) => $query->whereRaw('UPPER(Estado) = ?', [self::ESTADO_ACTIVO]),
                'recursosDigitales as inactivos_count' => fn ($query) => $query->whereRaw('UPPER(Estado) = ?', [self::ESTADO_INACTIVO]),
            ])
            ->find($typeId);

        abort_if(! $type, 404);

        return $this->decorateType($type);
    }

    private function resourcesQuery(int $typeId)
    {
        return RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->orderBy('Orden')
            ->orderBy('IdRecurso');
    }

    private function findManagedResourceOrFail(int $id): RecursosDigitales
    {
        $recurso = RecursosDigitales::query()->findOrFail($id);
        $this->findManagedTypeOrFail((int) $recurso->Tipo);

        return $recurso;
    }

    private function normalizeEstado(?string $estado): string
    {
        return strtoupper(trim((string) $estado)) === self::ESTADO_INACTIVO
            ? self::ESTADO_INACTIVO
            : self::ESTADO_ACTIVO;
    }

    private function normalizeRequestedOrder(int $typeId, int $orden, bool $isEdit): int
    {
        $maxOrder = (int) RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->max('Orden');

        $upperLimit = $isEdit ? max(1, $maxOrder) : max(1, $maxOrder + 1);

        return max(1, min($orden, $upperLimit));
    }

    private function shiftOrdersForCreate(int $typeId, int $orden): void
    {
        RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->where('Orden', '>=', $orden)
            ->increment('Orden');
    }

    private function shiftOrdersForUpdate(int $typeId, RecursosDigitales $recurso, int $newOrder): void
    {
        $currentOrder = (int) $recurso->Orden;

        if ($newOrder === $currentOrder) {
            return;
        }

        $query = RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->where('IdRecurso', '<>', $recurso->IdRecurso);

        if ($newOrder < $currentOrder) {
            $query->whereBetween('Orden', [$newOrder, $currentOrder - 1])->increment('Orden');

            return;
        }

        $query->whereBetween('Orden', [$currentOrder + 1, $newOrder])->decrement('Orden');
    }

    private function nextOrder(int $typeId): int
    {
        return ((int) RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->max('Orden')) + 1;
    }

    private function decorateType(TipoImagenes $type): TipoImagenes
    {
        $typeId = (int) $type->IdTipoRecurso;
        $type->clean_description = $this->cleanTypeDescription($type->Descripcion);
        $type->type_config = $this->typeConfig($typeId, $type->Descripcion);
        $type->resource_family = $type->type_config['resource_family'];
        $type->family_config = $this->familyCatalog()[$type->resource_family];
        $type->has_resources = (int) ($type->total_recursos ?? 0) > 0;

        return $type;
    }

    private function decorateResource(RecursosDigitales $recurso, ?array $typeConfig = null): RecursosDigitales
    {
        $typeConfig ??= $this->typeConfig((int) $recurso->Tipo);
        $previewKind = $this->detectPreviewKind($recurso->URL, $typeConfig);

        $recurso->preview_url = $this->resolvePreviewUrl($recurso->URL, (int) $recurso->Tipo);
        $recurso->es_activo = $this->normalizeEstado($recurso->Estado) === self::ESTADO_ACTIVO;
        $recurso->preview_kind = $previewKind;
        $recurso->preview_icon = $this->previewIcon($previewKind);
        $recurso->preview_icon_type = $previewKind === 'pdf' ? 'image' : 'font';
        $recurso->preview_icon_asset = $previewKind === 'pdf' ? asset('img/verPDF.png') : null;
        $recurso->preview_label = $this->previewLabel($recurso->URL, $previewKind);
        $recurso->display_name = $this->defaultResourceName($recurso, $typeConfig);
        $recurso->source_label = $this->resourceSourceLabel($recurso->URL, $previewKind);
        $recurso->host_label = $this->resourceHostLabel($recurso->URL);
        $recurso->short_url = Str::limit(trim((string) $recurso->URL), 70);

        return $recurso;
    }

    private function resolveTypeFamily(int $typeId, ?string $description = null): string
    {
        if (in_array($typeId, self::LINK_TYPE_IDS, true) || str_contains(strtolower((string) $description), 'link')) {
            return 'link';
        }

        if (in_array($typeId, self::MEDIA_TYPE_IDS, true) || preg_match('/banner|imagen/i', (string) $description)) {
            return 'media';
        }

        return 'document';
    }

    private function familyCatalog(): array
    {
        return [
            'media' => [
                'title' => 'Imagenes y banners',
                'description' => 'Piezas visuales que la app movil presenta como contenido grafico.',
                'icon' => 'fa-images',
                'accent' => 'primary',
            ],
            'document' => [
                'title' => 'Documentos y politicas',
                'description' => 'Archivos de consulta, formatos y documentos descargables por modulo.',
                'icon' => 'fa-folder-open',
                'accent' => 'info',
            ],
            'link' => [
                'title' => 'Enlaces y accesos',
                'description' => 'URLs que redirigen a modulos, integraciones o flujos externos.',
                'icon' => 'fa-link',
                'accent' => 'warning',
            ],
        ];
    }

    private function detectPreviewKind(?string $url, array $typeConfig): string
    {
        if ($typeConfig['resource_family'] === 'link') {
            return 'link';
        }

        if ($typeConfig['resource_family'] === 'media') {
            return 'image';
        }

        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return 'image';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        return 'file';
    }

    private function previewIcon(string $previewKind): string
    {
        return match ($previewKind) {
            'pdf' => 'fa-file-pdf',
            'image' => 'fa-image',
            'link' => 'fa-link',
            default => 'fa-file-alt',
        };
    }

    private function previewLabel(?string $url, string $previewKind): string
    {
        if ($previewKind === 'pdf') {
            return 'PDF';
        }

        if ($previewKind === 'image') {
            return 'Imagen';
        }

        if ($previewKind === 'link') {
            return $this->resourceHostLabel($url) ?: 'Enlace';
        }

        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        $extension = strtoupper(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'Archivo';
    }

    private function resolvePreviewUrl(?string $url, int $typeId): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return $this->placeholderPreviewUrl($typeId);
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return url('/'.ltrim($url, '/'));
    }

    private function placeholderPreviewUrl(int $typeId): string
    {
        $typeConfig = $this->typeConfig($typeId);

        return $this->placeholderPreviewByFamily($typeConfig['resource_family']);
    }

    private function placeholderPreviewByFamily(string $family): string
    {
        return match ($family) {
            'media' => 'data:image/svg+xml;charset=UTF-8,'.rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675">'
                .'<rect width="1200" height="675" fill="#f1f3f5"/>'
                .'<rect x="60" y="60" width="1080" height="555" rx="36" fill="#dee2e6"/>'
                .'<circle cx="260" cy="260" r="76" fill="#adb5bd"/>'
                .'<path d="M170 470l190-185 120 115 150-145 210 215H170z" fill="#868e96"/>'
                .'<text x="600" y="580" font-family="Arial, sans-serif" font-size="42" text-anchor="middle" fill="#495057">Vista previa visual</text>'
                .'</svg>'
            ),
            'link' => 'data:image/svg+xml;charset=UTF-8,'.rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675">'
                .'<rect width="1200" height="675" fill="#fff8e1"/>'
                .'<rect x="210" y="140" width="780" height="395" rx="42" fill="#ffffff" stroke="#f0ad4e" stroke-width="18"/>'
                .'<path d="M470 392c-42 42-110 42-152 0s-42-110 0-152l76-76c42-42 110-42 152 0" fill="none" stroke="#f0ad4e" stroke-width="28" stroke-linecap="round"/>'
                .'<path d="M730 284c42-42 110-42 152 0s42 110 0 152l-76 76c-42 42-110 42-152 0" fill="none" stroke="#f0ad4e" stroke-width="28" stroke-linecap="round"/>'
                .'<path d="M476 400l248-248" fill="none" stroke="#ffd27f" stroke-width="26" stroke-linecap="round"/>'
                .'<text x="600" y="500" font-family="Arial, sans-serif" font-size="44" text-anchor="middle" fill="#8a6d3b">Vista previa del enlace</text>'
                .'</svg>'
            ),
            default => 'data:image/svg+xml;charset=UTF-8,'.rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675">'
                .'<rect width="1200" height="675" fill="#f8f9fa"/>'
                .'<rect x="320" y="90" width="560" height="495" rx="34" fill="#ffffff" stroke="#ced4da" stroke-width="18"/>'
                .'<rect x="410" y="180" width="380" height="26" rx="13" fill="#0d6efd"/>'
                .'<rect x="410" y="246" width="310" height="18" rx="9" fill="#adb5bd"/>'
                .'<rect x="410" y="296" width="290" height="18" rx="9" fill="#adb5bd"/>'
                .'<rect x="410" y="346" width="330" height="18" rx="9" fill="#adb5bd"/>'
                .'<path d="M780 90v110h100" fill="#e9ecef"/>'
                .'<text x="600" y="470" font-family="Arial, sans-serif" font-size="54" font-weight="700" text-anchor="middle" fill="#495057">Documento</text>'
                .'<text x="600" y="530" font-family="Arial, sans-serif" font-size="30" text-anchor="middle" fill="#6c757d">Vista previa del recurso</text>'
                .'</svg>'
            ),
        };
    }

    private function storeResourceFile(UploadedFile $file, int $typeId): string
    {
        $typeConfig = $this->typeConfig($typeId);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = $typeConfig['storage_prefix'].'_'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;
        $path = $file->storeAs(
            trim(self::STORAGE_BASE_DIRECTORY.'/'.$typeConfig['storage_directory'], '/'),
            $filename,
            'public'
        );

        return $this->resolvePreviewUrl(Storage::disk('public')->url($path), $typeId);
    }

    private function deleteLocalResourceFile(?string $url): void
    {
        $path = parse_url((string) $url, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        $normalizedPath = trim(str_replace('\\', '/', $path), '/');

        if (! str_starts_with($normalizedPath, 'storage/'.self::STORAGE_BASE_DIRECTORY.'/')) {
            return;
        }

        $relativePath = Str::after($normalizedPath, 'storage/');

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function nullableTrimmedValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function connectionName(): string
    {
        return (new RecursosDigitales)->getConnectionName();
    }

    private function cleanTypeDescription(?string $description): string
    {
        $description = trim((string) $description);

        return trim((string) preg_replace('/\s*\(App\)\s*/i', '', $description));
    }

    private function defaultResourceName(RecursosDigitales $recurso, array $typeConfig): string
    {
        $name = trim((string) $recurso->TextoAuxiliar);

        if ($name !== '') {
            return $name;
        }

        return ucfirst($typeConfig['singular_label']).' '.((int) $recurso->Orden);
    }

    private function resourceSourceLabel(?string $url, string $previewKind): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return 'Sin recurso configurado';
        }

        if ($previewKind === 'link') {
            return $this->resourceHostLabel($url) ?: 'Enlace configurado';
        }

        if (filter_var($url, FILTER_VALIDATE_URL) || str_starts_with($url, '//')) {
            return 'URL publica';
        }

        return str_contains($url, 'storage/')
            ? 'Archivo cargado'
            : 'Ruta interna';
    }

    private function resourceHostLabel(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        if ($host === '') {
            return '';
        }

        return preg_replace('/^www\./i', '', $host) ?: '';
    }

    private function typeConfig(int $typeId, ?string $typeDescription = null): array
    {
        $description = $this->cleanTypeDescription($typeDescription ?: 'Recurso digital');
        $family = $this->resolveTypeFamily($typeId, $description);
        $familyConfig = $this->familyCatalog()[$family];

        if ($family === 'media') {
            $isBanner = $typeId === 1 || str_contains(strtolower($description), 'banner');

            return [
                'description' => $description,
                'resource_family' => $family,
                'family_title' => $familyConfig['title'],
                'family_description' => $familyConfig['description'],
                'singular_label' => $isBanner ? 'banner' : 'imagen',
                'plural_label' => $isBanner ? 'banners' : 'imagenes',
                'section_title' => 'Gestion de recursos digitales appmovil',
                'list_intro' => 'Administra las piezas visuales asociadas a este tipo para mantener el contenido grafico de la app movil organizado.',
                'summary_label' => $isBanner ? 'Total banners' : 'Total imagenes',
                'create_label' => $isBanner ? 'Registrar banner' : 'Registrar imagen',
                'form_create_title' => $isBanner ? 'Crear banner' : 'Crear imagen',
                'form_edit_title' => $isBanner ? 'Editar banner' : 'Editar imagen',
                'form_create_description' => 'Registra un nuevo recurso visual para este tipo.',
                'form_edit_description' => 'Actualiza la imagen, el orden y el estado del recurso.',
                'text_label' => 'Texto auxiliar',
                'text_required' => false,
                'text_help' => 'Campo opcional para identificar internamente el recurso.',
                'allows_file_upload' => true,
                'file_label' => 'Cargar imagen',
                'file_help' => 'Formatos permitidos: JPG, PNG, WEBP o GIF. Maximo 5 MB.',
                'file_rule' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
                'file_mimes_message' => 'La imagen debe estar en formato JPG, PNG, WEBP o GIF.',
                'file_max_message' => 'La imagen no puede superar los 5 MB.',
                'url_label' => 'URL de imagen',
                'url_placeholder' => 'https://dominio.com/mi-recurso.png',
                'url_help' => 'Usa este campo si la imagen ya existe en una URL publica.',
                'preview_title' => $isBanner ? 'Vista previa del banner' : 'Vista previa de la imagen',
                'preview_source_label' => 'Fuente del recurso',
                'open_current_label' => $isBanner ? 'Abrir imagen actual' : 'Abrir recurso actual',
                'preview_mode' => 'image',
                'detail_layout' => 'gallery',
                'storage_directory' => $isBanner ? 'banners' : 'imagenes/tipo-'.$typeId,
                'storage_prefix' => $isBanner ? 'banner' : 'imagen_tipo_'.$typeId,
                'selector_badge' => 'Tipo '.$typeId,
            ];
        }

        if ($family === 'link') {
            return [
                'description' => $description,
                'resource_family' => $family,
                'family_title' => $familyConfig['title'],
                'family_description' => $familyConfig['description'],
                'singular_label' => 'enlace',
                'plural_label' => 'enlaces',
                'section_title' => 'Gestion de recursos digitales appmovil',
                'list_intro' => 'Administra los accesos y redirecciones que la app movil usa para enlazar otros modulos o servicios.',
                'summary_label' => 'Total enlaces',
                'create_label' => 'Registrar enlace',
                'form_create_title' => 'Crear enlace',
                'form_edit_title' => 'Editar enlace',
                'form_create_description' => 'Configura un nuevo enlace para este tipo de acceso.',
                'form_edit_description' => 'Actualiza la URL, el orden y el estado del enlace.',
                'text_label' => 'Etiqueta interna',
                'text_required' => false,
                'text_help' => 'Opcional. Sirve para identificar mejor el enlace dentro de la administracion.',
                'allows_file_upload' => false,
                'file_label' => null,
                'file_help' => null,
                'file_rule' => 'nullable',
                'file_mimes_message' => 'Este tipo de recurso no permite archivos adjuntos.',
                'file_max_message' => 'Este tipo de recurso no permite archivos adjuntos.',
                'url_label' => 'URL destino',
                'url_placeholder' => 'https://dominio.com/modulo',
                'url_help' => 'Ingresa la URL completa a la que debe apuntar la app movil.',
                'preview_title' => 'Vista previa del enlace',
                'preview_source_label' => 'Destino configurado',
                'open_current_label' => 'Abrir enlace actual',
                'preview_mode' => 'link',
                'detail_layout' => 'table',
                'storage_directory' => 'links/tipo-'.$typeId,
                'storage_prefix' => 'link_tipo_'.$typeId,
                'selector_badge' => 'Tipo '.$typeId,
            ];
        }

        return [
            'description' => $description,
            'resource_family' => 'document',
            'family_title' => $familyConfig['title'],
            'family_description' => $familyConfig['description'],
            'singular_label' => 'documento',
            'plural_label' => 'documentos',
            'section_title' => 'Gestion de recursos digitales appmovil',
            'list_intro' => 'Administra los documentos y archivos informativos asociados a este tipo.',
            'summary_label' => 'Total documentos',
            'create_label' => 'Registrar documento',
            'form_create_title' => 'Crear documento',
            'form_edit_title' => 'Editar documento',
            'form_create_description' => 'Registra un nuevo documento para este tipo de recurso.',
            'form_edit_description' => 'Actualiza el archivo, el orden y el estado del documento.',
            'text_label' => 'Nombre del documento',
            'text_required' => true,
            'text_help' => 'Usa un nombre claro para identificar el documento dentro de la app.',
            'allows_file_upload' => true,
            'file_label' => 'Cargar archivo',
            'file_help' => 'Formatos permitidos: PDF, JPG, PNG o WEBP. Maximo 10 MB.',
            'file_rule' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'file_mimes_message' => 'El archivo debe estar en formato PDF, JPG, PNG o WEBP.',
            'file_max_message' => 'El archivo no puede superar los 10 MB.',
            'url_label' => 'URL del archivo',
            'url_placeholder' => 'https://dominio.com/mi-documento.pdf',
            'url_help' => 'Usa este campo si el documento ya existe en una URL publica.',
            'preview_title' => 'Vista previa del documento',
            'preview_source_label' => 'Fuente del documento',
            'open_current_label' => 'Abrir documento actual',
            'preview_mode' => 'document',
            'detail_layout' => 'table',
            'storage_directory' => 'documentos/tipo-'.$typeId,
            'storage_prefix' => 'documento_tipo_'.$typeId,
            'selector_badge' => 'Tipo '.$typeId,
        ];
    }
}
