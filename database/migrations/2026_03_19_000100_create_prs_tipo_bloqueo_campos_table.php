<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql-gestion-admin');

        if (! $schema->hasTable('prs_tipo_bloqueo_campos')) {
            $schema->create('prs_tipo_bloqueo_campos', function (Blueprint $table) {
                $table->id();
                $table->integer('tipo_bloqueo_id');
                $table->string('nombre_campo', 80);
                $table->string('label', 120);
                $table->string('tipo_input', 40);
                $table->boolean('requerido')->default(false);
                $table->unsignedInteger('orden')->default(10);
                $table->string('placeholder')->nullable();
                $table->string('help_text', 255)->nullable();
                $table->string('reglas_laravel', 255)->nullable();
                $table->string('valor_default', 255)->nullable();
                $table->string('fuente_opciones', 80)->nullable();
                $table->json('opciones_json')->nullable();
                $table->boolean('visible')->default(true);
                $table->boolean('estado')->default(true);
                $table->timestamps();

                $table->unique(['tipo_bloqueo_id', 'nombre_campo'], 'prs_tipo_bloqueo_campos_unico');
            });
        }

        $connection = DB::connection('mysql-gestion-admin');
        $database = $connection->getDatabaseName();

        $connection->statement('ALTER TABLE prs_tipo_bloqueo_campos MODIFY tipo_bloqueo_id INT NOT NULL');

        $foreignKeyExists = collect($connection->select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME = ?",
            [$database, 'prs_tipo_bloqueo_campos', 'prs_tipo_bloqueo_campos_tipo_fk']
        ))->isNotEmpty();

        if (! $foreignKeyExists) {
            $connection->statement(
                'ALTER TABLE prs_tipo_bloqueo_campos ADD CONSTRAINT prs_tipo_bloqueo_campos_tipo_fk FOREIGN KEY (tipo_bloqueo_id) REFERENCES prs_tipo_bloqueo(id) ON DELETE CASCADE'
            );
        }

        $tipos = $connection->table('prs_tipo_bloqueo')
            ->whereIn('id', [11, 35])
            ->pluck('id', 'id');

        $rows = [];

        $tipoDescansoId = $tipos->get(11);
        $tipoPreoperacionalId = $tipos->get(35);

        if (! empty($tipoDescansoId)) {
            $rows = array_merge($rows, [
                [
                    'tipo_bloqueo_id' => $tipoDescansoId,
                    'nombre_campo' => 'evento',
                    'label' => 'Tipo de levantamiento',
                    'tipo_input' => 'select',
                    'requerido' => 1,
                    'orden' => 10,
                    'placeholder' => null,
                    'help_text' => 'Seleccione el evento con el que se levantara el bloqueo de descanso.',
                    'reglas_laravel' => 'required|in:25,49',
                    'valor_default' => null,
                    'fuente_opciones' => 'descanso_eventos_levantamiento',
                    'opciones_json' => null,
                    'visible' => 1,
                    'estado' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tipo_bloqueo_id' => $tipoDescansoId,
                    'nombre_campo' => 'fecha',
                    'label' => 'Fecha y hora de reingreso',
                    'tipo_input' => 'datetime',
                    'requerido' => 1,
                    'orden' => 20,
                    'placeholder' => null,
                    'help_text' => 'La fecha no puede ser futura.',
                    'reglas_laravel' => 'required|date|before_or_equal:now',
                    'valor_default' => null,
                    'fuente_opciones' => null,
                    'opciones_json' => null,
                    'visible' => 1,
                    'estado' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tipo_bloqueo_id' => $tipoDescansoId,
                    'nombre_campo' => 'observacion',
                    'label' => 'Observacion',
                    'tipo_input' => 'textarea',
                    'requerido' => 1,
                    'orden' => 30,
                    'placeholder' => 'Observaciones del levantamiento',
                    'help_text' => null,
                    'reglas_laravel' => 'required|string|max:500',
                    'valor_default' => null,
                    'fuente_opciones' => null,
                    'opciones_json' => null,
                    'visible' => 1,
                    'estado' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (! empty($tipoPreoperacionalId)) {
            $rows[] = [
                'tipo_bloqueo_id' => $tipoPreoperacionalId,
                'nombre_campo' => 'observacion',
                'label' => 'Observacion',
                'tipo_input' => 'textarea',
                'requerido' => 1,
                'orden' => 10,
                'placeholder' => 'Observaciones del desbloqueo',
                'help_text' => 'Esta observacion se almacenara como soporte del levantamiento COP.',
                'reglas_laravel' => 'required|string|max:500',
                'valor_default' => null,
                'fuente_opciones' => null,
                'opciones_json' => null,
                'visible' => 1,
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($rows as $row) {
            $exists = $connection->table('prs_tipo_bloqueo_campos')
                ->where('tipo_bloqueo_id', $row['tipo_bloqueo_id'])
                ->where('nombre_campo', $row['nombre_campo'])
                ->exists();

            if (! $exists) {
                $connection->table('prs_tipo_bloqueo_campos')->insert($row);
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql-gestion-admin');

        if ($schema->hasTable('prs_tipo_bloqueo_campos')) {
            $schema->drop('prs_tipo_bloqueo_campos');
        }
    }
};
