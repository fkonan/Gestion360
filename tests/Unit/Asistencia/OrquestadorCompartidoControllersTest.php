<?php

namespace Tests\Unit\Asistencia;

use App\Modules\Camara\Http\Controllers\Api\CamaraApiController;
use App\Modules\Camara\Http\Requests\RecognizeLiveRequest;
use App\Modules\Camara\Services\CameraService;
use App\Modules\Huellero\Http\Controllers\FingerprintController;
use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class OrquestadorCompartidoControllersTest extends TestCase
{
  public function test_huellero_y_camara_usan_el_mismo_orquestador(): void
  {
    $servicio = RegistrarEventoEmpleadoService::class;

    $constructorHuellero = new ReflectionMethod(FingerprintController::class, '__construct');
    $constructorCamara = new ReflectionMethod(CamaraApiController::class, '__construct');

    $tiposHuellero = array_map(
      fn ($p) => $p->getType()?->getName(),
      $constructorHuellero->getParameters()
    );
    $tiposCamara = array_map(
      fn ($p) => $p->getType()?->getName(),
      $constructorCamara->getParameters()
    );

    $this->assertContains($servicio, $tiposHuellero);
    $this->assertContains($servicio, $tiposCamara);
  }

  public function test_huellero_store_evento_empleado_llama_orquestador_compartido(): void
  {
    $orquestador = $this->createMock(RegistrarEventoEmpleadoService::class);
    $orquestador->expects($this->once())
      ->method('registrar')
      ->with(
        '123456',
        null,
        null,
        '9001',
        77,
        'huella'
      )
      ->willReturn([
        'ok' => true,
        'status' => 'OK',
        'evento' => 2,
        'horario_cargo_id' => 10,
        'cargo_id' => 3,
        'flags' => [
          'llegada_tarde' => false,
          'cargo_especial' => true,
        ],
        'data' => [
          'id' => 555,
          'nombre' => 'PERSONA TEST',
          'cargo' => 'CARGO TEST',
        ],
      ]);

    $controller = new FingerprintController($orquestador);
    $request = Request::create('/fingerprint/eventos/empleados', 'POST', [
      'identificacion' => '123456',
    ]);
    $request->setUserResolver(function () {
      return (object) [
        'IdUsuario' => 77,
        'persona' => (object) ['PerNumDoc' => '9001'],
      ];
    });

    $response = $controller->storeEventoEmpleado($request);
    $payload = $response->getData(true);

    $this->assertSame(200, $response->status());
    $this->assertTrue($payload['ok']);
    $this->assertSame('OK', $payload['status']);
    $this->assertSame(2, $payload['evento']);
  }

  public function test_camara_recognize_live_llama_orquestador_compartido(): void
  {
    $cameraResponse = $this->createMock(Response::class);
    $cameraResponse->method('failed')->willReturn(false);
    $cameraResponse->method('status')->willReturn(200);
    $cameraResponse->method('json')->willReturn([
      'ok' => true,
      'personas' => [
        [
          'identificacion' => '123456',
          'nombre' => 'PERSONA TEST',
        ],
      ],
    ]);

    $cameraService = $this->createMock(CameraService::class);
    $cameraService->expects($this->once())
      ->method('recognizeBatch')
      ->willReturn($cameraResponse);

    $orquestador = $this->createMock(RegistrarEventoEmpleadoService::class);
    $orquestador->expects($this->once())
      ->method('registrar')
      ->with(
        '123456',
        null,
        $this->isInstanceOf(\Carbon\Carbon::class),
        '9001',
        77,
        'camara'
      )
      ->willReturn([
        'ok' => true,
        'status' => 'OK',
        'evento' => 2,
        'horario_cargo_id' => 10,
        'cargo_id' => 5,
        'flags' => [
          'llegada_tarde' => false,
          'cargo_especial' => false,
        ],
        'fecha_evento' => '2026-03-03T08:00:00-05:00',
      ]);

    $controller = new CamaraApiController($cameraService, $orquestador);
    $request = RecognizeLiveRequest::create('/camera/recognize-live', 'POST', [], [], []);
    $request->setUserResolver(function () {
      return (object) [
        'IdUsuario' => 77,
        'persona' => (object) ['PerNumDoc' => '9001'],
      ];
    });

    $response = $controller->recognizeLive($request);
    $payload = $response->getData(true);

    $this->assertSame(200, $response->status());
    $this->assertTrue($payload['ok']);
    $this->assertNotEmpty($payload['personas']);
    $this->assertSame(2, $payload['personas'][0]['evento']);
  }
}
