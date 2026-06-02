<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Services\GestionAlertaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CierreMasivoAlertasTest extends TestCase
{
    private GestionAlertaService $service;

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
        $this->crearEsquemaAlertas();

        $this->service = app(GestionAlertaService::class);
    }

    public function test_cuenta_relacionadas_abiertas_del_mismo_documento(): void
    {
        $this->crearAlerta('111', 'pendiente');
        $this->crearAlerta('111', 'en_revision');
        $this->crearAlerta('111', 'atendida');   // cerrada, no cuenta
        $alerta = $this->crearAlerta('111', 'pendiente');
        $this->crearAlerta('222', 'pendiente');   // otro documento

        $this->assertSame(3, $this->service->contarRelacionadasAbiertas($alerta));
    }

    public function test_cierre_masivo_afecta_solo_abiertas_del_mismo_documento(): void
    {
        $a1 = $this->crearAlerta('111', 'pendiente');
        $this->crearAlerta('111', 'en_revision');
        $cerrada = $this->crearAlerta('111', 'atendida');
        $otro = $this->crearAlerta('222', 'pendiente');

        $afectadas = $this->service->atenderRelacionadas(
            $a1,
            ['estado' => 'descartada', 'notas' => 'Caso repetido'],
            7,
        );

        // Solo las 2 abiertas del doc 111.
        $this->assertSame(2, $afectadas);
        $this->assertSame(2, Alerta::where('numero_documento', '111')->where('estado', 'descartada')->count());

        // La ya atendida del 111 no se toca.
        $this->assertSame('atendida', $cerrada->fresh()->estado);

        // El otro documento queda intacto.
        $this->assertSame('pendiente', $otro->fresh()->estado);
    }

    public function test_cierre_masivo_aplica_estado_notas_y_usuario(): void
    {
        $a1 = $this->crearAlerta('111', 'pendiente');
        $this->crearAlerta('111', 'pendiente');

        $this->service->atenderRelacionadas(
            $a1,
            ['estado' => 'atendida', 'notas' => 'Revisado y validado'],
            42,
        );

        foreach (Alerta::where('numero_documento', '111')->get() as $alerta) {
            $this->assertSame('atendida', $alerta->estado);
            $this->assertSame('Revisado y validado', $alerta->notas);
            $this->assertSame(42, (int) $alerta->atendida_por);
            $this->assertNotNull($alerta->fecha_atencion);
        }
    }

    private function crearAlerta(string $documento, string $estado): Alerta
    {
        return Alerta::query()->create([
            'numero_documento' => $documento,
            'tipo_documento' => 'CC',
            'nivel_riesgo' => 'vinculante',
            'estado' => $estado,
            'tipo' => 'intento_operacion_db',
        ]);
    }

    private function crearEsquemaAlertas(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_alertas', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('intento_id')->nullable();
            $table->string('tipo')->nullable();
            $table->string('nivel_riesgo', 20)->nullable();
            $table->string('estado', 20)->default('pendiente');
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
    }
}
