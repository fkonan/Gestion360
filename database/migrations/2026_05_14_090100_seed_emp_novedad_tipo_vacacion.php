<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';
    private const TIPO_DESCRIPCION = 'VACACION';
    private const TIPO_TABLA = 'EMP_VACACIONES';
    private const DOC_DESCRIPCION = 'CARTA SOLICITUD VACACIONES';
    private const DOC_CODIGO = 'DOCUMENTOS-VACACIONES';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);
        if (! $schema->hasTable('EMP_NOVEDADES_TIPO')) {
            return;
        }

        $tipoId = $this->resolverTipoVacacionId();
        if ($tipoId === null) {
            $tipoId = (string) Str::uuid();
            DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->insert($this->payloadTipoVacacion($schema, $tipoId));
        }

        if (! $schema->hasTable('EMP_TIPOS_DOCUMENTOS_NOVEDAD')) {
            return;
        }

        $queryDoc = DB::connection(self::CONNECTION)
            ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
            ->whereRaw('UPPER(NVL(descripcion, \'\')) = ?', [self::DOC_DESCRIPCION])
            ->where('id_tipo_novedad', $tipoId);

        if ($queryDoc->exists()) {
            return;
        }

        DB::connection(self::CONNECTION)
            ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
            ->insert($this->payloadDocumentoVacacion($schema, $tipoId));
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }

    private function resolverTipoVacacionId(): ?string
    {
        $id = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_TIPO')
            ->where(function ($query) {
                $query->whereRaw('UPPER(NVL(tabla, \'\')) = ?', [self::TIPO_TABLA])
                    ->orWhereRaw('UPPER(NVL(descripcion, \'\')) = ?', [self::TIPO_DESCRIPCION]);
            })
            ->value('id');

        if (! is_string($id) || trim($id) === '') {
            return null;
        }

        return trim($id);
    }

    private function payloadTipoVacacion($schema, string $id): array
    {
        $payload = [
            'id' => $id,
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

    private function payloadDocumentoVacacion($schema, string $tipoId): array
    {
        $payload = [
            'id' => (string) Str::uuid(),
            'descripcion' => self::DOC_DESCRIPCION,
            'id_tipo_novedad' => $tipoId,
        ];

        if ($schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'codigo')) {
            $payload['codigo'] = self::DOC_CODIGO;
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

        return $payload;
    }
};
