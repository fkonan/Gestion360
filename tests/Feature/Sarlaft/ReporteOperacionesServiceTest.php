<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Consulta;
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
        $this->crearConsulta([
            'numero_documento' => '9001',
            'nombre_consultado' => 'Maria Operativa',
            'presta_servicio' => true,
            'sistema_origen' => 'api_transportes',
            'contexto_operacion' => [
                'tipo_operacion' => 'pasaje',
                'origen' => 'Bucaramanga',
                'destino' => 'Bogota',
            ],
            'created_at' => now()->subDays(2),
        ]);

        $consultaPago = $this->crearConsulta([
            'numero_documento' => '9001',
            'nombre_consultado' => 'Maria Operativa',
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'encontrado' => true,
            'sistema_origen' => 'api_finanzas',
            'contexto_operacion' => [
                'tipo_operacion' => 'pago',
                'presta_servicio_base' => false,
                'presta_servicio_final' => false,
                'monto' => 350000,
            ],
            'created_at' => now()->subDay(),
        ]);
        $this->crearAlerta($consultaPago, '9001');

        $this->crearConsulta([
            'numero_documento' => '7777',
            'nombre_consultado' => 'Otra Persona',
            'presta_servicio' => false,
            'sistema_origen' => 'api_transportes',
            'contexto_operacion' => [
                'tipo_operacion' => 'pasaje',
            ],
            'created_at' => now(),
        ]);

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
    }

    public function test_filtra_por_operacion_y_resultado(): void
    {
        $this->crearConsulta([
            'numero_documento' => '5000',
            'nombre_consultado' => 'Carlos Filtro',
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'sistema_origen' => 'api_transportes',
            'contexto_operacion' => [
                'tipo_operacion' => 'pasaje',
            ],
            'created_at' => now()->subHours(3),
        ]);

        $this->crearConsulta([
            'numero_documento' => '5000',
            'nombre_consultado' => 'Carlos Filtro',
            'presta_servicio' => true,
            'sistema_origen' => 'api_transportes',
            'contexto_operacion' => [
                'tipo_operacion' => 'pasaje',
            ],
            'created_at' => now()->subHours(2),
        ]);

        $this->crearConsulta([
            'numero_documento' => '5000',
            'nombre_consultado' => 'Carlos Filtro',
            'presta_servicio' => false,
            'nivel_riesgo' => 'alto',
            'sistema_origen' => 'api_finanzas',
            'contexto_operacion' => [
                'tipo_operacion' => 'pago',
            ],
            'created_at' => now()->subHour(),
        ]);

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
        $this->assertFalse($items[0]->presta_servicio);
        $this->assertSame('pasaje', $items[0]->contexto_operacion['tipo_operacion']);
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
            $table->unsignedBigInteger('intento_id')->nullable();
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
            $table->json('evidencias')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * @param  array<string, mixed>  $override
     */
    private function crearConsulta(array $override): Consulta
    {
        return Consulta::query()->create(array_merge([
            'sistema_origen' => 'api',
            'tipo_documento' => 'CC',
            'numero_documento' => '1000',
            'nombre_consultado' => 'Persona de prueba',
            'encontrado' => false,
            'presta_servicio' => true,
            'nivel_riesgo' => 'ninguno',
            'coincidencias' => [],
            'contexto_operacion' => [
                'tipo_operacion' => 'pasaje',
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
}
