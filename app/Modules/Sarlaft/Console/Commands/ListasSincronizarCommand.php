<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Console\Commands;

use App\Modules\Sarlaft\Jobs\SincronizarListaJob;
use App\Modules\Sarlaft\Models\ListaVinculante;
use Illuminate\Console\Command;

class ListasSincronizarCommand extends Command
{
    protected $signature = 'listas:sincronizar';

    protected $description = 'Despachar jobs de sincronizacion para todas las listas SARLAFT activas';

    public function handle(): int
    {
        $sincronizadasDesdeConfig = $this->sincronizarListasDesdeConfig();

        if ($sincronizadasDesdeConfig > 0) {
            $this->info("Listas actualizadas desde config: {$sincronizadasDesdeConfig}.");
        }

        $listas = ListaVinculante::query()->where('activa', true)->get();

        if ($listas->isEmpty()) {
            $this->warn('No hay listas SARLAFT activas para encolar.');

            return self::SUCCESS;
        }

        foreach ($listas as $lista) {
            SincronizarListaJob::dispatch($lista);
            $this->info("Job despachado para: {$lista->nombre}");
        }

        $this->info("Total: {$listas->count()} listas encoladas.");

        return self::SUCCESS;
    }

    private function sincronizarListasDesdeConfig(): int
    {
        $listasConfig = collect(config('listas'))->filter(static function (mixed $item): bool {
            return is_array($item)
                && isset($item['nombre'], $item['url'], $item['parser']);
        });

        if ($listasConfig->isEmpty()) {
            return 0;
        }

        $listasExistentes = ListaVinculante::withTrashed()
            ->get()
            ->keyBy(static fn (ListaVinculante $lista): string => mb_strtolower(trim($lista->nombre)));

        $sincronizadas = 0;

        /** @var array<string, mixed> $configLista */
        foreach ($listasConfig as $configLista) {
            $nombre = trim((string) $configLista['nombre']);
            $llaveNombre = mb_strtolower($nombre);

            $payload = [
                'nombre' => $nombre,
                'tipo' => 'vinculante',
                'url_fuente' => (string) $configLista['url'],
                'frecuencia_sync' => (string) ($configLista['frecuencia'] ?? 'diaria'),
            ];

            /** @var ListaVinculante|null $lista */
            $lista = $listasExistentes->get($llaveNombre);

            if ($lista instanceof ListaVinculante) {
                $lista->fill($payload);

                if ($lista->trashed()) {
                    $lista->restore();
                    $lista->activa = true;
                }

                $lista->save();
            } else {
                $lista = ListaVinculante::create([
                    ...$payload,
                    'activa' => true,
                ]);

                $listasExistentes->put($llaveNombre, $lista);
            }

            $sincronizadas++;
        }

        return $sincronizadas;
    }
}
