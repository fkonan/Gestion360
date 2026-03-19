<?php

namespace Tests\Unit\Asistencia;

use App\Services\Asistencia\DecidirEventoService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DecidirEventoServiceTest extends TestCase
{
  public function test_permite_ingreso_en_rango_jornada_y_marca_llegada_tarde(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(10, '08:00:00', '17:00:00'),
    ]);

    $decisionAntes = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 7, 44, 0),
      $horarios,
      collect()
    );
    $this->assertSame('RECHAZADO', $decisionAntes->status);
    $this->assertSame('fuera de horarios', $decisionAntes->motivo);

    $decisionEnJornada = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 16, 59, 0),
      $horarios,
      collect()
    );

    $this->assertSame('OK', $decisionEnJornada->status);
    $this->assertSame(2, $decisionEnJornada->evento);
    $this->assertSame(10, $decisionEnJornada->horarioCargoId);
    $this->assertTrue($decisionEnJornada->llegadaTarde);
    $this->assertSame('LLEGADA TARDE', $decisionEnJornada->descripcion);
  }

  public function test_llegada_tarde_solo_despues_de_5_minutos_de_hora_inicio(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(10, '08:00:00', '17:00:00'),
    ]);

    $decisionNoTarde = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 8, 4, 0),
      $horarios,
      collect()
    );
    $this->assertSame('OK', $decisionNoTarde->status);
    $this->assertFalse($decisionNoTarde->llegadaTarde);

    $decisionTarde = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 8, 5, 0),
      $horarios,
      collect()
    );
    $this->assertSame('OK', $decisionTarde->status);
    $this->assertTrue($decisionTarde->llegadaTarde);
    $this->assertSame('LLEGADA TARDE', $decisionTarde->descripcion);
  }

  public function test_ingreso_se_habilita_desde_15_minutos_antes_de_hora_inicio(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(10, '07:00:00', '17:00:00'),
    ]);

    $decisionAntesVentana = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 6, 44, 0),
      $horarios,
      collect()
    );
    $this->assertSame('RECHAZADO', $decisionAntesVentana->status);
    $this->assertSame('fuera de horarios', $decisionAntesVentana->motivo);

    $decisionInicioVentana = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 6, 45, 0),
      $horarios,
      collect()
    );
    $this->assertSame('OK', $decisionInicioVentana->status);
    $this->assertSame(2, $decisionInicioVentana->evento);
    $this->assertSame(10, $decisionInicioVentana->horarioCargoId);
    $this->assertFalse($decisionInicioVentana->llegadaTarde);
  }

  public function test_salida_solo_desde_hora_fin(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(10, '08:00:00', '17:00:00'),
    ]);
    $eventosAbiertos = collect([
      $this->evento(2, 10),
    ]);

    $decisionAntes = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 16, 59, 0),
      $horarios,
      $eventosAbiertos
    );
    $this->assertSame('RECHAZADO', $decisionAntes->status);
    $this->assertSame('aun no puede salir', $decisionAntes->motivo);

    $decisionDespues = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 17, 1, 0),
      $horarios,
      $eventosAbiertos
    );
    $this->assertSame('OK', $decisionDespues->status);
    $this->assertSame(1, $decisionDespues->evento);
    $this->assertSame(10, $decisionDespues->horarioCargoId);
  }

  public function test_salida_empareja_por_horario_cargo_id(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(10, '08:00:00', '12:00:00'),
      $this->horario(20, '09:00:00', '18:00:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 20),
    ]);

    $decision = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 18, 5, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('OK', $decision->status);
    $this->assertSame(1, $decision->evento);
    $this->assertSame(20, $decision->horarioCargoId);
  }

  public function test_cargo_regular_convierte_salida_bloqueada_en_ingreso_de_jornada_activa(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(1, '07:00:00', '10:27:00'),
      $this->horario(2, '10:20:00', '17:30:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 1, '2026-03-04 10:10:00'),
    ]);

    $decision = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 4, 10, 28, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('OK', $decision->status);
    $this->assertSame(2, $decision->evento);
    $this->assertSame(2, $decision->horarioCargoId);
  }

  public function test_cargo_8_mantiene_salida_aunque_este_en_otra_jornada_valida(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(1, '07:00:00', '10:27:00'),
      $this->horario(2, '10:20:00', '17:30:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 1, '2026-03-04 10:10:00'),
    ]);

    $decision = $service->decidirConDatos(
      8,
      Carbon::create(2026, 3, 4, 10, 28, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('OK', $decision->status);
    $this->assertSame(1, $decision->evento);
    $this->assertSame(1, $decision->horarioCargoId);
  }

  public function test_si_hay_varios_ingresos_abiertos_evalua_todos_antes_de_rechazar(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(1, '08:00:00', '16:30:00'),
      $this->horario(2, '12:00:00', '17:00:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 1),
      $this->evento(2, 2),
    ]);

    $decision = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 17, 23, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('OK', $decision->status);
    $this->assertSame(1, $decision->evento);
    $this->assertSame(2, $decision->horarioCargoId);
  }

  public function test_no_permite_salida_tardia_de_jornada_anterior_si_ya_hubo_ingreso_posterior_en_otra_jornada(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(1, '07:00:00', '12:00:00'),
      $this->horario(2, '14:00:00', '17:00:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 1, '2026-03-02 07:05:00'),
      $this->evento(2, 2, '2026-03-02 14:05:00'),
      $this->evento(1, 2, '2026-03-02 17:01:00'),
    ]);

    $decision = $service->decidirConDatos(
      1,
      Carbon::create(2026, 3, 2, 17, 10, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('RECHAZADO', $decision->status);
    $this->assertSame('fuera de horarios', $decision->motivo);
    $this->assertSame('rechazado_fuera_de_horarios', $decision->trace['resultado'] ?? null);
    $this->assertSame(1, $decision->trace['horarios_ingreso_abierto_obsoletos'][0]['horario_cargo_id'] ?? null);
    $this->assertSame(2, $decision->trace['horarios_ingreso_abierto_obsoletos'][0]['horario_posterior_id'] ?? null);
  }

  public function test_si_no_hay_ingreso_previo_permite_ingreso_dentro_de_hora_inicio_y_hora_fin(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(1, '07:00:00', '12:00:00'),
      $this->horario(2, '12:00:00', '17:30:00'),
    ]);

    $decision = $service->decidirConDatos(
      8,
      Carbon::create(2026, 3, 3, 17, 23, 0),
      $horarios,
      collect()
    );

    $this->assertSame('OK', $decision->status);
    $this->assertSame(2, $decision->evento);
    $this->assertSame(2, $decision->horarioCargoId);
  }

  public function test_cargo_8_no_permite_reingreso_despues_de_salida_del_dia(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horarioConJornada(1, 8, 1, '07:00:00', '12:00:00'),
      $this->horarioConJornada(2, 8, 1, '12:00:00', '17:30:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 1, '2026-03-03 07:30:00'),
      $this->evento(1, 1, '2026-03-03 12:30:00'),
    ]);

    $decision = $service->decidirConDatos(
      8,
      Carbon::create(2026, 3, 3, 17, 23, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('RECHAZADO', $decision->status);
    $this->assertStringStartsWith('reingreso bloqueado por 7 horas desde la ultima salida', (string) $decision->motivo);
  }

  public function test_cargo_8_permite_reingreso_despues_de_7_horas(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horarioConJornada(1, 8, 1, '07:00:00', '12:00:00'),
      $this->horarioConJornada(2, 8, 1, '12:00:00', '17:30:00'),
    ]);
    $eventos = collect([
      $this->evento(2, 1, '2026-03-03 07:10:00'),
      $this->evento(1, 1, '2026-03-03 07:20:00'),
    ]);

    $decision = $service->decidirConDatos(
      8,
      Carbon::create(2026, 3, 3, 15, 30, 0),
      $horarios,
      $eventos
    );

    $this->assertSame('OK', $decision->status);
    $this->assertSame(2, $decision->evento);
    $this->assertSame(2, $decision->horarioCargoId);
  }

  public function test_cargo_tres_aplica_unicidad_diaria_de_ingreso_y_salida(): void
  {
    $service = new DecidirEventoService();
    $horarios = collect([
      $this->horario(10, '08:00:00', '17:00:00'),
    ]);

    $eventosConIngresoCerrado = collect([
      $this->evento(2, 10),
      $this->evento(1, 10),
    ]);
    $decisionIngresoDuplicado = $service->decidirConDatos(
      3,
      Carbon::create(2026, 3, 2, 7, 50, 0),
      $horarios,
      $eventosConIngresoCerrado
    );
    $this->assertSame('RECHAZADO', $decisionIngresoDuplicado->status);
    $this->assertSame('ya existe ingreso registrado hoy', $decisionIngresoDuplicado->motivo);

    $eventosConSalidaYaRegistrada = collect([
      $this->evento(2, 10),
      $this->evento(1, 99),
    ]);
    $decisionSalidaDuplicada = $service->decidirConDatos(
      3,
      Carbon::create(2026, 3, 2, 17, 10, 0),
      $horarios,
      $eventosConSalidaYaRegistrada
    );
    $this->assertSame('RECHAZADO', $decisionSalidaDuplicada->status);
    $this->assertSame('ya existe salida registrada hoy', $decisionSalidaDuplicada->motivo);
  }

  private function horario(int $id, string $horaInicio, string $horaFin): object
  {
    return (object) [
      'id' => $id,
      'cargo_id' => null,
      'jornada' => null,
      'hora_inicio' => Carbon::createFromFormat('H:i:s', $horaInicio),
      'hora_fin' => Carbon::createFromFormat('H:i:s', $horaFin),
    ];
  }

  private function horarioConJornada(
    int $id,
    int $cargoId,
    int $jornada,
    string $horaInicio,
    string $horaFin
  ): object {
    return (object) [
      'id' => $id,
      'cargo_id' => $cargoId,
      'jornada' => $jornada,
      'hora_inicio' => Carbon::createFromFormat('H:i:s', $horaInicio),
      'hora_fin' => Carbon::createFromFormat('H:i:s', $horaFin),
    ];
  }

  private function evento(int $evento, ?int $horarioCargoId, ?string $fechaCreacion = null): object
  {
    return (object) [
      'evento' => $evento,
      'horario_cargo_id' => $horarioCargoId,
      'fecha_creacion' => $fechaCreacion,
    ];
  }
}
