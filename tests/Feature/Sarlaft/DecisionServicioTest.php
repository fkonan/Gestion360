<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\NovedadExportacion;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Services\DecisionServicioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DecisionServicioTest extends TestCase
{
    private DecisionServicioService $service;

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

        $this->service = app(DecisionServicioService::class);
    }

    public function test_permitir_servicio_vinculante_remueve_todos_los_registros_del_documento(): void
    {
        $this->crearRegistroVinculante('900111', 'activo');
        $this->crearRegistroVinculante('900111', 'activo');   // misma persona, otra lista
        $this->crearRegistroVinculante('900999', 'activo');   // otra persona

        $alerta = $this->crearAlerta('900111', 'vinculante');

        $afectados = $this->service->permitirServicio($alerta, 'Validado', [], 7);

        $this->assertSame(2, $afectados);
        $this->assertSame(0, RegistroLista::where('identificacion', '900111')->where('estado', 'activo')->count());
        $this->assertSame(2, RegistroLista::where('identificacion', '900111')->where('estado', 'removido')->count());
        // Otra persona intacta.
        $this->assertSame(1, RegistroLista::where('identificacion', '900999')->where('estado', 'activo')->count());
        // La alerta queda atendida.
        $this->assertSame('atendida', $alerta->fresh()->estado);
    }

    public function test_permitir_servicio_restrictiva_inactiva_y_documenta_el_retiro(): void
    {
        $this->crearRegistroInterno('800222', 'activo');

        $alerta = $this->crearAlerta('800222', 'restrictiva');

        $afectados = $this->service->permitirServicio($alerta, 'Cliente verificado', [], 9);

        $this->assertSame(1, $afectados);

        $registro = ListaNegraInterna::where('numero_documento', '800222')->first();
        $this->assertSame('inactivo', $registro->estado);
        $this->assertSame('Cliente verificado', $registro->motivo_retiro);
        $this->assertSame(9, (int) $registro->retirado_por);
        $this->assertNotNull($registro->retirado_at);
    }

    public function test_permitir_servicio_genera_novedad_de_salida_para_exportacion(): void
    {
        $this->crearRegistroVinculante('900111', 'activo');

        $alerta = $this->crearAlerta('900111', 'vinculante');

        app(DecisionServicioService::class)->permitirServicio($alerta, 'Validado', [], 7);

        // Debe existir la novedad de salida para que el modo "novedades" propague el delta.
        $this->assertSame(1, NovedadExportacion::where('tipo_novedad', 'salida')->where('origen_lista', 'vinculante')->count());
    }

    public function test_permitir_servicio_marca_la_decision_en_la_alerta(): void
    {
        $this->crearRegistroVinculante('900111', 'activo');
        $alerta = $this->crearAlerta('900111', 'vinculante');

        app(DecisionServicioService::class)->permitirServicio($alerta, 'Validado', [], 7);

        $alerta->refresh();
        $this->assertSame('permitido', $alerta->decision_servicio);
        $this->assertNotNull($alerta->decision_at);
    }

    public function test_mantener_bloqueo_registra_decision_sin_tocar_listas(): void
    {
        $this->crearRegistroVinculante('900111', 'activo');
        $alerta = $this->crearAlerta('900111', 'vinculante');

        app(DecisionServicioService::class)->mantenerBloqueo($alerta, 'Riesgo confirmado', [], 7);

        $alerta->refresh();
        $this->assertSame('bloqueado', $alerta->decision_servicio);
        $this->assertNotNull($alerta->decision_at);
        // No se toca la lista: el registro sigue activo.
        $this->assertSame(1, RegistroLista::where('identificacion', '900111')->where('estado', 'activo')->count());
    }

    public function test_no_afecta_registros_ya_removidos(): void
    {
        $this->crearRegistroVinculante('900111', 'removido');

        $alerta = $this->crearAlerta('900111', 'vinculante');

        $afectados = $this->service->permitirServicio($alerta, 'Validado', [], 7);

        $this->assertSame(0, $afectados);
    }

    private function crearAlerta(string $documento, string $nivel): Alerta
    {
        return Alerta::query()->create([
            'numero_documento' => $documento,
            'tipo_documento' => 'CC',
            'nivel_riesgo' => $nivel,
            'estado' => 'pendiente',
            'tipo' => 'intento_operacion_db',
        ]);
    }

    private function crearRegistroVinculante(string $documento, string $estado): RegistroLista
    {
        return RegistroLista::query()->create([
            'lista_id' => 1,
            'identificacion' => $documento,
            'nombres' => 'Persona '.$documento,
            'estado' => $estado,
        ]);
    }

    private function crearRegistroInterno(string $documento, string $estado): ListaNegraInterna
    {
        return ListaNegraInterna::query()->create([
            'tipo_entidad' => 'persona',
            'tipo_documento' => 'CC',
            'numero_documento' => $documento,
            'nombres' => 'Persona '.$documento,
            'motivo' => 'Inclusion de prueba',
            'estado' => $estado,
        ]);
    }

    private function crearEsquema(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_alertas', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('intento_id')->nullable();
            $table->string('tipo')->nullable();
            $table->string('nivel_riesgo', 20)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('decision_servicio', 20)->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            $table->text('datos_persona')->nullable();
            $table->text('listas_coincidentes')->nullable();
            $table->text('contexto_operacion')->nullable();
            $table->boolean('escalada_automatica')->default(false);
            $table->timestamp('escalada_automatica_at')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('notas')->nullable();
            $table->text('evidencias')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_registros_lista', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lista_id')->nullable();
            $table->string('identificacion')->nullable();
            $table->string('nombres')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_novedades_exportacion', static function (Blueprint $table): void {
            $table->id();
            $table->string('origen_lista', 20);
            $table->string('tipo_novedad', 20);
            $table->unsignedBigInteger('registro_lista_id')->nullable();
            $table->unsignedBigInteger('lista_negra_id')->nullable();
            $table->unsignedBigInteger('sincronizacion_log_id')->nullable();
            $table->unsignedBigInteger('lista_id')->nullable();
            $table->string('nombre_lista', 150)->nullable();
            $table->text('datos')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_lista_negra_interna', static function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_entidad', 20)->default('persona');
            $table->string('tipo_documento', 20)->nullable();
            $table->string('numero_documento', 50)->nullable();
            $table->string('nombres', 500)->nullable();
            $table->text('motivo')->nullable();
            $table->text('evidencia_inclusion')->nullable();
            $table->text('motivo_retiro')->nullable();
            $table->text('evidencia_retiro')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('retirado_por')->nullable();
            $table->timestamp('retirado_at')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
