<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Bloqueo;
use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Services\AlertaEvidenciaService;
use App\Modules\Sarlaft\Services\ConsultaService;
use App\Modules\Sarlaft\Services\DecisionServicioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'sarlaft.suppress_alert_on_bloquear' => true,
            'sarlaft.suppress_alert_on_permitir_permanente' => true,
            'sarlaft.auto_atender_lista_negra_interna' => true,
            'sarlaft.auto_crear_alerta_atendida' => true,
            'sarlaft.auto_user_id' => 1,
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

    public function test_permitir_permanente_no_genera_nueva_alerta_repetida(): void
    {
        $this->crearCoincidenciaPorDocumento('555', 'Persona Coincidente');

        $this->crearDecisionActiva('CC', '555', 'permitir_permanente');

        $alertasAntes = Alerta::query()->count();
        $consulta = app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '555',
            'nombre' => null,
        ], '127.0.0.1', 'test');
        $alertasDespues = Alerta::query()->count();

        $this->assertTrue($consulta->encontrado);
        $this->assertTrue($consulta->presta_servicio);
        $this->assertSame($alertasAntes, $alertasDespues);
    }

    public function test_decision_bloquear_no_genera_nueva_alerta_repetida(): void
    {
        $this->crearCoincidenciaPorDocumento('556', 'Persona Bloqueada');
        $this->crearDecisionActiva('CC', '556', 'bloquear');

        $alertasAntes = Alerta::query()->count();
        $consulta = app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '556',
            'nombre' => null,
        ], '127.0.0.1', 'test');
        $alertasDespues = Alerta::query()->count();

        $this->assertTrue($consulta->encontrado);
        $this->assertFalse($consulta->presta_servicio);
        $this->assertSame($alertasAntes, $alertasDespues);
    }

    public function test_autoatencion_lista_negra_interna_crea_alerta_atendida_y_bloqueo(): void
    {
        DB::connection('mysql-sarlaft')->table('sarlaft_lista_negra_interna')->insert([
            'tipo_documento' => 'CC',
            'numero_documento' => '777',
            'nombres' => 'Persona Lista Negra',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consulta = app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '777',
            'nombre' => 'Persona Lista Negra',
        ], '127.0.0.1', 'test');

        $alerta = Alerta::query()->latest('id')->first();
        $this->assertNotNull($alerta);
        $this->assertSame((int) $consulta->id, (int) $alerta->consulta_id);
        $this->assertSame('atendida', $alerta->estado);
        $this->assertSame('bloquear', $alerta->decision_servicio);
        $this->assertTrue((bool) $alerta->decision_activa);
        $this->assertTrue((bool) $alerta->escalada_automatica);
        $this->assertSame('auto_lista_negra_interna', $alerta->contexto_operacion['origen_atencion'] ?? null);
        $this->assertDatabaseCount('sarlaft_bloqueos', 1, 'mysql-sarlaft');
    }

    public function test_autoatencion_lista_negra_interna_deja_decision_activa_bloquear(): void
    {
        DB::connection('mysql-sarlaft')->table('sarlaft_lista_negra_interna')->insert([
            'tipo_documento' => 'CC',
            'numero_documento' => '778',
            'nombres' => 'Persona Negra 2',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '778',
            'nombre' => 'Persona Negra 2',
        ], '127.0.0.1', 'test');

        $decisionActiva = app(DecisionServicioService::class)->resolverDecisionActiva('CC', '778');

        $this->assertNotNull($decisionActiva);
        $this->assertSame('bloquear', $decisionActiva->decision_servicio);
        $this->assertTrue((bool) $decisionActiva->decision_activa);
    }

    public function test_politica_desde_bd_sobrescribe_config(): void
    {
        config(['sarlaft.suppress_alert_on_bloquear' => false]);
        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')
            ->where('id', 1)
            ->update([
                'suppress_alert_on_bloquear' => 1,
                'updated_at' => now(),
            ]);

        $this->crearCoincidenciaPorDocumento('559', 'Persona Politica BD');
        $this->crearDecisionActiva('CC', '559', 'bloquear');

        $alertasAntes = Alerta::query()->count();
        app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '559',
            'nombre' => null,
        ], '127.0.0.1', 'test');
        $alertasDespues = Alerta::query()->count();

        $this->assertSame($alertasAntes, $alertasDespues);
    }

    public function test_fallback_config_si_no_hay_politica(): void
    {
        config(['sarlaft.suppress_alert_on_bloquear' => false]);
        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')->delete();

        $this->crearCoincidenciaPorDocumento('560', 'Persona Fallback Config');
        $this->crearDecisionActiva('CC', '560', 'bloquear');

        $alertasAntes = Alerta::query()->count();
        app(ConsultaService::class)->ejecutar([
            'tipo_documento' => 'CC',
            'numero_documento' => '560',
            'nombre' => null,
        ], '127.0.0.1', 'test');
        $alertasDespues = Alerta::query()->count();

        $this->assertSame($alertasAntes + 1, $alertasDespues);
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

    public function test_atender_alerta_acumula_evidencias_adjuntas(): void
    {
        Storage::fake('local');

        $alerta = Alerta::query()->create([
            'consulta_id' => null,
            'tipo' => 'coincidencia_lista',
            'nivel_riesgo' => 'alto',
            'estado' => 'pendiente',
            'tipo_documento' => 'CC',
            'numero_documento' => '301',
            'decision_servicio' => 'sin_decision',
            'decision_activa' => false,
            'datos_persona' => [
                'tipo_documento' => 'CC',
                'numero_documento' => '301',
                'nombre' => 'Persona Evidencia',
            ],
            'listas_coincidentes' => [],
        ]);

        $servicioEvidencia = app(AlertaEvidenciaService::class);

        $primerLote = $servicioEvidencia->guardarArchivos($alerta, [
            UploadedFile::fake()->create('soporte.pdf', 120, 'application/pdf'),
        ], 10);

        $this->assertCount(1, $primerLote);
        Storage::disk('local')->assertExists($primerLote[0]['path']);

        app(DecisionServicioService::class)->aplicarDecisionEnAtencion($alerta, [
            'estado' => 'en_revision',
            'decision_servicio' => 'sin_decision',
            'notas' => 'Primer soporte',
            'evidencias' => $primerLote,
        ], 10);

        $segundoLote = $servicioEvidencia->guardarArchivos($alerta, [
            UploadedFile::fake()->image('evidencia.png'),
        ], 11);

        $this->assertCount(1, $segundoLote);
        Storage::disk('local')->assertExists($segundoLote[0]['path']);

        app(DecisionServicioService::class)->aplicarDecisionEnAtencion($alerta, [
            'estado' => 'atendida',
            'decision_servicio' => 'bloquear',
            'notas' => 'Segundo soporte',
            'evidencias' => $segundoLote,
        ], 11);

        $alerta->refresh();

        $this->assertSame('atendida', $alerta->estado);
        $this->assertSame('bloquear', $alerta->decision_servicio);
        $this->assertCount(2, $alerta->evidencias ?? []);
        $this->assertSame('soporte.pdf', $alerta->evidencias[0]['original_name']);
        $this->assertSame('evidencia.png', $alerta->evidencias[1]['original_name']);
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
            $table->json('evidencias')->nullable();
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
            $table->json('contexto_operacion')->nullable();
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

        Schema::connection('mysql-sarlaft')->create('sarlaft_politicas', function (Blueprint $table): void {
            $table->id();
            $table->boolean('suppress_alert_on_bloquear')->default(true);
            $table->boolean('suppress_alert_on_permitir_permanente')->default(true);
            $table->unsignedTinyInteger('sla_dias_alerta')->default(1);
            $table->json('auto_escalar_riesgos')->nullable();
            $table->string('auto_estado', 20)->default('en_revision');
            $table->string('auto_decision', 30)->default('bloquear');
            $table->boolean('auto_atender_lista_negra_interna')->default(true);
            $table->boolean('auto_crear_alerta_atendida')->default(true);
            $table->unsignedBigInteger('auto_user_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')->insert([
            'id' => 1,
            'suppress_alert_on_bloquear' => 1,
            'suppress_alert_on_permitir_permanente' => 1,
            'sla_dias_alerta' => 1,
            'auto_escalar_riesgos' => json_encode(['alto', 'critico']),
            'auto_estado' => 'en_revision',
            'auto_decision' => 'bloquear',
            'auto_atender_lista_negra_interna' => 1,
            'auto_crear_alerta_atendida' => 1,
            'auto_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function crearCoincidenciaPorDocumento(string $identificacion, string $nombre): void
    {
        $lista = ListaVinculante::query()->create([
            'nombre' => 'OFAC',
            'tipo' => 'vinculante',
        ]);

        RegistroLista::query()->create([
            'lista_id' => $lista->id,
            'tipo_entidad' => 'persona',
            'identificacion' => $identificacion,
            'nombres' => $nombre,
            'estado' => 'activo',
        ]);
    }
}
