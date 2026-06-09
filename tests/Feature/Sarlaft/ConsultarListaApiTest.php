<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsultarListaApiTest extends TestCase
{
    private string $token = 'token-consulta-test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql-sarlaft' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('mysql-sarlaft');
        DB::connection('mysql-sarlaft')->getPdo();
        $this->crearEsquema();

        SistemaConsumidor::query()->create([
            'nombre' => 'Empresa Externa',
            'codigo' => 'externa',
            'api_token' => $this->token,
            'estado' => 'activo',
            'modo_integracion' => 'push',
            'limite_requests_minuto' => 1000,
        ]);
    }

    public function test_responde_true_si_el_documento_esta_en_lista_vinculante(): void
    {
        $this->crearListaVinculante();
        RegistroLista::query()->create([
            'lista_id' => 1,
            'identificacion' => '900111',
            'nombres' => 'Persona X',
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
            'numero_documento' => '900111',
        ], ['Authorization' => 'Bearer '.$this->token])
            ->assertOk()
            ->assertExactJson(['en_lista' => true]);
    }

    public function test_responde_true_si_esta_en_lista_restrictiva_interna(): void
    {
        ListaNegraInterna::query()->create([
            'tipo_entidad' => 'persona',
            'tipo_documento' => 'CC',
            'numero_documento' => '800222',
            'nombres' => 'Persona Y',
            'motivo' => 'Restriccion interna',
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
            'numero_documento' => '800222',
        ], ['Authorization' => 'Bearer '.$this->token])
            ->assertOk()
            ->assertExactJson(['en_lista' => true]);
    }

    public function test_responde_false_si_el_documento_no_esta_en_ninguna_lista(): void
    {
        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
            'numero_documento' => '99999999',
        ], ['Authorization' => 'Bearer '.$this->token])
            ->assertOk()
            ->assertExactJson(['en_lista' => false]);
    }

    public function test_requiere_token_valido(): void
    {
        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
            'numero_documento' => '900111',
        ])->assertUnauthorized();
    }

    public function test_valida_que_el_documento_es_obligatorio(): void
    {
        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
        ], ['Authorization' => 'Bearer '.$this->token])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('numero_documento');
    }

    public function test_no_registra_intento_ni_alerta(): void
    {
        $this->crearListaVinculante();
        RegistroLista::query()->create([
            'lista_id' => 1,
            'identificacion' => '900111',
            'nombres' => 'Persona X',
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
            'numero_documento' => '900111',
        ], ['Authorization' => 'Bearer '.$this->token])->assertOk();

        // La consulta no debe dejar rastro de intento ni alerta.
        $this->assertSame(0, DB::connection('mysql-sarlaft')->table('sarlaft_intentos_operacion')->count());
        $this->assertSame(0, DB::connection('mysql-sarlaft')->table('sarlaft_alertas')->count());
    }

    private function crearListaVinculante(): void
    {
        ListaVinculante::query()->create([
            'id' => 1,
            'nombre' => 'OFAC SDN',
            'tipo' => 'vinculante',
            'activa' => true,
        ]);
    }

    private function crearEsquema(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_sistemas_consumidores', static function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50);
            $table->string('api_token', 100)->nullable();
            $table->string('estado', 20)->default('activo');
            $table->string('modo_integracion', 20)->default('push');
            $table->integer('limite_requests_minuto')->default(100);
            $table->string('pull_endpoint', 500)->nullable();
            $table->string('pull_token', 255)->nullable();
            $table->string('db_conexion', 100)->nullable();
            $table->string('db_tabla', 150)->nullable();
            $table->string('db_filtro_sistema_origen', 100)->nullable();
            $table->timestamp('db_ultima_lectura_at')->nullable();
            $table->unsignedBigInteger('db_ultimo_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

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

        Schema::connection('mysql-sarlaft')->create('sarlaft_registros_lista', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lista_id')->nullable();
            $table->string('tipo_entidad', 20)->default('persona');
            $table->string('identificacion')->nullable();
            $table->string('tipo_identificacion')->nullable();
            $table->string('nombres')->nullable();
            $table->text('alias')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_lista_negra_interna', static function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_entidad', 20)->default('persona');
            $table->string('tipo_documento', 20)->nullable();
            $table->string('numero_documento', 50)->nullable();
            $table->string('nombres', 500)->nullable();
            $table->text('motivo')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_intentos_operacion', static function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_alertas', static function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }
}
