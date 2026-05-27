<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $this->actualizarEmpNovedades($schema);
        $this->actualizarEmpNovedadesTipo($schema);
        $this->actualizarEmpNovedadesHistorial($schema);
        $this->actualizarEmpTiposDocumentosNovedad($schema);
        $this->actualizarEmpNovedadesDocumentos($schema);
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
        // Se acordó no borrar tablas ni deshacer cambios de esquema en Oracle 360
        // dentro de esta etapa de reestructuracion del modelo de novedades.
    }

    private function actualizarEmpNovedades($schema): void
    {
        if (! $schema->hasTable('EMP_NOVEDADES')) {
            return;
        }

        $schema->table('EMP_NOVEDADES', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_NOVEDADES', 'id_origen')) {
                $table->string('id_origen', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_NOVEDADES', 'usuario_creacion')) {
                $table->string('usuario_creacion', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_NOVEDADES', 'resumen')) {
                $table->text('resumen')->nullable();
            }
        });
    }

    private function actualizarEmpNovedadesTipo($schema): void
    {
        if (! $schema->hasTable('EMP_NOVEDADES_TIPO')) {
            return;
        }

        $schema->table('EMP_NOVEDADES_TIPO', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'codigo')) {
                $table->string('codigo', 80)->nullable();
            }

            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'activo')) {
                $table->unsignedSmallInteger('activo')->default(1);
            }

            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'justifica_tardanza')) {
                $table->unsignedSmallInteger('justifica_tardanza')->default(0);
            }

            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'afecta_operacion')) {
                $table->unsignedSmallInteger('afecta_operacion')->default(0);
            }

            if (! $schema->hasColumn('EMP_NOVEDADES_TIPO', 'requiere_bloqueo')) {
                $table->unsignedSmallInteger('requiere_bloqueo')->default(0);
            }
        });
    }

    private function actualizarEmpNovedadesHistorial($schema): void
    {
        if (! $schema->hasTable('EMP_NOVEDADES_HISTORIAL')) {
            return;
        }

        $schema->table('EMP_NOVEDADES_HISTORIAL', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_NOVEDADES_HISTORIAL', 'tipo_evento')) {
                $table->string('tipo_evento', 80)->nullable();
            }
        });
    }

    private function actualizarEmpTiposDocumentosNovedad($schema): void
    {
        if (! $schema->hasTable('EMP_TIPOS_DOCUMENTOS_NOVEDAD')) {
            return;
        }

        $schema->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'id_tipo_novedad')) {
                $table->string('id_tipo_novedad', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'codigo')) {
                $table->string('codigo', 80)->nullable();
            }

            if (! $schema->hasColumn('EMP_TIPOS_DOCUMENTOS_NOVEDAD', 'activo')) {
                $table->unsignedSmallInteger('activo')->default(1);
            }
        });
    }

    private function actualizarEmpNovedadesDocumentos($schema): void
    {
        if (! $schema->hasTable('EMP_NOVEDADES_DOCUMENTOS')) {
            return;
        }

        $schema->table('EMP_NOVEDADES_DOCUMENTOS', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_NOVEDADES_DOCUMENTOS', 'ruta_documento')) {
                $table->string('ruta_documento', 1000)->nullable();
            }
        });
    }
};
