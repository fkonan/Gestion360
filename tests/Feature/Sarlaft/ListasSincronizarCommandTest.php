<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Jobs\SincronizarListaJob;
use App\Modules\Sarlaft\Models\ListaVinculante;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListasSincronizarCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql-sarlaft' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('mysql-sarlaft');
        DB::connection('mysql-sarlaft')->getPdo();

        $this->crearEsquemaSarlaft();
    }

    public function test_crea_lista_faltante_desde_config_y_encola_solo_activas(): void
    {
        config([
            'listas' => [
                'verificar_ssl' => false,
                'ofac_api_base' => 'https://sanctionslistservice.ofac.treas.gov',
                'ofac_sdn' => [
                    'nombre' => 'OFAC SDN',
                    'url' => 'https://sanctionslistservice.ofac.treas.gov/api/download/SDN.XML',
                    'formato' => 'xml',
                    'parser' => 'ofac',
                    'frecuencia' => 'diaria',
                ],
                'union_europea' => [
                    'nombre' => 'Union Europea Consolidada',
                    'url' => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content',
                    'formato' => 'xml',
                    'parser' => 'eu',
                    'frecuencia' => 'diaria',
                ],
            ],
        ]);

        ListaVinculante::query()->create([
            'nombre' => 'OFAC SDN',
            'tipo' => 'vinculante',
            'url_fuente' => 'https://ejemplo.local/ofac-antigua.xml',
            'frecuencia_sync' => 'mensual',
            'activa' => false,
        ]);

        Queue::fake();

        $this->artisan('listas:sincronizar')->assertSuccessful();

        $this->assertDatabaseCount('sarlaft_listas_vinculantes', 2, 'mysql-sarlaft');

        $this->assertDatabaseHas('sarlaft_listas_vinculantes', [
            'nombre' => 'Union Europea Consolidada',
            'tipo' => 'vinculante',
            'url_fuente' => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content',
            'frecuencia_sync' => 'diaria',
            'activa' => 1,
        ], 'mysql-sarlaft');

        $this->assertDatabaseHas('sarlaft_listas_vinculantes', [
            'nombre' => 'OFAC SDN',
            'url_fuente' => 'https://sanctionslistservice.ofac.treas.gov/api/download/SDN.XML',
            'frecuencia_sync' => 'diaria',
            'activa' => 0,
        ], 'mysql-sarlaft');

        Queue::assertPushed(SincronizarListaJob::class, 1);
        Queue::assertPushed(
            SincronizarListaJob::class,
            static fn (SincronizarListaJob $job): bool => $job->lista->nombre === 'Union Europea Consolidada'
        );
    }

    public function test_restaura_lista_soft_deleted_desde_config_y_la_activa(): void
    {
        config([
            'listas' => [
                'union_europea' => [
                    'nombre' => 'Union Europea Consolidada',
                    'url' => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content',
                    'formato' => 'xml',
                    'parser' => 'eu',
                    'frecuencia' => 'diaria',
                ],
            ],
        ]);

        $lista = ListaVinculante::query()->create([
            'nombre' => 'Union Europea Consolidada',
            'tipo' => 'vinculante',
            'url_fuente' => 'https://ejemplo.local/ue-antigua.xml',
            'frecuencia_sync' => 'semanal',
            'activa' => false,
        ]);
        $lista->delete();

        Queue::fake();

        $this->artisan('listas:sincronizar')->assertSuccessful();

        $this->assertDatabaseHas('sarlaft_listas_vinculantes', [
            'id' => $lista->id,
            'nombre' => 'Union Europea Consolidada',
            'url_fuente' => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content',
            'frecuencia_sync' => 'diaria',
            'activa' => 1,
            'deleted_at' => null,
        ], 'mysql-sarlaft');

        Queue::assertPushed(SincronizarListaJob::class, 1);
        Queue::assertPushed(
            SincronizarListaJob::class,
            static fn (SincronizarListaJob $job): bool => $job->lista->id === $lista->id
        );
    }

    private function crearEsquemaSarlaft(): void
    {
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_listas_vinculantes');

        Schema::connection('mysql-sarlaft')->create('sarlaft_listas_vinculantes', static function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->string('tipo', 20);
            $table->string('url_fuente', 500)->nullable();
            $table->string('frecuencia_sync', 50)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamp('ultima_sincronizacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
