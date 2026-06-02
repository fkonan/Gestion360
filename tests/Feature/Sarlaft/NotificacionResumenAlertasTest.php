<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Mail\ResumenAlertasMail;
use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Services\NotificacionAlertaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificacionResumenAlertasTest extends TestCase
{
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
    }

    public function test_no_envia_correo_si_no_hay_alertas(): void
    {
        Mail::fake();

        app(NotificacionAlertaService::class)->notificarResumen(collect(), 'Logtrans');

        Mail::assertNothingSent();
    }

    public function test_no_envia_correo_si_no_hay_oficiales_con_email(): void
    {
        Mail::fake();

        // Sin oficiales configurados, no debe enviar (pero no rompe).
        $alertas = collect([$this->crearAlerta('111', 'vinculante')]);

        app(NotificacionAlertaService::class)->notificarResumen($alertas, 'Logtrans');

        Mail::assertNothingSent();
    }

    public function test_arma_el_mail_resumen_con_todas_las_alertas(): void
    {
        // Validamos la construccion del Mailable (sin depender de destinatarios reales).
        $alertas = collect([
            $this->crearAlerta('111', 'vinculante'),
            $this->crearAlerta('111', 'vinculante'),
            $this->crearAlerta('222', 'restrictiva'),
        ]);

        $mail = new ResumenAlertasMail($alertas, 'Logtrans');
        $envelope = $mail->envelope();

        // Asunto refleja el total y las vinculantes.
        $this->assertStringContainsString('3 nueva(s) coincidencia(s)', $envelope->subject);
        $this->assertStringContainsString('2 en lista vinculante', $envelope->subject);
        $this->assertSame(3, $mail->alertas->count());
    }

    private function crearAlerta(string $documento, string $nivel): Alerta
    {
        return Alerta::query()->create([
            'numero_documento' => $documento,
            'tipo_documento' => 'CC',
            'nivel_riesgo' => $nivel,
            'estado' => 'pendiente',
            'tipo' => 'intento_operacion_db',
            'datos_persona' => ['nombre' => 'Persona '.$documento],
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
