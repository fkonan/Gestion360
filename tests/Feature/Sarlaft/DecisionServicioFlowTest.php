<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Bloqueo;
use App\Modules\Sarlaft\Services\ConsultaService;
use App\Modules\Sarlaft\Services\DecisionServicioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DecisionServicioFlowTest extends TestCase
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

    public function test_decision_bloquear_fuerza_no_presta_servicio(): void
    {
        $this->crearDecisionActiva('CC', '123', 'bloquear');

        $consulta = app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '123',
            'nombre' => null,
        ], '127.0.0.1', 'test');

        $this->assertFalse($consulta->presta_servicio);
        $this->assertSame('alto', $consulta->nivel_riesgo);
    }

    public function test_permitir_una_operacion_consumo_en_primera_consulta_bloqueante(): void
    {
        $this->crearBloqueoManual('CC', '200');
        $alerta = $this->crearDecisionActiva('CC', '200', 'permitir_una_operacion');
        $service = app(ConsultaService::class);

        $primeraConsulta = $service->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '200',
            'nombre' => null,
        ], '127.0.0.1', 'test');

        $alerta->refresh();
        $this->assertTrue($primeraConsulta->presta_servicio);
        $this->assertFalse((bool) $alerta->decision_activa);
        $this->assertNotNull($alerta->decision_consumida_at);
        $this->assertSame($primeraConsulta->id, (int) $alerta->decision_consumida_consulta_id);

        $segundaConsulta = $service->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '200',
            'nombre' => null,
        ], '127.0.0.1', 'test');

        $this->assertFalse($segundaConsulta->presta_servicio);
        $this->assertSame('alto', $segundaConsulta->nivel_riesgo);
    }

    public function test_permitir_una_operacion_no_se_consume_si_consulta_no_bloqueaba(): void
    {
        $alerta = $this->crearDecisionActiva('CC', '201', 'permitir_una_operacion');

        $consulta = app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '201',
            'nombre' => null,
        ], '127.0.0.1', 'test');

        $alerta->refresh();

        $this->assertTrue($consulta->presta_servicio);
        $this->assertTrue((bool) $alerta->decision_activa);
        $this->assertNull($alerta->decision_consumida_at);
        $this->assertNull($alerta->decision_consumida_consulta_id);
    }

    public function test_permitir_permanente_sobrepasa_bloqueo_manual(): void
    {
        $this->crearBloqueoManual('CC', '202');
        $this->crearDecisionActiva('CC', '202', 'permitir_permanente');

        $consulta = app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '202',
            'nombre' => null,
        ], '127.0.0.1', 'test');

        $this->assertTrue($consulta->presta_servicio);
        $this->assertSame('alto', $consulta->nivel_riesgo);
    }

    public function test_atender_alerta_actualiza_decision_y_sincroniza_bloqueo(): void
    {
        $alerta = Alerta::query()->create([
            'consulta_id' => null,
            'tipo' => 'coincidencia_lista',
            'nivel_riesgo' => 'alto',
            'estado' => 'pendiente',
            'tipo_documento' => 'CC',
            'numero_documento' => '300',
            'decision_servicio' => 'sin_decision',
            'decision_activa' => false,
            'datos_persona' => [
                'tipo_documento' => 'CC',
                'numero_documento' => '300',
                'nombre' => 'Persona Test',
            ],
            'listas_coincidentes' => [],
        ]);

        app(DecisionServicioService::class)->aplicarDecisionEnAtencion($alerta, [
            'estado' => 'atendida',
            'decision_servicio' => 'bloquear',
            'notas' => 'Decision oficial',
        ], 10);

        $alerta->refresh();

        $this->assertSame('atendida', $alerta->estado);
        $this->assertSame('bloquear', $alerta->decision_servicio);
        $this->assertTrue((bool) $alerta->decision_activa);

        $this->assertDatabaseHas('sarlaft_bloqueos', [
            'tipo_documento' => 'CC',
            'numero_documento' => '300',
            'tipo_bloqueo' => 'automatico',
            'estado' => 'bloqueado',
        ], 'mysql-sarlaft');
    }

    public function test_migracion_descartadas_a_permitir_una_operacion(): void
    {
        Schema::connection('mysql-sarlaft')->dropIfExists('sarlaft_alertas');
        Schema::connection('mysql-sarlaft')->create('sarlaft_alertas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('consulta_id')->nullable();
            $table->string('tipo', 50);
            $table->string('nivel_riesgo', 20);
            $table->string('estado', 20)->default('pendiente');
            $table->json('datos_persona');
            $table->json('listas_coincidentes');
            $table->json('contexto_operacion')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::connection('mysql-sarlaft')->table('sarlaft_alertas')->insert([
            'tipo' => 'coincidencia_lista',
            'nivel_riesgo' => 'alto',
            'estado' => 'descartada',
            'datos_persona' => json_encode([
                'tipo_documento' => 'CC',
                'numero_documento' => '999',
                'nombre' => 'Historico',
            ]),
            'listas_coincidentes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('app/Modules/Sarlaft/Database/Migrations/2026_02_19_232931_add_decision_servicio_fields_to_sarlaft_alertas_table.php');
        $migration->up();

        $fila = DB::connection('mysql-sarlaft')->table('sarlaft_alertas')->first();

        $this->assertSame('CC', $fila->tipo_documento);
        $this->assertSame('999', $fila->numero_documento);
        $this->assertSame('permitir_una_operacion', $fila->decision_servicio);
        $this->assertSame(1, (int) $fila->decision_activa);
        $this->assertNull($fila->decision_consumida_at);
    }

    private function crearEsquemaSarlaft(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_consultas', function (Blueprint $table): void {
            $table->id();
            $table->string('sistema_origen', 50);
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombre_consultado', 300)->nullable();
            $table->boolean('encontrado');
            $table->boolean('presta_servicio');
            $table->string('nivel_riesgo', 20)->nullable();
            $table->json('coincidencias')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_alertas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('consulta_id')->nullable();
            $table->string('tipo', 50);
            $table->string('nivel_riesgo', 20);
            $table->string('estado', 20)->default('pendiente');
            $table->string('tipo_documento', 20)->nullable();
            $table->string('numero_documento', 50)->nullable();
            $table->string('decision_servicio', 30)->default('sin_decision');
            $table->boolean('decision_activa')->default(false);
            $table->timestamp('decision_consumida_at')->nullable();
            $table->unsignedBigInteger('decision_consumida_consulta_id')->nullable();
            $table->json('datos_persona');
            $table->json('listas_coincidentes');
            $table->json('contexto_operacion')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_bloqueos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombre', 300)->nullable();
            $table->string('tipo_bloqueo', 20);
            $table->string('estado', 20);
            $table->text('motivo_bloqueo');
            $table->text('justificacion_desbloqueo')->nullable();
            $table->json('documentos_soporte')->nullable();
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_listas_vinculantes', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 255);
            $table->string('tipo', 20)->default('vinculante');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_registros_lista', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lista_id');
            $table->string('tipo_entidad', 30)->default('persona');
            $table->string('identificacion', 50)->nullable();
            $table->string('nombres', 500);
            $table->text('alias')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_lista_negra_interna', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 50);
            $table->string('nombres', 300)->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function crearDecisionActiva(string $tipoDocumento, string $numeroDocumento, string $decisionServicio): Alerta
    {
        return Alerta::query()->create([
            'consulta_id' => null,
            'tipo' => 'coincidencia_lista',
            'nivel_riesgo' => 'alto',
            'estado' => 'atendida',
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'decision_servicio' => $decisionServicio,
            'decision_activa' => true,
            'datos_persona' => [
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'nombre' => 'Persona de prueba',
            ],
            'listas_coincidentes' => [],
            'atendida_por' => 1,
            'fecha_atencion' => now(),
        ]);
    }

    private function crearBloqueoManual(string $tipoDocumento, string $numeroDocumento): Bloqueo
    {
        return Bloqueo::query()->create([
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'nombre' => 'Persona bloqueada',
            'tipo_bloqueo' => 'manual',
            'estado' => 'bloqueado',
            'motivo_bloqueo' => 'Bloqueo de prueba',
            'creado_por' => 1,
        ]);
    }
}
