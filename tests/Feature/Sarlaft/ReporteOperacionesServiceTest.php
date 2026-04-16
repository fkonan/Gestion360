<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Models\SimulacionPasaje;
use App\Modules\Sarlaft\Models\SimulacionRemesa;
use App\Modules\Sarlaft\Services\ReporteOperacionesService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReporteOperacionesServiceTest extends TestCase
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

        $this->crearEsquema();
    }

    public function test_construye_metricas_operativas_por_documento(): void
    {
        $consultaPasaje = $this->crearConsulta([
            'numero_documento' => '9001',
            'nombre_consultado' => 'Maria Operativa',
            'presta_servicio' => true,
            'sistema_origen' => 'simulacion_pasaje',
            'created_at' => now()->subDays(2),
        ]);
        $this->crearSimulacionPasaje($consultaPasaje);

        $consultaRemesa = $this->crearConsulta([
            'numero_documento' => '9001',
            'nombre_consultado' => 'Maria Operativa',
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'encontrado' => true,
            'sistema_origen' => 'simulacion_remesa',
            'contexto_operacion' => [
                'decision_aplicada' => 'bloquear',
                'presta_servicio_base' => false,
                'presta_servicio_final' => false,
            ],
            'created_at' => now()->subDay(),
        ]);
        $this->crearSimulacionRemesa($consultaRemesa);
        $this->crearAlerta($consultaRemesa, '9001');

        $consultaTercera = $this->crearConsulta([
            'numero_documento' => '7777',
            'nombre_consultado' => 'Otra Persona',
            'presta_servicio' => false,
            'sistema_origen' => 'simulacion_pasaje',
            'created_at' => now(),
        ]);
        $this->crearSimulacionPasaje($consultaTercera, '7777');

        $reporte = app(ReporteOperacionesService::class)->construirReporte([
            'tipo_documento' => 'cc',
            'numero_documento' => '9001',
        ]);

        $this->assertSame(2, $reporte['stats']['total_operaciones']);
        $this->assertSame(1, $reporte['stats']['total_permitidas']);
        $this->assertSame(1, $reporte['stats']['total_bloqueadas']);
        $this->assertSame(1, $reporte['stats']['total_pasajes']);
        $this->assertSame(1, $reporte['stats']['total_remesas']);
        $this->assertSame(1, $reporte['stats']['con_alerta']);
        $this->assertSame('CC', $reporte['filtros']['tipo_documento']);

        $items = $reporte['operaciones']->items();

        $this->assertCount(2, $items);
        $this->assertSame('9001', $items[0]->numero_documento);
        $this->assertTrue($items[0]->relationLoaded('alertas'));
        $this->assertTrue($items[0]->relationLoaded('simulacionPasaje'));
        $this->assertTrue($items[0]->relationLoaded('simulacionRemesa'));
    }

    public function test_filtra_por_operacion_y_resultado(): void
    {
        $consultaPasajeBloqueado = $this->crearConsulta([
            'numero_documento' => '5000',
            'nombre_consultado' => 'Carlos Filtro',
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'sistema_origen' => 'simulacion_pasaje',
            'created_at' => now()->subHours(3),
        ]);
        $this->crearSimulacionPasaje($consultaPasajeBloqueado, '5000');

        $consultaPasajePermitido = $this->crearConsulta([
            'numero_documento' => '5000',
            'nombre_consultado' => 'Carlos Filtro',
            'presta_servicio' => true,
            'sistema_origen' => 'simulacion_pasaje',
            'created_at' => now()->subHours(2),
        ]);
        $this->crearSimulacionPasaje($consultaPasajePermitido, '5000');

        $consultaRemesaBloqueada = $this->crearConsulta([
            'numero_documento' => '5000',
            'nombre_consultado' => 'Carlos Filtro',
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'sistema_origen' => 'simulacion_remesa',
            'created_at' => now()->subHour(),
        ]);
        $this->crearSimulacionRemesa($consultaRemesaBloqueada, '5000');

        $reporte = app(ReporteOperacionesService::class)->construirReporte([
            'numero_documento' => '5000',
            'operacion' => 'pasaje',
            'resultado' => 'bloqueada',
        ]);

        $items = $reporte['operaciones']->items();

        $this->assertSame(1, $reporte['stats']['total_operaciones']);
        $this->assertSame(0, $reporte['stats']['total_permitidas']);
        $this->assertSame(1, $reporte['stats']['total_bloqueadas']);
        $this->assertSame(1, $reporte['stats']['total_pasajes']);
        $this->assertSame(0, $reporte['stats']['total_remesas']);
        $this->assertCount(1, $items);
        $this->assertSame('simulacion_pasaje', $items[0]->sistema_origen);
        $this->assertFalse($items[0]->presta_servicio);
    }

    private function crearEsquema(): void
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
            $table->json('evidencias')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_simulacion_pasajes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('consulta_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('ciudad_origen_id');
            $table->string('ciudad_origen_nombre', 150);
            $table->unsignedBigInteger('ciudad_destino_id');
            $table->string('ciudad_destino_nombre', 150);
            $table->date('fecha_viaje');
            $table->string('tipo_documento', 20);
            $table->string('documento', 50);
            $table->string('nombres', 150);
            $table->string('apellidos', 150);
            $table->string('direccion', 250);
            $table->string('telefono', 30);
            $table->string('correo', 150);
            $table->boolean('encontrado');
            $table->boolean('presta_servicio');
            $table->string('nivel_riesgo', 20)->nullable();
            $table->json('coincidencias')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_simulacion_remesas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('consulta_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('ciudad_origen_id');
            $table->string('ciudad_origen_nombre', 150);
            $table->unsignedBigInteger('ciudad_destino_id');
            $table->string('ciudad_destino_nombre', 150);
            $table->date('fecha_envio');
            $table->string('tipo_documento', 20);
            $table->string('documento_remitente', 50);
            $table->string('nombres_remitente', 150);
            $table->string('apellidos_remitente', 150);
            $table->string('telefono_remitente', 30);
            $table->string('nombre_destinatario', 300);
            $table->string('documento_destinatario', 50);
            $table->decimal('monto', 14, 2);
            $table->string('concepto', 500);
            $table->boolean('encontrado');
            $table->boolean('presta_servicio');
            $table->string('nivel_riesgo', 20)->nullable();
            $table->json('coincidencias')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, mixed>  $override
     */
    private function crearConsulta(array $override): Consulta
    {
        return Consulta::query()->create(array_merge([
            'sistema_origen' => 'simulacion_pasaje',
            'tipo_documento' => 'CC',
            'numero_documento' => '1000',
            'nombre_consultado' => 'Persona de prueba',
            'encontrado' => false,
            'presta_servicio' => true,
            'nivel_riesgo' => 'ninguno',
            'coincidencias' => [],
            'contexto_operacion' => [
                'decision_aplicada' => 'sin_decision',
                'presta_servicio_base' => true,
                'presta_servicio_final' => true,
            ],
            'ip_origen' => '127.0.0.1',
            'created_at' => now(),
        ], $override));
    }

    private function crearAlerta(Consulta $consulta, string $numeroDocumento): Alerta
    {
        return Alerta::query()->create([
            'consulta_id' => $consulta->id,
            'tipo' => 'coincidencia_lista',
            'nivel_riesgo' => 'alto',
            'estado' => 'pendiente',
            'tipo_documento' => 'CC',
            'numero_documento' => $numeroDocumento,
            'decision_servicio' => 'sin_decision',
            'decision_activa' => false,
            'datos_persona' => [
                'tipo_documento' => 'CC',
                'numero_documento' => $numeroDocumento,
                'nombre' => $consulta->nombre_consultado,
            ],
            'listas_coincidentes' => [],
            'created_at' => $consulta->created_at,
            'updated_at' => $consulta->created_at,
        ]);
    }

    private function crearSimulacionPasaje(Consulta $consulta, string $documento = '9001'): SimulacionPasaje
    {
        return SimulacionPasaje::query()->create([
            'consulta_id' => $consulta->id,
            'usuario_id' => null,
            'ciudad_origen_id' => 1,
            'ciudad_origen_nombre' => 'Bucaramanga',
            'ciudad_destino_id' => 2,
            'ciudad_destino_nombre' => 'Bogota',
            'fecha_viaje' => now()->toDateString(),
            'tipo_documento' => 'CC',
            'documento' => $documento,
            'nombres' => 'Maria',
            'apellidos' => 'Operativa',
            'direccion' => 'Calle 1',
            'telefono' => '3000000000',
            'correo' => 'maria@example.com',
            'encontrado' => $consulta->encontrado,
            'presta_servicio' => $consulta->presta_servicio,
            'nivel_riesgo' => $consulta->nivel_riesgo,
            'coincidencias' => [],
        ]);
    }

    private function crearSimulacionRemesa(Consulta $consulta, string $documento = '9001'): SimulacionRemesa
    {
        return SimulacionRemesa::query()->create([
            'consulta_id' => $consulta->id,
            'usuario_id' => null,
            'ciudad_origen_id' => 1,
            'ciudad_origen_nombre' => 'Bucaramanga',
            'ciudad_destino_id' => 3,
            'ciudad_destino_nombre' => 'Medellin',
            'fecha_envio' => now()->toDateString(),
            'tipo_documento' => 'CC',
            'documento_remitente' => $documento,
            'nombres_remitente' => 'Maria',
            'apellidos_remitente' => 'Operativa',
            'telefono_remitente' => '3000000000',
            'nombre_destinatario' => 'Destinatario Prueba',
            'documento_destinatario' => '12345',
            'monto' => 250000,
            'concepto' => 'Pago de prueba',
            'encontrado' => $consulta->encontrado,
            'presta_servicio' => $consulta->presta_servicio,
            'nivel_riesgo' => $consulta->nivel_riesgo,
            'coincidencias' => [],
        ]);
    }
}
