<?php

namespace App\Modules\GestionWeb\Http\Controllers;

use App\Services\DocumentalStorageService;
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

    private const CDN_BANNER_DEFAULT_RELATIVE_PATH = 'cdn/app/banner/img';

    private const CDN_DOCS_DEFAULT_RELATIVE_PATH = 'cdn/app/docs';

    private const MEDIA_TYPE_IDS = [1, 2];

    private const LINK_TYPE_IDS = [11, 12, 13, 14, 16, 17];

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
        $textoAuxiliar = $this->nullableTrimmedValue($request->input('textoAuxiliar'));
        $validator = $this->resourceValidator($request, false, $typeId, $typeConfig);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFileUrl = null;

        try {
            if ($typeConfig['allows_file_upload'] && $request->hasFile('archivo')) {
                $uploadedFileUrl = $this->storeResourceFile($request->file('archivo'), $typeId, $textoAuxiliar);
            }

            $orden = $this->normalizeRequestedOrder($typeId, (int) $request->input('orden'), false);
            $url = $uploadedFileUrl ?: $this->nullableTrimmedValue($request->input('url_externa'));

            DB::connection($this->connectionName())->transaction(function () use ($request, $typeId, $orden, $url, $textoAuxiliar) {
                $this->shiftOrdersForCreate($typeId, $orden);

                RecursosDigitales::create([
                    'TextoAuxiliar' => $textoAuxiliar,
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
        $textoAuxiliar = $this->nullableTrimmedValue($request->input('textoAuxiliar'));
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
                $uploadedFileUrl = $this->storeResourceFile($request->file('archivo'), $typeId, $textoAuxiliar);
            }

            $shouldUpdateUrl = ($typeConfig['allows_file_upload'] && $request->hasFile('archivo'))
                || filled(trim((string) $request->input('url_externa')));
            $newUrl = $uploadedFileUrl ?: $this->nullableTrimmedValue($request->input('url_externa'));
            $nuevoOrden = $this->normalizeRequestedOrder($typeId, (int) $request->input('orden'), true);

            DB::connection($this->connectionName())->transaction(function () use ($request, $recurso, $typeId, $shouldUpdateUrl, $newUrl, $nuevoOrden, &$previousUrl, $textoAuxiliar) {
                $this->shiftOrdersForUpdate($typeId, $recurso, $nuevoOrden);

                $payload = [
                    'TextoAuxiliar' => $textoAuxiliar,
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

    public function reordenar(Request $request, int $tipo)
    {
        $currentType = $this->findManagedTypeOrFail($tipo);
        $typeId = (int) $currentType->IdTipoRecurso;
        $typeConfig = $currentType->type_config;

        if (($typeConfig['detail_layout'] ?? '') !== 'gallery') {
            return response()->json([
                'message' => 'Este tipo de recurso no permite reordenamiento visual.',
                'type' => 'danger',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'orden' => 'required|array|min:1',
            'orden.*' => 'required|integer|distinct',
        ], [
            'orden.required' => 'Debe indicar el nuevo orden de los recursos.',
            'orden.array' => 'El formato del orden enviado no es valido.',
            'orden.min' => 'Debe enviar al menos un recurso para reordenar.',
            'orden.*.integer' => 'El identificador del recurso debe ser numerico.',
            'orden.*.distinct' => 'El nuevo orden contiene recursos duplicados.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'No fue posible validar el nuevo orden.',
                'type' => 'danger',
                'errors' => $validator->errors(),
            ], 422);
        }

        $orderedIds = array_values(array_map('intval', (array) $request->input('orden', [])));
        $currentIds = RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->orderBy('Orden')
            ->orderBy('IdRecurso')
            ->pluck('IdRecurso')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $expectedIds = $currentIds;
        $receivedIds = $orderedIds;
        sort($expectedIds);
        sort($receivedIds);

        if (count($orderedIds) !== count($currentIds) || $expectedIds !== $receivedIds) {
            return response()->json([
                'message' => 'El nuevo orden no coincide con los recursos disponibles en pantalla.',
                'type' => 'danger',
            ], 422);
        }

        if ($orderedIds === $currentIds) {
            return response()->json([
                'message' => 'El orden de '.$typeConfig['plural_label'].' ya se encuentra actualizado.',
                'type' => 'success',
            ]);
        }

        try {
            DB::connection($this->connectionName())->transaction(function () use ($typeId, $orderedIds) {
                foreach ($orderedIds as $index => $resourceId) {
                    RecursosDigitales::query()
                        ->where('Tipo', $typeId)
                        ->where('IdRecurso', $resourceId)
                        ->update(['Orden' => $index + 1]);
                }
            });

            return response()->json([
                'message' => 'Orden de '.$typeConfig['plural_label'].' actualizado correctamente.',
                'type' => 'success',
            ]);
        } catch (Exception $e) {
            Log::error('Error al reordenar recursos digitales de app movil: '.$e->getMessage());

            return response()->json([
                'message' => 'No fue posible guardar el nuevo orden.',
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
        $isEdit = $recurso !== null;

        if ($recurso) {
            $recurso = $this->decorateResource($recurso, $typeConfig);
        }

        $defaultOrder = $recurso ? (int) $recurso->Orden : $this->nextOrder($selectedTypeId);
        $rutaDetalleTipo = route('recursos-digitales.show', ['tipo' => $selectedTypeId]);

        return [
            'currentType' => $currentType,
            'modo' => $isEdit ? 'editar' : 'crear',
            'placeholderPreview' => $placeholderPreview,
            'previewUrl' => $recurso?->preview_url ?? $placeholderPreview,
            'recurso' => $recurso,
            'selectedTypeId' => $selectedTypeId,
            'siguienteOrden' => $defaultOrder,
            'typeConfig' => $typeConfig,
            'formAction' => $recurso
                ? route('recursos-digitales.update', ['id' => $recurso->IdRecurso])
                : route('recursos-digitales.store', ['tipo' => $selectedTypeId]),
            'httpMethod' => $isEdit ? 'PUT' : 'POST',
            'urlActual' => $recurso?->URL,
            'esEdicion' => $isEdit,
            'rutaIndice' => route('recursos-digitales.index'),
            'rutaDetalleTipo' => $rutaDetalleTipo,
            'tituloAccion' => $isEdit ? $typeConfig['form_edit_title'] : $typeConfig['form_create_title'],
            'descripcionAccion' => $isEdit ? $typeConfig['form_edit_description'] : $typeConfig['form_create_description'],
            'textoBoton' => $isEdit ? 'Guardar cambios' : $typeConfig['create_label'],
            'iconoBoton' => $isEdit ? 'fa-save' : 'fa-plus-circle',
            ...$this->buildFormPresentationData($recurso, $typeConfig, $selectedTypeId, $defaultOrder, $isEdit),
        ];
    }

    private function buildFormPresentationData(
        ?RecursosDigitales $recurso,
        array $typeConfig,
        int $selectedTypeId,
        int $defaultOrder,
        bool $isEdit
    ): array {
        $previewMode = (string) $typeConfig['preview_mode'];
        $defaultText = trim((string) ($recurso?->TextoAuxiliar ?? ''));
        $previewKind = $recurso?->preview_kind ?? $this->defaultPreviewKindByMode($previewMode);
        $previewLabel = $recurso?->preview_label ?? $this->defaultPreviewLabelByMode($previewMode);

        return [
            'textoAuxiliar' => $defaultText,
            'ordenDefault' => $defaultOrder,
            'estadoDefault' => $this->normalizeEstado($recurso?->Estado),
            'urlActualTexto' => $recurso?->URL ?? 'La URL se definira cuando cargues el archivo o escribas una URL externa.',
            'previewKind' => $previewKind,
            'previewIcon' => $recurso?->preview_icon ?? $this->defaultPreviewIconByMode($previewMode),
            'previewIconType' => $recurso?->preview_icon_type ?? ($previewKind === 'pdf' ? 'image' : 'font'),
            'previewIconAsset' => $recurso?->preview_icon_asset ?? ($previewKind === 'pdf' ? asset('img/verPDF.png') : null),
            'previewLabel' => $previewLabel,
            'initialSourceText' => $this->buildInitialSourceText($isEdit, $previewKind, $previewMode),
            'acceptTypes' => $this->resolveAcceptTypes($previewMode),
            'previewDisplayName' => $defaultText !== '' ? $defaultText : ucfirst($typeConfig['singular_label']).' sin nombre',
            'defaultDocumentName' => ucfirst($typeConfig['singular_label']).' sin nombre',
            'alertaFormulario' => $this->buildFormAlertMessage($isEdit, $typeConfig),
            'notas' => $this->buildFormNotes($typeConfig, $selectedTypeId),
        ];
    }

    private function defaultPreviewKindByMode(string $previewMode): string
    {
        if ($previewMode === 'image') {
            return 'image';
        }

        if ($previewMode === 'link') {
            return 'link';
        }

        return 'file';
    }

    private function defaultPreviewIconByMode(string $previewMode): string
    {
        if ($previewMode === 'image') {
            return 'fa-image';
        }

        if ($previewMode === 'link') {
            return 'fa-link';
        }

        return 'fa-file-alt';
    }

    private function defaultPreviewLabelByMode(string $previewMode): string
    {
        if ($previewMode === 'image') {
            return 'Imagen';
        }

        if ($previewMode === 'link') {
            return 'Enlace';
        }

        return 'Archivo';
    }

    private function buildInitialSourceText(bool $isEdit, string $previewKind, string $previewMode): string
    {
        if ($isEdit) {
            if ($previewKind === 'image') {
                return 'Imagen actualmente configurada';
            }

            if ($previewKind === 'link') {
                return 'Enlace actualmente configurado';
            }

            return 'Archivo actualmente configurado';
        }

        if ($previewMode === 'image') {
            return 'Aun no se ha seleccionado una imagen';
        }

        if ($previewMode === 'link') {
            return 'Aun no se ha configurado un enlace';
        }

        return 'Aun no se ha seleccionado un archivo';
    }

    private function resolveAcceptTypes(string $previewMode): string
    {
        if ($previewMode === 'image') {
            return 'image/png,image/jpeg,image/jpg,image/webp,image/gif';
        }

        return '.pdf,image/png,image/jpeg,image/jpg,image/webp';
    }

    private function buildFormAlertMessage(bool $isEdit, array $typeConfig): string
    {
        if ($isEdit) {
            return 'Si no cambias '.($typeConfig['allows_file_upload'] ? 'el archivo ni ' : '').'la URL, se conserva el '.$typeConfig['singular_label'].' actual.';
        }

        if ($typeConfig['allows_file_upload']) {
            return 'Debes elegir una de estas opciones: cargar un archivo o indicar una URL externa.';
        }

        return 'Debes indicar la URL que la app movil usara para este enlace.';
    }

    private function buildFormNotes(array $typeConfig, int $selectedTypeId): array
    {
        if ($typeConfig['resource_family'] === 'media') {
            return [
                'Estas gestionando el tipo '.$selectedTypeId.' ('.$typeConfig['description'].').',
                'La app movil usa la columna URL para cargar el recurso visual correspondiente.',
                'El estado permite ocultar el recurso sin eliminarlo de la tabla.',
            ];
        }

        if ($typeConfig['resource_family'] === 'link') {
            return [
                'Estas gestionando el tipo '.$selectedTypeId.' ('.$typeConfig['description'].').',
                'Este grupo solo utiliza enlaces; no requiere carga de archivos.',
                'La app movil redirige al usuario a la URL configurada en este recurso.',
            ];
        }

        return [
            'Estas gestionando el tipo '.$selectedTypeId.' ('.$typeConfig['description'].').',
            'La app movil usa la columna URL para abrir el documento configurado.',
            'Si el recurso es PDF o imagen, el listado lo mostrara dentro del grupo documental.',
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

    private function storeResourceFile(UploadedFile $file, int $typeId, ?string $textAuxiliar = null): string
    {
        $typeConfig = $this->typeConfig($typeId);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = $this->buildResourceFilename($typeId, $typeConfig, $extension, $textAuxiliar);

        if ($this->shouldStoreBannerInCdn($typeConfig)) {
            return $this->storeBannerFileInCdn($file, $filename, $typeId);
        }

        if ($this->shouldStoreDocumentInCdn($typeConfig)) {
            return $this->storeDocumentFileInCdn($file, $filename, $typeId);
        }

        $path = $file->storeAs(
            trim(self::STORAGE_BASE_DIRECTORY.'/'.$typeConfig['storage_directory'], '/'),
            $filename,
            'public'
        );

        return $this->resolvePreviewUrl(Storage::disk('public')->url($path), $typeId);
    }

    private function buildResourceFilename(int $typeId, array $typeConfig, string $extension, ?string $textAuxiliar = null): string
    {
        if ($typeConfig['is_banner'] ?? false) {
            return $this->buildBannerFilename($typeId, $typeConfig, $extension);
        }

        if (($typeConfig['resource_family'] ?? '') === 'document') {
            return $this->buildDocumentFilename($typeId, $extension, $textAuxiliar);
        }

        return $typeConfig['storage_prefix'].'_'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;
    }

    private function buildBannerFilename(int $typeId, array $typeConfig, string $extension): string
    {
        $baseName = $this->bannerTypeBaseName((string) ($typeConfig['storage_prefix'] ?? 'banner'));
        $nextSequence = $this->nextBannerSequenceNumber($typeId, $baseName);

        return $baseName.'_'.$nextSequence.'.'.$extension;
    }

    private function bannerTypeBaseName(string $text): string
    {
        $normalized = (string) Str::of($text)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');

        return $normalized !== '' ? $normalized : 'banner';
    }

    private function nextBannerSequenceNumber(int $typeId, string $baseName): int
    {
        $pattern = '/^'.preg_quote($baseName, '/').'_(\d+)\.[a-z0-9]+$/i';
        $maxMatchedSequence = 0;

        $resourceUrls = RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->pluck('URL');

        foreach ($resourceUrls as $resourceUrl) {
            $path = (string) parse_url((string) $resourceUrl, PHP_URL_PATH);
            $fileName = basename($path);

            if (preg_match($pattern, $fileName, $matches) === 1) {
                $maxMatchedSequence = max($maxMatchedSequence, (int) $matches[1]);
            }
        }

        if ($maxMatchedSequence > 0) {
            return $maxMatchedSequence + 1;
        }

        return ((int) RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->count()) + 1;
    }

    private function buildDocumentFilename(int $typeId, string $extension, ?string $textAuxiliar = null): string
    {
        $baseName = $this->documentFileBaseName((string) $textAuxiliar);
        if ($baseName === '') {
            return 'documento_tipo_'.$typeId.'_'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;
        }

        $nextSequence = $this->nextDocumentSequenceNumber($typeId, $baseName);

        if ($nextSequence <= 1) {
            return $baseName.'.'.$extension;
        }

        return $baseName.'_'.$nextSequence.'.'.$extension;
    }

    private function documentFileBaseName(string $text): string
    {
        return (string) Str::of($text)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function nextDocumentSequenceNumber(int $typeId, string $baseName): int
    {
        $pattern = '/^'.preg_quote($baseName, '/').'(?:_(\d+))?\.[a-z0-9]+$/i';
        $maxSequence = 0;

        $resourceUrls = RecursosDigitales::query()
            ->where('Tipo', $typeId)
            ->pluck('URL');

        foreach ($resourceUrls as $resourceUrl) {
            $path = (string) parse_url((string) $resourceUrl, PHP_URL_PATH);
            $fileName = basename($path);

            if (preg_match($pattern, $fileName, $matches) !== 1) {
                continue;
            }

            $sequence = isset($matches[1]) && $matches[1] !== ''
                ? (int) $matches[1]
                : 1;
            $maxSequence = max($maxSequence, $sequence);
        }

        return $maxSequence + 1;
    }

    private function shouldStoreBannerInCdn(array $typeConfig): bool
    {
        if (! ($typeConfig['is_banner'] ?? false)) {
            return false;
        }

        if (! config('recursos_digitales.cdn.banner_upload_enabled', false)) {
            return false;
        }

        $relativePath = $this->cdnStorageRelativePath(
            'recursos_digitales.cdn.banner_relative_path',
            self::CDN_BANNER_DEFAULT_RELATIVE_PATH
        );

        if ($this->shouldUseDocumentalDiskForCdn()) {
            return $this->documentalDiskNameForCdn() !== '' && $relativePath !== '';
        }

        return $this->cdnRootPath() !== '' && $relativePath !== '';
    }

    private function storeBannerFileInCdn(UploadedFile $file, string $filename, int $typeId): string
    {
        $relativePath = $this->cdnStorageRelativePath(
            'recursos_digitales.cdn.banner_relative_path',
            self::CDN_BANNER_DEFAULT_RELATIVE_PATH
        );

        return $this->storeFileInCdnRelativePath(
            $file,
            $filename,
            $relativePath,
            $typeId,
            'No fue posible crear el directorio de destino para banners.'
        );
    }

    private function shouldStoreDocumentInCdn(array $typeConfig): bool
    {
        if (($typeConfig['resource_family'] ?? '') !== 'document') {
            return false;
        }

        if (! config('recursos_digitales.cdn.docs_upload_enabled', true)) {
            return false;
        }

        $relativePath = $this->cdnStorageRelativePath(
            'recursos_digitales.cdn.docs_relative_path',
            self::CDN_DOCS_DEFAULT_RELATIVE_PATH
        );

        if ($this->shouldUseDocumentalDiskForCdn()) {
            return $this->documentalDiskNameForCdn() !== '' && $relativePath !== '';
        }

        return $this->cdnRootPath() !== '' && $relativePath !== '';
    }

    private function storeDocumentFileInCdn(UploadedFile $file, string $filename, int $typeId): string
    {
        $baseDocsRelativePath = $this->cdnStorageRelativePath(
            'recursos_digitales.cdn.docs_relative_path',
            self::CDN_DOCS_DEFAULT_RELATIVE_PATH
        );
        $documentFolder = $this->resolveDocumentFolderByType($typeId);
        $relativePath = trim($baseDocsRelativePath.'/'.$documentFolder, '/');

        return $this->storeFileInCdnRelativePath(
            $file,
            $filename,
            $relativePath,
            $typeId,
            'No fue posible crear el directorio de destino para documentos.'
        );
    }

    private function resolveDocumentFolderByType(int $typeId): string
    {
        $defaultFolder = $this->sanitizeDocsFolderName((string) config('recursos_digitales.cdn.docs_default_folder', 'politicas'));
        if ($defaultFolder === '') {
            $defaultFolder = 'politicas';
        }

        $folderMap = (array) config('recursos_digitales.cdn.docs_by_type', []);
        $mappedFolder = $this->sanitizeDocsFolderName((string) ($folderMap[$typeId] ?? ''));
        $selectedFolder = $mappedFolder !== '' ? $mappedFolder : $defaultFolder;

        $allowedFolders = [];
        foreach ((array) config('recursos_digitales.cdn.docs_allowed_folders', []) as $folder) {
            $normalized = $this->sanitizeDocsFolderName((string) $folder);
            if ($normalized !== '') {
                $allowedFolders[$normalized] = true;
            }
        }

        if (! empty($allowedFolders) && ! isset($allowedFolders[$selectedFolder])) {
            return $defaultFolder;
        }

        return $selectedFolder;
    }

    private function sanitizeDocsFolderName(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->trim();
    }

    private function storeFileInCdnRelativePath(
        UploadedFile $file,
        string $filename,
        string $relativePath,
        int $typeId,
        string $directoryErrorMessage
    ): string {
        $normalizedRelativePath = trim(str_replace('\\', '/', $relativePath), '/');

        if ($normalizedRelativePath === '') {
            throw new Exception('No hay una ruta CDN valida configurada para almacenar el recurso.');
        }

        if ($this->shouldUseDocumentalDiskForCdn()) {
            $diskName = $this->documentalDiskNameForCdn();
            if ($diskName === '') {
                throw new Exception('No hay un disco documental configurado para almacenar el recurso.');
            }

            $targetFilePath = trim($normalizedRelativePath.'/'.$filename, '/');
            $stream = fopen($file->getRealPath(), 'rb');
            if ($stream === false) {
                throw new Exception('No fue posible abrir el archivo temporal para carga.');
            }

            try {
                $stored = Storage::disk($diskName)->writeStream($targetFilePath, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $stored) {
                throw new Exception('No fue posible guardar el archivo en el servidor SFTP configurado.');
            }

            return $this->buildCdnFilePublicUrl($targetFilePath, $typeId);
        }

        $rootPath = $this->cdnRootPath();
        if ($rootPath === '') {
            throw new Exception('No hay una ruta CDN valida configurada para almacenar el recurso.');
        }

        $targetDirectory = $rootPath.'/'.$normalizedRelativePath;
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0755, true) && ! is_dir($targetDirectory)) {
            throw new Exception($directoryErrorMessage);
        }

        $file->move($targetDirectory, $filename);

        return $this->buildCdnFilePublicUrl($normalizedRelativePath.'/'.$filename, $typeId);
    }

    private function buildCdnFilePublicUrl(string $relativeFilePath, int $typeId): string
    {
        $publicBaseUrl = $this->cdnPublicBaseUrl();
        $normalizedFilePath = ltrim($relativeFilePath, '/');

        if ($publicBaseUrl === '') {
            return $this->resolvePreviewUrl('/'.$normalizedFilePath, $typeId);
        }

        return $publicBaseUrl.'/'.$normalizedFilePath;
    }

    private function cdnRootPath(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('recursos_digitales.cdn.root_path', '')), '/');
    }

    private function cdnPublicBaseUrl(): string
    {
        if ($this->shouldUseDocumentalDiskForCdn()) {
            return trim($this->documentalStorageService()->obtenerPublicBaseUrlConfigurada(), '/');
        }

        return trim((string) config('recursos_digitales.cdn.public_base_url', ''), '/');
    }

    private function cdnRelativePath(string $configKey, string $default): string
    {
        return trim((string) config($configKey, $default), " \t\n\r\0\x0B/\\");
    }

    private function cdnStorageRelativePath(string $configKey, string $default): string
    {
        $relativePath = $this->cdnRelativePath($configKey, $default);
        if ($relativePath === '' || ! $this->shouldUseDocumentalDiskForCdn()) {
            return $relativePath;
        }

        $baseDirectory = trim($this->documentalStorageService()->obtenerBaseDirectoryConfigurada(), '/');
        if ($baseDirectory === '') {
            return $relativePath;
        }

        if ($relativePath === $baseDirectory || str_starts_with($relativePath, $baseDirectory.'/')) {
            return $relativePath;
        }

        return trim($baseDirectory.'/'.$relativePath, '/');
    }

    private function shouldUseDocumentalDiskForCdn(): bool
    {
        return (bool) config('recursos_digitales.cdn.use_documental_disk', false);
    }

    private function documentalDiskNameForCdn(): string
    {
        if (! $this->shouldUseDocumentalDiskForCdn()) {
            return '';
        }

        return trim((string) config('services.documental.disk', ''));
    }

    private function documentalStorageService(): DocumentalStorageService
    {
        return app(DocumentalStorageService::class);
    }

    private function deleteLocalResourceFile(?string $url): void
    {
        $path = parse_url((string) $url, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        $normalizedPath = trim(str_replace('\\', '/', $path), '/');
        if ($normalizedPath === '') {
            return;
        }

        if (str_starts_with($normalizedPath, 'storage/'.self::STORAGE_BASE_DIRECTORY.'/')) {
            $relativePath = Str::after($normalizedPath, 'storage/');

            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        $this->deleteFileFromCdnIfManaged($normalizedPath);
    }

    private function deleteFileFromCdnIfManaged(string $normalizedPath): void
    {
        foreach ($this->managedCdnRelativePrefixes() as $relativePrefix) {
            if (! str_starts_with($normalizedPath, $relativePrefix.'/')) {
                continue;
            }

            if ($this->shouldUseDocumentalDiskForCdn()) {
                $diskName = $this->documentalDiskNameForCdn();
                if ($diskName !== '' && Storage::disk($diskName)->exists($normalizedPath)) {
                    Storage::disk($diskName)->delete($normalizedPath);
                }

                return;
            }

            $rootPath = $this->cdnRootPath();
            if ($rootPath === '') {
                return;
            }

            $absolutePath = $rootPath.'/'.$normalizedPath;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            return;
        }
    }

    private function managedCdnRelativePrefixes(): array
    {
        $prefixes = [
            $this->cdnStorageRelativePath(
                'recursos_digitales.cdn.banner_relative_path',
                self::CDN_BANNER_DEFAULT_RELATIVE_PATH
            ),
            $this->cdnStorageRelativePath(
                'recursos_digitales.cdn.docs_relative_path',
                self::CDN_DOCS_DEFAULT_RELATIVE_PATH
            ),
        ];

        $filtered = [];
        foreach ($prefixes as $prefix) {
            if ($prefix !== '') {
                $filtered[$prefix] = $prefix;
            }
        }

        return array_values($filtered);
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
                'is_banner' => $isBanner,
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
