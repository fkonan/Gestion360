<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('mysql-gestion-admin');

        $connection->table('prs_tipo_bloqueo')
            ->where('id', 11)
            ->update(['handler_key' => 'descanso']);

        $connection->table('prs_tipo_bloqueo')
            ->where('id', 35)
            ->update(['handler_key' => 'preoperacional_api']);

        $rows = [
            [
                'tipo_bloqueo_id' => 11,
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
                'tipo_bloqueo_id' => 11,
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
                'tipo_bloqueo_id' => 11,
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
            [
                'tipo_bloqueo_id' => 35,
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
            ],
        ];

        foreach ($rows as $row) {
            $tipoExiste = $connection->table('prs_tipo_bloqueo')
                ->where('id', $row['tipo_bloqueo_id'])
                ->exists();

            if (! $tipoExiste) {
                continue;
            }

            $existe = $connection->table('prs_tipo_bloqueo_campos')
                ->where('tipo_bloqueo_id', $row['tipo_bloqueo_id'])
                ->where('nombre_campo', $row['nombre_campo'])
                ->exists();

            if (! $existe) {
                $connection->table('prs_tipo_bloqueo_campos')->insert($row);
            }
        }
    }

    public function down(): void
    {
        $connection = DB::connection('mysql-gestion-admin');

        $connection->table('prs_tipo_bloqueo')
            ->whereIn('id', [11, 35])
            ->update(['handler_key' => null]);

        $connection->table('prs_tipo_bloqueo_campos')
            ->where(function ($query) {
                $query->where('tipo_bloqueo_id', 11)
                    ->whereIn('nombre_campo', ['evento', 'fecha', 'observacion']);
            })
            ->orWhere(function ($query) {
                $query->where('tipo_bloqueo_id', 35)
                    ->where('nombre_campo', 'observacion');
            })
            ->delete();
    }
};
