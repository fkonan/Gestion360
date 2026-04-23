<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Bloqueo;
use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Services\AlertaEscalationService;
use App\Modules\Sarlaft\Services\ConsultaArchiveService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RetentionAndEscalationTest extends TestCase
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
            'sarlaft.retencion_negativas_dias' => 180,
            'sarlaft.alerta_sla_dias' => 1,
            'sarlaft.auto_escalar_riesgos' => ['alto', 'critico'],
            'sarlaft.auto_estado' => 'en_revision',
            'sarlaft.auto_user_id' => 1,
            'sarlaft.archive_chunk' => 100,
        ]);

        DB::purge('mysql-sarlaft');
        DB::connection('mysql-sarlaft')->getPdo();

        $this->crearEsquemaSarlaft();
    }

    public function test_archiva_solo_negativas_mayores_180_dias(): void
    {
        $consultaArchivable = $this->crearConsulta([
            'created_at' => now()->subDays(190),
        ]);

        $consultaConAlerta = $this->crearConsulta([
            'numero_documento' => '200',
            'created_at' => now()->subDays(190),
        ]);
        $this->crearAlertaPendiente($consultaConAlerta, 'alto', 'CC', '200');

        $this->crearConsulta([
            'numero_documento' => '300',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(190),
        ]);

        $this->crearConsulta([
            'numero_documento' => '400',
            'created_at' => now()->subDays(10),
        ]);

        $stats = app(ConsultaArchiveService::class)->archivarNegativas(false, 100);

        $this->assertSame(1, $stats['procesadas']);
        $this->assertDatabaseHas('sarlaft_consultas_archivo', [
            'consulta_id_original' => $consultaArchivable->id,
            'motivo_archivo' => 'retencion_negativa_180d',
        ], 'mysql-sarlaft');
        $this->assertDatabaseMissing('sarlaft_consultas', [
            'id' => $consultaArchivable->id,
        ], 'mysql-sarlaft');
        $this->assertDatabaseHas('sarlaft_consultas', [
            'id' => $consultaConAlerta->id,
        ], 'mysql-sarlaft');
    }

    public function test_no_archiva_en_dry_run(): void
    {
        $consulta = $this->crearConsulta([
            'created_at' => now()->subDays(200),
        ]);

        $stats = app(ConsultaArchiveService::class)->archivarNegativas(true, 100);

        $this->assertTrue($stats['dry_run']);
        $this->assertSame(0, $stats['procesadas']);
        $this->assertDatabaseHas('sarlaft_consultas', [
            'id' => $consulta->id,
        ], 'mysql-sarlaft');
        $this->assertDatabaseMissing('sarlaft_consultas_archivo', [
            'consulta_id_original' => $consulta->id,
        ], 'mysql-sarlaft');
    }

    public function test_archivado_es_idempotente(): void
    {
        $consulta = $this->crearConsulta([
            'created_at' => now()->subDays(181),
        ]);

        app(ConsultaArchiveService::class)->archivarNegativas(false, 100);
        $statsSegunda = app(ConsultaArchiveService::class)->archivarNegativas(false, 100);

        $this->assertSame(0, $statsSegunda['procesadas']);
        $this->assertSame(1, DB::connection('mysql-sarlaft')
            ->table('sarlaft_consultas_archivo')
            ->where('consulta_id_original', $consulta->id)
            ->count());
    }

    public function test_escalado_automatico_aplica_en_alto_y_critico(): void
    {
        $consultaAlto = $this->crearConsulta([
            'numero_documento' => '500',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(10),
        ]);
        $consultaCritico = $this->crearConsulta([
            'numero_documento' => '600',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(10),
        ]);

        $alertaAlto = $this->crearAlertaPendiente($consultaAlto, 'alto', 'CC', '500', now()->subDays(6));
        $alertaCritico = $this->crearAlertaPendiente($consultaCritico, 'critico', 'CC', '600', now()->subDays(7));

        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);

        $alertaAlto->refresh();
        $alertaCritico->refresh();

        $this->assertSame(2, $stats['procesadas']);
        $this->assertSame('en_revision', $alertaAlto->estado);
        $this->assertTrue((bool) $alertaAlto->escalada_automatica);
        $this->assertNotNull($alertaAlto->escalada_automatica_at);

        $this->assertSame('en_revision', $alertaCritico->estado);
        $this->assertTrue((bool) $alertaCritico->escalada_automatica);

        $this->assertDatabaseCount('sarlaft_bloqueos', 0, 'mysql-sarlaft');
    }

    public function test_no_escalado_en_bajo_y_medio(): void
    {
        $consultaBajo = $this->crearConsulta([
            'numero_documento' => '700',
            'created_at' => now()->subDays(10),
        ]);
        $consultaMedio = $this->crearConsulta([
            'numero_documento' => '701',
            'created_at' => now()->subDays(10),
        ]);

        $alertaBaja = $this->crearAlertaPendiente($consultaBajo, 'bajo', 'CC', '700', now()->subDays(10));
        $alertaMedia = $this->crearAlertaPendiente($consultaMedio, 'medio', 'CC', '701', now()->subDays(10));

        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);

        $alertaBaja->refresh();
        $alertaMedia->refresh();

        $this->assertSame(0, $stats['procesadas']);
        $this->assertSame('pendiente', $alertaBaja->estado);
        $this->assertSame('pendiente', $alertaMedia->estado);
        $this->assertDatabaseCount('sarlaft_bloqueos', 0, 'mysql-sarlaft');
    }

    public function test_sla_no_escalada_antes_del_corte(): void
    {
        $consulta = $this->crearConsulta([
            'numero_documento' => '705',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subHours(20),
        ]);

        $alerta = $this->crearAlertaPendiente($consulta, 'alto', 'CC', '705', now()->subHours(20));

        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);
        $alerta->refresh();

        $this->assertSame(0, $stats['procesadas']);
        $this->assertSame('pendiente', $alerta->estado);
        $this->assertFalse((bool) $alerta->escalada_automatica);
    }

    public function test_no_reprocesa_alerta_ya_escalada_automaticamente(): void
    {
        $consulta = $this->crearConsulta([
            'numero_documento' => '800',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(15),
        ]);

        $alerta = $this->crearAlertaPendiente($consulta, 'alto', 'CC', '800', now()->subDays(10), [
            'escalada_automatica' => true,
            'escalada_automatica_at' => now()->subDays(9),
        ]);

        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);

        $alerta->refresh();
        $this->assertSame(0, $stats['procesadas']);
        $this->assertSame('pendiente', $alerta->estado);
        $this->assertDatabaseCount('sarlaft_bloqueos', 0, 'mysql-sarlaft');
    }

    public function test_escalado_reutiliza_bloqueo_existente(): void
    {
        $consulta = $this->crearConsulta([
            'numero_documento' => '900',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(10),
        ]);

        $this->crearBloqueoManual('CC', '900');
        $alerta = $this->crearAlertaPendiente($consulta, 'alto', 'CC', '900', now()->subDays(8));

        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);
        $alerta->refresh();

        $this->assertSame(1, $stats['procesadas']);
        $this->assertSame('en_revision', $alerta->estado);
        $this->assertSame(1, DB::connection('mysql-sarlaft')->table('sarlaft_bloqueos')->count());
    }

    public function test_politica_desde_bd_sobrescribe_config(): void
    {
        config([
            'sarlaft.auto_estado' => 'descartada',
        ]);

        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')
            ->where('id', 1)
            ->update([
                'auto_estado' => 'en_revision',
                'updated_at' => now(),
            ]);

        $consulta = $this->crearConsulta([
            'numero_documento' => '950',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(2),
        ]);

        $alerta = $this->crearAlertaPendiente($consulta, 'alto', 'CC', '950', now()->subDays(2));
        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);
        $alerta->refresh();

        $this->assertSame(1, $stats['procesadas']);
        $this->assertSame('en_revision', $alerta->estado);
    }

    public function test_fallback_config_si_no_hay_politica(): void
    {
        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')->delete();
        config([
            'sarlaft.alerta_sla_dias' => 1,
            'sarlaft.auto_estado' => 'descartada',
        ]);

        $consulta = $this->crearConsulta([
            'numero_documento' => '951',
            'encontrado' => true,
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'created_at' => now()->subDays(2),
        ]);

        $alerta = $this->crearAlertaPendiente($consulta, 'alto', 'CC', '951', now()->subDays(2));
        $stats = app(AlertaEscalationService::class)->procesarVencidas(false, 100);
        $alerta->refresh();

        $this->assertSame(1, $stats['procesadas']);
        $this->assertSame('descartada', $alerta->estado);
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
            $table->boolean('escalada_automatica')->default(false);
            $table->timestamp('escalada_automatica_at')->nullable();
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

        Schema::connection('mysql-sarlaft')->create('sarlaft_consultas_archivo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('consulta_id_original')->unique();
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
            $table->timestamp('archived_at')->nullable();
            $table->string('motivo_archivo', 120)->nullable();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_mantenimiento_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('proceso', 100);
            $table->string('estado', 20)->default('exitoso');
            $table->unsignedInteger('registros_evaluados')->default(0);
            $table->unsignedInteger('registros_procesados')->default(0);
            $table->unsignedInteger('registros_omitidos')->default(0);
            $table->integer('duracion_segundos')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->json('detalles')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_politicas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('sla_dias_alerta')->default(1);
            $table->json('auto_escalar_riesgos')->nullable();
            $table->string('auto_estado', 20)->default('en_revision');
            $table->boolean('auto_atender_lista_negra_interna')->default(true);
            $table->boolean('auto_crear_alerta_atendida')->default(true);
            $table->unsignedBigInteger('auto_user_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::connection('mysql-sarlaft')->table('sarlaft_politicas')->insert([
            'id' => 1,
            'sla_dias_alerta' => 1,
            'auto_escalar_riesgos' => json_encode(['alto', 'critico']),
            'auto_estado' => 'en_revision',
            'auto_atender_lista_negra_interna' => 1,
            'auto_crear_alerta_atendida' => 1,
            'auto_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearConsulta(array $overrides = []): Consulta
    {
        return Consulta::query()->create(array_merge([
            'sistema_origen' => 'test',
            'tipo_documento' => 'CC',
            'numero_documento' => '100',
            'nombre_consultado' => 'Persona de prueba',
            'encontrado' => false,
            'presta_servicio' => true,
            'nivel_riesgo' => 'ninguno',
            'coincidencias' => null,
            'ip_origen' => '127.0.0.1',
            'created_at' => now()->subDays(181),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearAlertaPendiente(
        Consulta $consulta,
        string $nivelRiesgo,
        string $tipoDocumento,
        string $numeroDocumento,
        \Illuminate\Support\Carbon $createdAt,
        array $overrides = [],
    ): Alerta {
        return Alerta::query()->create(array_merge([
            'consulta_id' => $consulta->id,
            'tipo' => 'coincidencia_lista',
            'nivel_riesgo' => $nivelRiesgo,
            'estado' => 'pendiente',
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'datos_persona' => [
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'nombre' => 'Persona de riesgo',
            ],
            'listas_coincidentes' => [],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ], $overrides));
    }

    private function crearBloqueoManual(string $tipoDocumento, string $numeroDocumento): Bloqueo
    {
        return Bloqueo::query()->create([
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'nombre' => 'Bloqueado previo',
            'tipo_bloqueo' => 'manual',
            'estado' => 'bloqueado',
            'motivo_bloqueo' => 'Bloqueo manual existente',
            'creado_por' => 1,
        ]);
    }
}
