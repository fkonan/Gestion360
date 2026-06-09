<?php

namespace Tests\Unit\Asistencia;

use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use App\Services\Asistencia\DecidirEventoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class RegistrarEventoEmpleadoServiceCargoCentroTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->configurarConexionOracle360Sqlite();
    $this->crearTablasPruebaHuellero();
  }

  public function test_auxiliar_repetido_resuelve_cargo_por_centro_de_costo(): void
  {
    $this->insertarCentroCosto(1, 'OFICINA SISTEMAS');
    $this->insertarCentroCosto(2, 'TALENTO HUMANO');

    $this->insertarCargo(100, 1, 'AUXILIAR');
    $this->insertarCargo(200, 2, 'AUXILIAR');

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());

    $cargoId = $this->invocarResolverCargoId(
      $service,
      'Auxiliar',
      '1160 - OFICINA DE SISTEMAS'
    );

    $this->assertSame(100, $cargoId);
  }

  public function test_auxiliar_por_centro_aplica_horario_del_cargo_correcto(): void
  {
    $this->insertarCentroCosto(1, 'OFICINA SISTEMAS');
    $this->insertarCentroCosto(2, 'TALENTO HUMANO');

    $this->insertarCargo(100, 1, 'AUXILIAR');
    $this->insertarCargo(200, 2, 'AUXILIAR');

    $this->insertarHorarioCargo(1001, 100, '06:00:00', '14:00:00');
    $this->insertarHorarioCargo(2001, 200, '14:00:00', '22:00:00');

    $registrador = new RegistrarEventoEmpleadoService(new DecidirEventoService());
    $decisor = new DecidirEventoService();

    $cargoSistemas = $this->invocarResolverCargoId($registrador, 'AUXILIAR', '1160 - OFICINA DE SISTEMAS');
    $cargoTalento = $this->invocarResolverCargoId($registrador, 'AUXILIAR', 'TALENTO HUMANO');

    $fechaSistemas = now()->copy()->setDate(2026, 4, 13)->setTime(9, 0, 0);
    $fechaTalento = now()->copy()->setDate(2026, 4, 13)->setTime(15, 0, 0);
    $horariosSistemas = $this->invocarObtenerHorariosAplicables($decisor, (int) $cargoSistemas, $fechaSistemas);
    $horariosTalento = $this->invocarObtenerHorariosAplicables($decisor, (int) $cargoTalento, $fechaTalento);
    $decisionSistemas = $decisor->decidirConDatos((int) $cargoSistemas, $fechaSistemas, $horariosSistemas, collect());
    $decisionTalento = $decisor->decidirConDatos((int) $cargoTalento, $fechaTalento, $horariosTalento, collect());

    $this->assertSame(100, $cargoSistemas);
    $this->assertSame(200, $cargoTalento);
    $this->assertSame(1001, (int) ($horariosSistemas->first()->id ?? 0));
    $this->assertSame(2001, (int) ($horariosTalento->first()->id ?? 0));
    $this->assertSame('OK', $decisionSistemas->status);
    $this->assertSame(1001, $decisionSistemas->horarioCargoId);
    $this->assertSame('OK', $decisionTalento->status);
    $this->assertSame(2001, $decisionTalento->horarioCargoId);
  }

  public function test_auxiliar_repetido_devuelve_null_si_centro_no_coincide(): void
  {
    $this->insertarCentroCosto(1, 'OFICINA SISTEMAS');
    $this->insertarCentroCosto(2, 'TALENTO HUMANO');

    $this->insertarCargo(100, 1, 'AUXILIAR');
    $this->insertarCargo(200, 2, 'AUXILIAR');

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());

    $cargoId = $this->invocarResolverCargoId(
      $service,
      'AUXILIAR',
      'CENTRO NO REGISTRADO'
    );

    $this->assertNull($cargoId);
  }

  public function test_si_el_nombre_de_cargo_es_unico_hace_fallback_por_nombre(): void
  {
    $this->insertarCentroCosto(1, 'OFICINA SISTEMAS');
    $this->insertarCargo(100, 1, 'AUXILIAR');

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());

    $cargoId = $this->invocarResolverCargoId(
      $service,
      'AUXILIAR',
      'CENTRO NO REGISTRADO'
    );

    $this->assertSame(100, $cargoId);
  }

  public function test_si_no_hay_horario_especifico_del_cargo_usa_horario_por_defecto(): void
  {
    $this->insertarHorario(9001, null, 1, 7, '08:00:00', '17:00:00');

    $decisor = new DecidirEventoService();
    $fecha = now()->copy()->setDate(2026, 4, 14)->setTime(9, 0, 0);
    $horarios = $this->invocarObtenerHorariosAplicables($decisor, 999, $fecha);
    $decision = $decisor->decidirConDatos(999, $fecha, $horarios, collect());

    $this->assertCount(1, $horarios);
    $this->assertSame(9001, (int) ($horarios->first()->id ?? 0));
    $this->assertSame('OK', $decision->status);
    $this->assertSame(2, $decision->evento);
    $this->assertSame(9001, $decision->horarioCargoId);
  }

  public function test_horario_por_defecto_nocturno_permite_ingreso_y_salida_de_madrugada(): void
  {
    $this->insertarHorario(9002, null, 1, 7, '22:00:00', '06:00:00');

    $decisor = new DecidirEventoService();

    $fechaIngreso = now()->copy()->setDate(2026, 4, 14)->setTime(23, 10, 0);
    $horariosIngreso = $this->invocarObtenerHorariosAplicables($decisor, 999, $fechaIngreso);
    $decisionIngreso = $decisor->decidirConDatos(999, $fechaIngreso, $horariosIngreso, collect());

    $eventosAbiertos = collect([
      (object) [
        'evento' => 2,
        'horario_cargo_id' => 9002,
        'fecha_creacion' => '2026-04-14 22:10:00',
      ],
    ]);
    $fechaSalida = now()->copy()->setDate(2026, 4, 15)->setTime(6, 2, 0);
    $horariosSalida = $this->invocarObtenerHorariosAplicables($decisor, 999, $fechaSalida);
    $decisionSalida = $decisor->decidirConDatos(999, $fechaSalida, $horariosSalida, $eventosAbiertos);

    $this->assertSame('OK', $decisionIngreso->status);
    $this->assertSame(2, $decisionIngreso->evento);
    $this->assertSame(9002, $decisionIngreso->horarioCargoId);
    $this->assertSame('OK', $decisionSalida->status);
    $this->assertSame(1, $decisionSalida->evento);
    $this->assertSame(9002, $decisionSalida->horarioCargoId);
  }

  public function test_cargo_con_dos_jornadas_elige_la_jornada_correcta_en_transicion_dia_noche(): void
  {
    $this->insertarCentroCosto(10, 'OPERACIONES');
    $this->insertarCargo(500, 10, 'SUPERVISOR');
    $this->insertarHorario(5001, 500, 1, 7, '08:00:00', '12:00:00');
    $this->insertarHorario(5002, 500, 1, 7, '22:00:00', '06:00:00');

    $decisor = new DecidirEventoService();

    $fechaDiurna = now()->copy()->setDate(2026, 4, 14)->setTime(10, 0, 0);
    $horariosDiurnos = $this->invocarObtenerHorariosAplicables($decisor, 500, $fechaDiurna);
    $decisionDiurna = $decisor->decidirConDatos(500, $fechaDiurna, $horariosDiurnos, collect());

    $fechaNocturna = now()->copy()->setDate(2026, 4, 14)->setTime(23, 0, 0);
    $horariosNocturnos = $this->invocarObtenerHorariosAplicables($decisor, 500, $fechaNocturna);
    $decisionNocturna = $decisor->decidirConDatos(500, $fechaNocturna, $horariosNocturnos, collect());

    $this->assertSame('OK', $decisionDiurna->status);
    $this->assertSame(5001, $decisionDiurna->horarioCargoId);
    $this->assertSame('OK', $decisionNocturna->status);
    $this->assertSame(5002, $decisionNocturna->horarioCargoId);
  }

  public function test_ingreso_por_novedad_no_asigna_horario_vencido(): void
  {
    $this->insertarHorario(9101, null, 1, 7, '08:00:00', '12:00:00');

    $decisor = new DecidirEventoService();
    $fecha = now()->copy()->setDate(2026, 4, 14)->setTime(13, 0, 0);
    $resultado = $decisor->resolverHorarioParaIngresoPorNovedad(999, $fecha);

    $this->assertFalse((bool) ($resultado['ok'] ?? true));
    $this->assertSame('sin_horario_para_ingreso', (string) ($resultado['motivo'] ?? ''));
  }

  public function test_ingreso_por_novedad_usa_horario_futuro_mas_cercano(): void
  {
    $this->insertarHorario(9201, null, 1, 7, '14:00:00', '22:00:00');
    $this->insertarHorario(9202, null, 1, 7, '16:00:00', '23:00:00');

    $decisor = new DecidirEventoService();
    $fecha = now()->copy()->setDate(2026, 4, 14)->setTime(13, 0, 0);
    $resultado = $decisor->resolverHorarioParaIngresoPorNovedad(999, $fecha);

    $this->assertTrue((bool) ($resultado['ok'] ?? false));
    $this->assertSame(9201, (int) ($resultado['horario_cargo_id'] ?? 0));
    $this->assertSame('hora_inicio_mas_cercana_hacia_adelante', (string) ($resultado['criterio'] ?? ''));
  }

  public function test_evento_manual_repetido_en_menos_de_dos_minutos_se_rechaza(): void
  {
    $this->insertarEventoPrs([
      'id' => 2001,
      'evento' => 2,
      'descripcion' => 'ingreso de personal por huella',
      'identificacion' => '77777',
      'fecha_creacion' => '2026-04-14 09:00:00',
    ]);

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());
    $decision = $this->invocarResolverDecision(
      $service,
      '77777',
      2,
      null,
      now()->copy()->setDate(2026, 4, 14)->setTime(9, 1, 0)
    );

    $this->assertFalse($decision->esOk());
    $this->assertSame('RECHAZADO', $decision->status);
    $this->assertStringContainsString(
      'espere 2 minutos antes de repetir el mismo evento',
      strtolower((string) $decision->motivo)
    );
  }

  public function test_evento_manual_repetido_a_los_dos_minutos_se_permite(): void
  {
    $this->insertarEventoPrs([
      'id' => 2002,
      'evento' => 1,
      'descripcion' => 'salida de personal por huella',
      'identificacion' => '88888',
      'fecha_creacion' => '2026-04-14 10:00:00',
    ]);

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());
    $decision = $this->invocarResolverDecision(
      $service,
      '88888',
      1,
      null,
      now()->copy()->setDate(2026, 4, 14)->setTime(10, 2, 0)
    );

    $this->assertTrue($decision->esOk());
    $this->assertSame('OK', $decision->status);
    $this->assertSame(1, $decision->evento);
  }

  public function test_usuario_auditoria_per_personas_prefiere_el_usuario_provisto(): void
  {
    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());

    $usuario = $this->invocarResolverUsuarioAuditoriaPerPersonas(
      $service,
      987654,
      123456
    );

    $this->assertSame(987654, $usuario);
  }

  public function test_usuario_auditoria_per_personas_hace_fallback_a_persona_cuando_viene_null(): void
  {
    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());

    $usuario = $this->invocarResolverUsuarioAuditoriaPerPersonas(
      $service,
      null,
      123456
    );

    $this->assertSame(123456, $usuario);
  }

  public function test_empresa_evento_corresponde_a_la_persona_del_centro_de_costo(): void
  {
    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());
    $cargoDetalle = (object) ['centro_costo_persona_id' => 8420];

    $empresaId = $this->invocarResolverEmpresaCentroCostoId($service, $cargoDetalle);

    $this->assertSame(8420, $empresaId);
  }

  public function test_empresa_evento_no_usa_un_valor_fijo_si_el_centro_no_tiene_persona(): void
  {
    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());

    $empresaId = $this->invocarResolverEmpresaCentroCostoId($service, (object) []);

    $this->assertNull($empresaId);
  }

  private function invocarResolverCargoId(
    RegistrarEventoEmpleadoService $service,
    string $cargoNombre,
    ?string $centroCosto
  ): ?int {
    $method = new ReflectionMethod(RegistrarEventoEmpleadoService::class, 'resolverCargoId');
    $method->setAccessible(true);

    return $method->invoke($service, $cargoNombre, $centroCosto);
  }

  private function invocarObtenerHorariosAplicables(
    DecidirEventoService $service,
    int $cargoId,
    \Carbon\Carbon $fecha
  ): \Illuminate\Support\Collection {
    $method = new ReflectionMethod(DecidirEventoService::class, 'obtenerHorariosAplicables');
    $method->setAccessible(true);

    return $method->invoke($service, $cargoId, $fecha);
  }

  private function invocarResolverDecision(
    RegistrarEventoEmpleadoService $service,
    string $identificacion,
    ?int $eventoManual,
    ?int $cargoId,
    \Carbon\Carbon $fecha
  ) {
    $method = new ReflectionMethod(RegistrarEventoEmpleadoService::class, 'resolverDecision');
    $method->setAccessible(true);

    return $method->invoke($service, $identificacion, $eventoManual, $cargoId, $fecha);
  }

  private function invocarResolverUsuarioAuditoriaPerPersonas(
    RegistrarEventoEmpleadoService $service,
    ?int $usuarioPerPersonasId,
    int $personaId
  ): int {
    $method = new ReflectionMethod(RegistrarEventoEmpleadoService::class, 'resolverUsuarioAuditoriaPerPersonas');
    $method->setAccessible(true);

    return (int) $method->invoke($service, $usuarioPerPersonasId, $personaId);
  }

  private function invocarResolverEmpresaCentroCostoId(
    RegistrarEventoEmpleadoService $service,
    mixed $cargoDetalle
  ): ?int {
    $method = new ReflectionMethod(RegistrarEventoEmpleadoService::class, 'resolverEmpresaCentroCostoId');
    $method->setAccessible(true);

    return $method->invoke($service, $cargoDetalle);
  }

  private function configurarConexionOracle360Sqlite(): void
  {
    config()->set('database.connections.oracle-360', [
      'driver' => 'sqlite',
      'database' => ':memory:',
      'prefix' => '',
      'foreign_key_constraints' => false,
    ]);

    DB::purge('oracle-360');
    DB::reconnect('oracle-360');
  }

  private function crearTablasPruebaHuellero(): void
  {
    $schema = Schema::connection('oracle-360');

    $schema->dropIfExists('PRS_HORARIOS_CARGOS');
    $schema->dropIfExists('PRS_CARGOS');
    $schema->dropIfExists('PRS_CENTRO_COSTO');
    $schema->dropIfExists('PRS_EVENTOS');
    $schema->dropIfExists('EMP_NOVEDADES');
    $schema->dropIfExists('EMP_NOVEDADES_TIPO');

    $schema->create('PRS_CENTRO_COSTO', function (Blueprint $table) {
      $table->integer('id')->primary();
      $table->string('nombre', 150);
      $table->integer('estado')->default(1);
    });

    $schema->create('PRS_CARGOS', function (Blueprint $table) {
      $table->integer('id')->primary();
      $table->integer('centro_costo_id');
      $table->string('nombre', 150);
      $table->integer('estado')->default(1);
    });

    $schema->create('PRS_HORARIOS_CARGOS', function (Blueprint $table) {
      $table->integer('id')->primary();
      $table->integer('cargo_id')->nullable();
      $table->integer('jornada')->nullable();
      $table->integer('dia_inicio')->nullable();
      $table->integer('dia_fin')->nullable();
      $table->dateTime('hora_inicio')->nullable();
      $table->dateTime('hora_fin')->nullable();
      $table->integer('estado')->default(1);
    });

    $schema->create('PRS_EVENTOS', function (Blueprint $table) {
      $table->integer('id')->primary();
      $table->integer('evento');
      $table->string('descripcion')->nullable();
      $table->integer('tipo')->nullable();
      $table->string('identificacion');
      $table->dateTime('fecha_creacion');
      $table->string('usuario_creacion')->nullable();
      $table->integer('horario_cargo_id')->nullable();
      $table->integer('llegada_tarde')->nullable();
    });

    $schema->create('EMP_NOVEDADES_TIPO', function (Blueprint $table) {
      $table->string('id')->primary();
      $table->string('descripcion')->nullable();
      $table->string('bloquea', 1)->nullable();
    });

    $schema->create('EMP_NOVEDADES', function (Blueprint $table) {
      $table->string('id')->primary();
      $table->string('id_persona');
      $table->string('id_tipo_novedad');
      $table->dateTime('fecha_inicio');
      $table->dateTime('fecha_fin')->nullable();
      $table->string('estado');
      $table->dateTime('fecha_creacion');
      $table->dateTime('fecha_modifica')->nullable();
      $table->string('usuario_modifica')->nullable();
      $table->text('observacion')->nullable();
    });

  }

  private function insertarCentroCosto(int $id, string $nombre): void
  {
    DB::connection('oracle-360')
      ->table('PRS_CENTRO_COSTO')
      ->insert([
        'id' => $id,
        'nombre' => $nombre,
        'estado' => 1,
      ]);
  }

  private function insertarCargo(int $id, int $centroCostoId, string $nombre): void
  {
    DB::connection('oracle-360')
      ->table('PRS_CARGOS')
      ->insert([
        'id' => $id,
        'centro_costo_id' => $centroCostoId,
        'nombre' => $nombre,
        'estado' => 1,
      ]);
  }

  private function insertarHorarioCargo(int $id, int $cargoId, string $horaInicio, string $horaFin): void
  {
    $this->insertarHorario($id, $cargoId, 1, 7, $horaInicio, $horaFin);
  }

  private function insertarHorario(
    int $id,
    ?int $cargoId,
    int $diaInicio,
    int $diaFin,
    string $horaInicio,
    string $horaFin
  ): void
  {
    DB::connection('oracle-360')
      ->table('PRS_HORARIOS_CARGOS')
      ->insert([
        'id' => $id,
        'cargo_id' => $cargoId,
        'jornada' => 1,
        'dia_inicio' => $diaInicio,
        'dia_fin' => $diaFin,
        'hora_inicio' => '2026-04-13 ' . $horaInicio,
        'hora_fin' => '2026-04-13 ' . $horaFin,
        'estado' => 1,
      ]);
  }

  public function test_retorno_abierto_por_novedad_permite_ingreso_aunque_no_haya_fecha_fin(): void
  {
    $this->insertarNovedadTipo('tipo-1', 'Permiso');
    $this->insertarNovedad([
      'id' => 'nov-1',
      'id_persona' => '12345',
      'id_tipo_novedad' => 'tipo-1',
      'fecha_inicio' => '2026-04-14 09:00:00',
      'fecha_fin' => null,
      'estado' => 'APROBADO',
    ]);
    $this->insertarEventoPrs([
      'id' => 1,
      'evento' => 1,
      'descripcion' => 'salida por novedad aprobada',
      'identificacion' => '12345',
      'fecha_creacion' => '2026-04-14 09:00:00',
      'horario_cargo_id' => 9101,
    ]);

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());
    $method = new ReflectionMethod(RegistrarEventoEmpleadoService::class, 'resolverIngresoFlexiblePostSalidaNovedad');
    $method->setAccessible(true);
    $resultado = $method->invoke($service, '12345', now()->copy()->setDate(2026, 4, 14)->setTime(11, 30, 0));

    $this->assertTrue((bool) ($resultado['aplica'] ?? false));
    $this->assertSame(2, (int) ($resultado['evento'] ?? 0));
    $this->assertSame('retorno_abierto', (string) ($resultado['ventana'] ?? ''));
    $this->assertSame(9101, (int) ($resultado['horario_cargo_id'] ?? 0));
  }

  public function test_retorno_abierto_por_novedad_no_aplica_si_ya_hubo_ingreso_posterior(): void
  {
    $this->insertarNovedadTipo('tipo-2', 'Permiso');
    $this->insertarNovedad([
      'id' => 'nov-2',
      'id_persona' => '12346',
      'id_tipo_novedad' => 'tipo-2',
      'fecha_inicio' => '2026-04-14 09:00:00',
      'fecha_fin' => null,
      'estado' => 'APROBADO',
    ]);
    $this->insertarEventoPrs([
      'id' => 2,
      'evento' => 1,
      'descripcion' => 'salida por novedad aprobada',
      'identificacion' => '12346',
      'fecha_creacion' => '2026-04-14 09:00:00',
      'horario_cargo_id' => 9101,
    ]);
    $this->insertarEventoPrs([
      'id' => 3,
      'evento' => 2,
      'descripcion' => 'entrada por novedad aprobada',
      'identificacion' => '12346',
      'fecha_creacion' => '2026-04-14 09:40:00',
      'horario_cargo_id' => 9101,
    ]);

    $service = new RegistrarEventoEmpleadoService(new DecidirEventoService());
    $method = new ReflectionMethod(RegistrarEventoEmpleadoService::class, 'resolverIngresoFlexiblePostSalidaNovedad');
    $method->setAccessible(true);
    $resultado = $method->invoke($service, '12346', now()->copy()->setDate(2026, 4, 14)->setTime(11, 30, 0));

    $this->assertFalse((bool) ($resultado['aplica'] ?? true));
  }

  private function insertarNovedadTipo(string $id, string $descripcion): void
  {
    DB::connection('oracle-360')
      ->table('EMP_NOVEDADES_TIPO')
      ->insert([
        'id' => $id,
        'descripcion' => $descripcion,
        'bloquea' => 'F',
      ]);
  }

  private function insertarNovedad(array $data): void
  {
    DB::connection('oracle-360')
      ->table('EMP_NOVEDADES')
      ->insert([
        'id' => $data['id'],
        'id_persona' => $data['id_persona'],
        'id_tipo_novedad' => $data['id_tipo_novedad'],
        'fecha_inicio' => $data['fecha_inicio'],
        'fecha_fin' => $data['fecha_fin'],
        'estado' => $data['estado'],
        'fecha_creacion' => '2026-04-14 08:00:00',
        'fecha_modifica' => null,
        'usuario_modifica' => null,
        'observacion' => null,
      ]);
  }

  private function insertarEventoPrs(array $data): void
  {
    DB::connection('oracle-360')
      ->table('PRS_EVENTOS')
      ->insert([
        'id' => $data['id'],
        'evento' => $data['evento'],
        'descripcion' => $data['descripcion'] ?? null,
        'tipo' => 1,
        'identificacion' => $data['identificacion'],
        'fecha_creacion' => $data['fecha_creacion'],
        'usuario_creacion' => null,
        'horario_cargo_id' => $data['horario_cargo_id'] ?? null,
        'llegada_tarde' => 0,
      ]);
  }
}
