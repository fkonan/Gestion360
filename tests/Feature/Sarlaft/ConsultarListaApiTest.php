<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsultarListaApiTest extends TestCase
{
    private string $clientId = 'externa';

    private string $clientSecret = 'secret-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        // Secret de firma JWT para el motor transversal.
        config([
            'services.api_jwt.secret' => 'clave-de-firma-de-prueba-suficientemente-larga',
            'services.api_jwt.issuer' => 'autogestion',
            'services.api_jwt.audience' => 'autogestion-api',
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
            'codigo' => $this->clientId,
            'api_token' => 'no-bearer',
            'client_secret' => Hash::make($this->clientSecret),
            'scopes' => 'sarlaft.listas.consultar',
            'estado' => 'activo',
            'modo_integracion' => 'push',
            'limite_requests_minuto' => 1000,
        ]);
    }

    /**
     * Pide un JWT valido al endpoint de token y lo devuelve.
     */
    private function obtenerJwt(): string
    {
        $respuesta = $this->postJson('/api/v1/auth/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ])->assertOk();

        return (string) $respuesta->json('data.access_token');
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->obtenerJwt()];
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
        ], $this->authHeaders())
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
        ], $this->authHeaders())
            ->assertOk()
            ->assertExactJson(['en_lista' => true]);
    }

    public function test_responde_false_si_el_documento_no_esta_en_ninguna_lista(): void
    {
        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
            'numero_documento' => '99999999',
        ], $this->authHeaders())
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

    public function test_emite_token_con_credenciales_validas(): void
    {
        $this->postJson('/api/v1/auth/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['data' => ['access_token', 'expires_in', 'scope']]);
    }

    public function test_no_emite_token_con_secret_incorrecto(): void
    {
        $this->postJson('/api/v1/auth/token', [
            'client_id' => $this->clientId,
            'client_secret' => 'secret-malo',
        ])->assertUnauthorized();
    }

    public function test_valida_que_el_documento_es_obligatorio(): void
    {
        $this->postJson('/api/v1/listas/consultar', [
            'tipo_documento' => 'CC',
        ], $this->authHeaders())
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
        ], $this->authHeaders())->assertOk();

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
            $table->string('client_secret', 255)->nullable();
            $table->string('scopes', 500)->nullable();
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
