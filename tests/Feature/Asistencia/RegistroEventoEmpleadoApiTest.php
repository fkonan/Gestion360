<?php

namespace Tests\Feature\Asistencia;

use App\Http\Middleware\ValidateAttendanceApiKey;
use App\Modules\Huellero\Http\Controllers\Api\EventoEmpleadoApiController;
use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RegistroEventoEmpleadoApiTest extends TestCase
{
  protected function tearDown(): void
  {
    Mockery::close();

    parent::tearDown();
  }

  public function test_rechaza_peticion_sin_api_key(): void
  {
    config()->set('services.attendance_events.key', 'secreta');

    $middleware = new ValidateAttendanceApiKey();
    $request = Request::create('/api/asistencia/eventos/empleados', 'POST');

    $response = $middleware->handle($request, function () {
      return response()->json(['ok' => true]);
    });

    $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    $this->assertSame([
      'ok' => false,
      'message' => 'Unauthorized.',
    ], $response->getData(true));
  }

  public function test_endpoint_api_usa_el_mismo_orquestador_compartido(): void
  {
    $orquestador = Mockery::mock(RegistrarEventoEmpleadoService::class);
    $orquestador->shouldReceive('registrar')
      ->once()
      ->ordered()
      ->with(
        '123456',
        null,
        null,
        '9001',
        77,
        'api'
      )
      ->andReturn([
        'ok' => true,
        'status' => 'OK',
        'evento' => 2,
        'horario_cargo_id' => 10,
        'cargo_id' => 5,
        'flags' => [
          'llegada_tarde' => false,
          'cargo_especial' => false,
        ],
        'fecha_evento' => '2026-03-16T08:00:00-05:00',
        'data' => [
          'id' => 555,
          'nombre' => 'PERSONA TEST',
          'cargo' => 'CARGO TEST',
        ],
        'origen' => 'api',
      ]);
    $orquestador->shouldReceive('registrar')
      ->once()
      ->ordered()
      ->with(
        '789012',
        null,
        null,
        '9001',
        77,
        'api'
      )
      ->andReturn([
        'ok' => false,
        'status' => 'RECHAZADO',
        'evento' => null,
        'motivo' => 'fuera de horarios',
        'horario_cargo_id' => null,
        'cargo_id' => 8,
        'flags' => [
          'llegada_tarde' => false,
          'cargo_especial' => false,
        ],
        'error' => 'fuera de horarios',
        'data' => [
          'id' => null,
          'nombre' => 'PERSONA TEST 2',
          'cargo' => 'CARGO TEST 2',
        ],
        'origen' => 'api',
      ]);

    $controller = new EventoEmpleadoApiController($orquestador);
    $request = Request::create('/api/asistencia/eventos/empleados', 'POST', [
      'identificaciones' => ['123456', '789012'],
      'documento_usuario' => '9001',
      'usuario_notificacion' => 77,
    ]);

    $response = $controller->store($request);

    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertSame([
      'ok' => false,
      'status' => 'PARCIAL',
      'resumen' => [
        'total' => 2,
        'exitosos' => 1,
        'fallidos' => 1,
      ],
      'origen' => 'api',
    ], $response->getData(true));
  }
}
