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

class RecursosDigitalesController extends Controller
{
    private const TIPO_BANNER = 1;

    private const TIPO_DOCUMENTOS_MENSAJERIA = 5;

    private const TIPOS_GESTIONABLES = [
        self::TIPO_BANNER,
        self::TIPO_DOCUMENTOS_MENSAJERIA,
    ];

    private const ESTADO_ACTIVO = 'ACTIVO';

    private const ESTADO_INACTIVO = 'INACTIVO';

    private const STORAGE_BASE_DIRECTORY = 'gestionweb/appmovil';

    public function index(Request $request)
    {
        $selectedTypeId = $this->resolveManagedType($request->input('tipo'), self::TIPO_BANNER);
        $managedTypes = $this->managedTypes();
        $currentType = $managedTypes->firstWhere('IdTipoRecurso', $selectedTypeId);
        $typeConfig = $this->typeConfig($selectedTypeId, $currentType?->Descripcion);
        $placeholderPreview = $this->placeholderPreviewUrl($selectedTypeId);

        $recursos = $this->resourcesQuery($selectedTypeId)
            ->get()
            ->map(fn (RecursosDigitales $recurso) => $this->decorateResource($recurso));

        $resumen = [
            'total' => $recursos->count(),
            'activos' => $recursos->where('es_activo', true)->count(),
            'inactivos' => $recursos->where('es_activo', false)->count(),
        ];

        return view('gestionweb::appmovil.recursosdigitales.index', compact(
            'currentType',
            'managedTypes',
            'placeholderPreview',
            'recursos',
            'resumen',
            'selectedTypeId',
            'typeConfig'
        ));
    }

    public function create(Request $request)
    {
        $typeId = $this->resolveManagedType($request->input('tipo'), self::TIPO_BANNER);

        return view('gestionweb::appmovil.recursosdigitales.form', $this->buildFormData(null, $typeId));
    }

    public function store(Request $request)
    {
        $typeId = $this->resolveManagedType($request->input('tipo'), self::TIPO_BANNER);
        $typeConfig = $this->typeConfig($typeId);
        $validator = $this->resourceValidator($request, false, $typeId);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFileUrl = null;

        try {
            if ($request->hasFile('archivo')) {
                $uploadedFileUrl = $this->storeResourceFile($request->file('archivo'), $typeId);
            }

            $orden = $this->normalizeRequestedOrder($typeId, (int) $request->input('orden'), false);
            $url = $uploadedFileUrl ?: trim((string) $request->input('url_externa'));

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
                route('recursos-digitales.index', ['tipo' => $typeId])
            );
        } catch (Exception $e) {
            if ($uploadedFileUrl) {
                $this->deleteLocalResourceFile($uploadedFileUrl);
            }

            Log::error('Error al registrar recurso digital de app movil: '.$e->getMessage());

            return toastModal(
                'Error al registrar el '.$typeConfig['singular_label'],
                'error',
                route('recursos-digitales.index', ['tipo' => $typeId])
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
        $typeConfig = $this->typeConfig($typeId);
        $validator = $this->resourceValidator($request, true, $typeId);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFileUrl = null;
        $previousUrl = null;

        try {
            if ($request->hasFile('archivo')) {
                $uploadedFileUrl = $this->storeResourceFile($request->file('archivo'), $typeId);
            }

            $shouldUpdateUrl = $request->hasFile('archivo') || filled(trim((string) $request->input('url_externa')));
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
                route('recursos-digitales.index', ['tipo' => $typeId])
            );
        } catch (Exception $e) {
            if ($uploadedFileUrl) {
                $this->deleteLocalResourceFile($uploadedFileUrl);
            }

            Log::error('Error al actualizar recurso digital de app movil: '.$e->getMessage());

            return toastModal(
                'Error al actualizar el '.$typeConfig['singular_label'],
                'error',
                route('recursos-digitales.index', ['tipo' => $typeId])
            );
        }
    }

    public function cambiarEstado(int $id)
    {
        try {
            $recurso = $this->findManagedResourceOrFail($id);
            $typeConfig = $this->typeConfig((int) $recurso->Tipo);
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
        $selectedTypeId = $recurso ? (int) $recurso->Tipo : $this->resolveManagedType($typeId, self::TIPO_BANNER);
        $managedTypes = $this->managedTypes();
        $currentType = $managedTypes->firstWhere('IdTipoRecurso', $selectedTypeId);
        $typeConfig = $this->typeConfig($selectedTypeId, $currentType?->Descripcion);
        $placeholderPreview = $this->placeholderPreviewUrl($selectedTypeId);

        if ($recurso) {
            $recurso = $this->decorateResource($recurso);
        }

        return [
            'currentType' => $currentType,
            'managedTypes' => $managedTypes,
            'modo' => $recurso ? 'editar' : 'crear',
            'placeholderPreview' => $placeholderPreview,
            'previewUrl' => $recurso?->preview_url ?? $placeholderPreview,
            'recurso' => $recurso,
            'selectedTypeId' => $selectedTypeId,
            'siguienteOrden' => $recurso ? (int) $recurso->Orden : $this->nextOrder($selectedTypeId),
            'typeConfig' => $typeConfig,
            'formAction' => $recurso
                ? route('recursos-digitales.update', ['id' => $recurso->IdRecurso])
                : route('recursos-digitales.store'),
            'httpMethod' => $recurso ? 'PUT' : 'POST',
            'urlActual' => $recurso?->URL,
        ];
    }

    private function resourceValidator(Request $request, bool $isEdit, int $typeId)
    {
        $typeConfig = $this->typeConfig($typeId);
        $textRule = $typeConfig['requires_text'] ? 'required|string|max:200' : 'nullable|string|max:200';

        $validator = Validator::make($request->all(), [
            'tipo' => 'required|integer|in:'.implode(',', self::TIPOS_GESTIONABLES),
            'textoAuxiliar' => $textRule,
            'archivo' => $typeConfig['file_rule'],
            'url_externa' => 'nullable|url|max:2048',
            'orden' => 'required|integer|min:1',
            'estado' => 'required|in:'.self::ESTADO_ACTIVO.','.self::ESTADO_INACTIVO,
        ], [
            'tipo.required' => 'El tipo de recurso es obligatorio.',
            'tipo.in' => 'El tipo de recurso seleccionado no esta habilitado.',
            'textoAuxiliar.required' => 'El nombre del documento es obligatorio.',
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

            if ($hasFile && $hasUrl) {
                $validator->errors()->add('archivo', 'Use un archivo o una URL externa, no ambas al mismo tiempo.');
                $validator->errors()->add('url_externa', 'Use un archivo o una URL externa, no ambas al mismo tiempo.');
            }

            if (! $isEdit && ! $hasFile && ! $hasUrl) {
                $validator->errors()->add('archivo', 'Debe cargar un '.$typeConfig['singular_label'].' o indicar una URL externa.');
            }
        });

        return $validator;
    }

    private function managedTypes(): Collection
    {
        return TipoImagenes::query()
            ->whereIn('IdTipoRecurso', self::TIPOS_GESTIONABLES)
            ->orderBy('IdTipoRecurso')
            ->get();
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
        return RecursosDigitales::query()
            ->whereIn('Tipo', self::TIPOS_GESTIONABLES)
            ->findOrFail($id);
    }

    private function resolveManagedType(mixed $requestedType, int $default): int
    {
        $typeId = (int) $requestedType;

        return in_array($typeId, self::TIPOS_GESTIONABLES, true) ? $typeId : $default;
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

    private function decorateResource(RecursosDigitales $recurso): RecursosDigitales
    {
        $recurso->preview_url = $this->resolvePreviewUrl($recurso->URL, (int) $recurso->Tipo);
        $recurso->es_activo = $this->normalizeEstado($recurso->Estado) === self::ESTADO_ACTIVO;
        $recurso->preview_kind = $this->detectPreviewKind($recurso->URL, (int) $recurso->Tipo);
        $recurso->preview_icon = $this->previewIcon($recurso->preview_kind);
        $recurso->preview_label = $this->previewLabel($recurso->URL, $recurso->preview_kind);

        return $recurso;
    }

    private function detectPreviewKind(?string $url, int $typeId): string
    {
        if ($typeId === self::TIPO_BANNER) {
            return 'image';
        }

        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
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

        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        $extension = strtoupper(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'Archivo';
    }

    private function resolvePreviewUrl(?string $url, int $typeId = self::TIPO_BANNER): string
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
        if ($typeId === self::TIPO_BANNER) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675">'
                .'<rect width="1200" height="675" fill="#f1f3f5"/>'
                .'<rect x="60" y="60" width="1080" height="555" rx="36" fill="#dee2e6"/>'
                .'<circle cx="260" cy="260" r="76" fill="#adb5bd"/>'
                .'<path d="M170 470l190-185 120 115 150-145 210 215H170z" fill="#868e96"/>'
                .'<text x="600" y="580" font-family="Arial, sans-serif" font-size="42" text-anchor="middle" fill="#495057">Vista previa del banner</text>'
                .'</svg>';

            return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675">'
            .'<rect width="1200" height="675" fill="#f8f9fa"/>'
            .'<rect x="320" y="90" width="560" height="495" rx="34" fill="#ffffff" stroke="#ced4da" stroke-width="18"/>'
            .'<rect x="410" y="180" width="380" height="26" rx="13" fill="#0d6efd"/>'
            .'<rect x="410" y="246" width="310" height="18" rx="9" fill="#adb5bd"/>'
            .'<rect x="410" y="296" width="290" height="18" rx="9" fill="#adb5bd"/>'
            .'<rect x="410" y="346" width="330" height="18" rx="9" fill="#adb5bd"/>'
            .'<path d="M780 90v110h100" fill="#e9ecef"/>'
            .'<text x="600" y="470" font-family="Arial, sans-serif" font-size="54" font-weight="700" text-anchor="middle" fill="#495057">Documento</text>'
            .'<text x="600" y="530" font-family="Arial, sans-serif" font-size="30" text-anchor="middle" fill="#6c757d">Vista previa del recurso</text>'
            .'</svg>';

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
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

    private function typeConfig(int $typeId, ?string $typeDescription = null): array
    {
        return match ($typeId) {
            self::TIPO_DOCUMENTOS_MENSAJERIA => [
                'description' => $typeDescription ?: 'Documentos Mensajeria Express (App)',
                'singular_label' => 'documento',
                'plural_label' => 'documentos',
                'section_title' => 'Gestion de recursos digitales appmovil',
                'list_intro' => 'Desde aqui se administran los documentos que consume la app movil para mensajeria express.',
                'summary_label' => 'Total documentos',
                'create_label' => 'Registrar documento',
                'form_create_title' => 'Crear documento',
                'form_edit_title' => 'Editar documento',
                'form_create_description' => 'Registra un nuevo documento para la app movil.',
                'form_edit_description' => 'Actualiza el documento, su orden y su estado.',
                'text_label' => 'Nombre del documento',
                'text_required' => true,
                'text_help' => 'Usa un nombre claro para identificar el documento dentro de la app.',
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
                'storage_directory' => 'documentos/tipo-5',
                'storage_prefix' => 'documento',
                'requires_text' => true,
                'selector_badge' => 'Tipo 5',
            ],
            default => [
                'description' => $typeDescription ?: 'Banner Inicial (App)',
                'singular_label' => 'banner',
                'plural_label' => 'banners',
                'section_title' => 'Gestion de recursos digitales appmovil',
                'list_intro' => 'Desde aqui se administran los banners que consume la app movil.',
                'summary_label' => 'Total banners',
                'create_label' => 'Registrar banner',
                'form_create_title' => 'Crear banner',
                'form_edit_title' => 'Editar banner',
                'form_create_description' => 'Registra un nuevo banner para la app movil.',
                'form_edit_description' => 'Actualiza la imagen, el orden y el estado del banner.',
                'text_label' => 'Texto auxiliar',
                'text_required' => false,
                'text_help' => 'Campo opcional. Si no lo necesitas, puedes dejarlo vacio.',
                'file_label' => 'Cargar imagen',
                'file_help' => 'Formatos permitidos: JPG, PNG, WEBP o GIF. Maximo 5 MB.',
                'file_rule' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
                'file_mimes_message' => 'La imagen debe estar en formato JPG, PNG, WEBP o GIF.',
                'file_max_message' => 'La imagen no puede superar los 5 MB.',
                'url_label' => 'URL de imagen',
                'url_placeholder' => 'https://dominio.com/mi-banner.png',
                'url_help' => 'Usa este campo si la imagen ya existe en otra URL publica.',
                'preview_title' => 'Vista previa del banner',
                'preview_source_label' => 'Fuente de imagen',
                'open_current_label' => 'Abrir imagen actual',
                'preview_mode' => 'image',
                'storage_directory' => 'banners',
                'storage_prefix' => 'banner',
                'requires_text' => false,
                'selector_badge' => 'Tipo 1',
            ],
        };
    }
}
