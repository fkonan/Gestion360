<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';
    private const TIPO_DESCRIPCION = 'Permiso permanente';
    private const TIPO_TABLA = 'EMP_PERMISOS_PERMANENTES';
    private const DOC_CARTA_DESCRIPCION = 'CARTA SOLICITUD PERMISO PERMANENTE';
    private const DOC_SOPORTE_DESCRIPCION = 'DOCUMENTO SOPORTE PERMISO PERMANENTE';
    private const DOC_CARTA_CODIGO = 'DOCUMENTOS-PERMISO-PERMANENTE-CARTA';
    private const DOC_SOPORTE_CODIGO = 'DOCUMENTOS-PERMISO-PERMANENTE-SOPORTE';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);
        if (! $schema->hasTable('EMP_NOVEDADES_TIPO')) {
            return;
        }

        $tipoId = $this->resolverTipoId();
        if ($tipoId === null) {
            $tipoId = (string) Str::uuid();
            DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->insert($this->payloadTipo($schema, $tipoId));
        }

        if (! $schema->hasTable('EMP_TIPOS_DOCUMENTOS_NOVEDAD')) {
            return;
        }

        $this->upsertTipoDocumento(
            schema: $schema,
            tipoId: $tipoId,
            descripcion: self::DOC_CARTA_DESCRIPCION,
            codigo: self::DOC_CARTA_CODIGO
        );

        $this->upsertTipoDocumento(
            schema: $schema,
            tipoId: $tipoId,
            descripcion: self::DOC_SOPORTE_DESCRIPCION,
            codigo: self::DOC_SOPORTE_CODIGO
        );
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function resolverTipoId(): ?string
    {
        $id = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_TIPO')
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(tabla, '')) = ?", [self::TIPO_TABLA])
                    ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", [mb_strtoupper(self::TIPO_DESCRIPCION, 'UTF-8')])
                    ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['PERMISO_PERMANENTE']);
            })
            ->value('id');

        if (! is_string($id) || trim($id) === '') {
            return null;
        }

        return trim($id);
    }

    private function payloadTipo($schema, string $tipoId): array
    {
        $payload = [
            'id' => $tipoId,
            'descripcion' => self::TIPO_DESCRIPCION,
            'tabla' => self::TIPO_TABLA,
        ];

        if ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'activo')) {
            $payload['activo'] = 1;
        }

        if ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'requiere_bloqueo')) {
            $payload['requiere_bloqueo'] = 0;
        }

        if ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'fecha_creacion')) {
            $payload['fecha_creacion'] = now();
        }

        if ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'fecha_modifica')) {
            $payload['fecha_modifica'] = now();
        }

        return $payload;
    }

    private function upsertTipoDocumento($schema, string $tipoId, string $descripcion, string $codigo): void
    {
        $existe = DB::connection(self::CONNECTION)
            ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
            ->where('id_tipo_novedad', $tipoId)
            ->whereRaw("UPPER(NVL(descripcion, '')) = ?", [mb_strtoupper($descripcion, 'UTF-8')])
            ->exists();

        if ($existe) {
            return;
        }

        $payload = [
            'id' => (string) Str::uuid(),
            'descripcion' => $descripcion,
            'id_tipo_novedad' => $tipoId,
        ];

        if ($schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'codigo')) {
            $payload['codigo'] = $codigo;
        }

        if ($schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'activo')) {
            $payload['activo'] = 1;
        }

        if ($schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'fecha_creacion')) {
            $payload['fecha_creacion'] = now();
        }

        if ($schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'fecha_modifica')) {
            $payload['fecha_modifica'] = now();
        }

        DB::connection(self::CONNECTION)
            ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
            ->insert($payload);
    }
};
