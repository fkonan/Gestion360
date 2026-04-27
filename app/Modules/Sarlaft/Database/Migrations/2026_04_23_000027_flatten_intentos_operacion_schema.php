<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql-sarlaft';

    public function up(): void
    {
        $schema = Schema::connection('mysql-sarlaft');

        if (! $schema->hasTable('sarlaft_intentos_operacion')) {
            return;
        }

        $schema->table('sarlaft_intentos_operacion', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'sistema_origen')) {
                $table->string('sistema_origen', 100)->nullable()->after('sistema_id');
            }

            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'tipo_documento')) {
                $table->string('tipo_documento', 20)->nullable()->after('modo_integracion');
            }

            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'numero_documento')) {
                $table->string('numero_documento', 50)->nullable()->after('tipo_documento');
            }

            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'nombre')) {
                $table->string('nombre', 300)->nullable()->after('numero_documento');
            }

            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'tipo_lista')) {
                $table->string('tipo_lista', 100)->nullable()->after('nombre');
            }

            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'lista_nombre')) {
                $table->string('lista_nombre', 150)->nullable()->after('tipo_lista');
            }

            if (! $schema->hasColumn('sarlaft_intentos_operacion', 'referencia')) {
                $table->string('referencia', 100)->nullable()->after('tipo_operacion');
            }
        });

        $this->backfillReferencia();
        $this->backfillSistemaOrigen();
        $this->backfillPersonaDesdeIntentoPersonas();

        $schema->dropIfExists('sarlaft_intento_personas');
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql-sarlaft');

        if ($schema->hasTable('sarlaft_intentos_operacion') && ! $schema->hasTable('sarlaft_intento_personas')) {
            $schema->create('sarlaft_intento_personas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('intento_id')
                    ->constrained('sarlaft_intentos_operacion')
                    ->cascadeOnDelete();
                $table->string('tipo_documento', 20);
                $table->string('numero_documento', 50);
                $table->string('nombre', 300)->nullable();
                $table->string('rol', 50);
                $table->enum('tipo_lista', ['vinculante', 'restrictiva']);
                $table->string('lista_nombre', 150);
                $table->json('detalle_coincidencia')->nullable();

                $table->index(['intento_id'], 'idx_intento_persona_intento');
                $table->index(['tipo_documento', 'numero_documento'], 'idx_intento_persona_doc');
            });

            $this->backfillIntentoPersonasDesdeIntentos();
        }

        if (! $schema->hasTable('sarlaft_intentos_operacion')) {
            return;
        }

        $columnasDrop = array_values(array_filter([
            'sistema_origen',
            'tipo_documento',
            'numero_documento',
            'nombre',
            'tipo_lista',
            'lista_nombre',
            'referencia',
        ], static fn (string $columna): bool => $schema->hasColumn('sarlaft_intentos_operacion', $columna)));

        if ($columnasDrop !== []) {
            $schema->table('sarlaft_intentos_operacion', function (Blueprint $table) use ($columnasDrop): void {
                $table->dropColumn($columnasDrop);
            });
        }
    }

    private function backfillReferencia(): void
    {
        $conexion = DB::connection('mysql-sarlaft');

        $conexion->table('sarlaft_intentos_operacion')
            ->whereNull('referencia')
            ->whereNotNull('referencia_externa')
            ->update([
                'referencia' => DB::raw('referencia_externa'),
            ]);
    }

    private function backfillSistemaOrigen(): void
    {
        $conexion = DB::connection('mysql-sarlaft');

        $intentos = $conexion->table('sarlaft_intentos_operacion')
            ->select(['id', 'sistema_id', 'sistema_origen'])
            ->orderBy('id')
            ->get();

        foreach ($intentos as $intento) {
            if (is_string($intento->sistema_origen) && trim($intento->sistema_origen) !== '') {
                continue;
            }

            $codigoSistema = $conexion->table('sarlaft_sistemas_consumidores')
                ->where('id', (int) $intento->sistema_id)
                ->value('codigo');

            if (! is_string($codigoSistema) || trim($codigoSistema) === '') {
                continue;
            }

            $conexion->table('sarlaft_intentos_operacion')
                ->where('id', (int) $intento->id)
                ->update(['sistema_origen' => trim($codigoSistema)]);
        }
    }

    private function backfillPersonaDesdeIntentoPersonas(): void
    {
        $schema = Schema::connection('mysql-sarlaft');

        if (! $schema->hasTable('sarlaft_intento_personas')) {
            return;
        }

        $conexion = DB::connection('mysql-sarlaft');
        $personas = $conexion->table('sarlaft_intento_personas as p')
            ->select([
                'p.intento_id',
                'p.tipo_documento',
                'p.numero_documento',
                'p.nombre',
                'p.tipo_lista',
                'p.lista_nombre',
            ])
            ->whereRaw('p.id = (SELECT MIN(p2.id) FROM sarlaft_intento_personas p2 WHERE p2.intento_id = p.intento_id)')
            ->orderBy('p.intento_id')
            ->get();

        foreach ($personas as $persona) {
            $conexion->table('sarlaft_intentos_operacion')
                ->where('id', (int) $persona->intento_id)
                ->update([
                    'tipo_documento' => (string) $persona->tipo_documento,
                    'numero_documento' => (string) $persona->numero_documento,
                    'nombre' => is_string($persona->nombre) ? $persona->nombre : null,
                    'tipo_lista' => (string) $persona->tipo_lista,
                    'lista_nombre' => (string) $persona->lista_nombre,
                ]);
        }
    }

    private function backfillIntentoPersonasDesdeIntentos(): void
    {
        $conexion = DB::connection('mysql-sarlaft');

        $intentos = $conexion->table('sarlaft_intentos_operacion')
            ->select([
                'id',
                'tipo_documento',
                'numero_documento',
                'nombre',
                'tipo_lista',
                'lista_nombre',
            ])
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($intentos as $intento) {
            if (! is_string($intento->tipo_documento) || trim($intento->tipo_documento) === '') {
                continue;
            }

            if (! is_string($intento->numero_documento) || trim($intento->numero_documento) === '') {
                continue;
            }

            $tipoLista = is_string($intento->tipo_lista) && trim($intento->tipo_lista) !== ''
                ? strtolower(trim($intento->tipo_lista))
                : 'restrictiva';

            $rows[] = [
                'intento_id' => (int) $intento->id,
                'tipo_documento' => trim((string) $intento->tipo_documento),
                'numero_documento' => trim((string) $intento->numero_documento),
                'nombre' => is_string($intento->nombre) && trim($intento->nombre) !== '' ? trim($intento->nombre) : null,
                'rol' => 'cliente',
                'tipo_lista' => $tipoLista === 'vinculante' ? 'vinculante' : 'restrictiva',
                'lista_nombre' => is_string($intento->lista_nombre) && trim($intento->lista_nombre) !== ''
                    ? trim($intento->lista_nombre)
                    : 'N/A',
                'detalle_coincidencia' => null,
            ];
        }

        if ($rows !== []) {
            $conexion->table('sarlaft_intento_personas')->insert($rows);
        }
    }
};
