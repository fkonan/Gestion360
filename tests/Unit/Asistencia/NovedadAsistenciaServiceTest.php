<?php

namespace Tests\Unit\Asistencia;

use App\Services\Asistencia\NovedadAsistenciaService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NovedadAsistenciaServiceTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->configurarConexionOracle360Sqlite();
    $this->crearTablasPruebaNovedades();
    $this->insertarCatalogoTipos();
  }

  public function test_fecha_inicio_permita_salida_en_ventana(): void
  {
    $this->insertarNovedad([
      'id' => 'nov-1',
      'id_persona' => '1095913073',
      'id_tipo_novedad' => 'tipo-incapacidad',
      'fecha_inicio' => '2026-04-21 10:00:00',
      'fecha_fin' => '2026-04-21 18:00:00',
      'estado' => 'APROBADO',
    ]);

    $service = new NovedadAsistenciaService();
    $resultado = $service->resolverEventoPermitido(
      '1095913073',
      Carbon::parse('2026-04-21 10:15:00')
    );

    $this->assertTrue($resultado['aplica']);
    $this->assertSame(1, $resultado['evento']);
    $this->assertSame('fecha_inicio', $resultado['ventana']);
  }

  public function test_fecha_fin_permita_ingreso_en_ventana(): void
  {
    $this->insertarNovedad([
      'id' => 'nov-2',
      'id_persona' => '1095913073',
      'id_tipo_novedad' => 'tipo-permiso',
      'fecha_inicio' => '2026-04-21 08:00:00',
      'fecha_fin' => '2026-04-21 12:00:00',
      'estado' => 'APROBADO',
    ]);

    $service = new NovedadAsistenciaService();
    $resultado = $service->resolverEventoPermitido(
      '1095913073',
      Carbon::parse('2026-04-21 12:10:00')
    );

    $this->assertTrue($resultado['aplica']);
    $this->assertSame(2, $resultado['evento']);
    $this->assertSame('fecha_fin', $resultado['ventana']);
  }

  public function test_fuera_de_ventana_no_aplica_override(): void
  {
    $this->insertarNovedad([
      'id' => 'nov-3',
      'id_persona' => '1095913073',
      'id_tipo_novedad' => 'tipo-vacaciones',
      'fecha_inicio' => '2026-04-21 08:00:00',
      'fecha_fin' => '2026-04-21 12:00:00',
      'estado' => 'APROBADO',
    ]);

    $service = new NovedadAsistenciaService();
    $resultado = $service->resolverEventoPermitido(
      '1095913073',
      Carbon::parse('2026-04-21 13:00:00')
    );

    $this->assertFalse($resultado['aplica']);
  }

  public function test_si_coinciden_inicio_y_fin_prioriza_inicio(): void
  {
    $this->insertarNovedad([
      'id' => 'nov-4',
      'id_persona' => '1095913073',
      'id_tipo_novedad' => 'tipo-permiso',
      'fecha_inicio' => '2026-04-21 10:00:00',
      'fecha_fin' => '2026-04-21 10:00:00',
      'estado' => 'APROBADO',
    ]);

    $service = new NovedadAsistenciaService();
    $resultado = $service->resolverEventoPermitido(
      '1095913073',
      Carbon::parse('2026-04-21 10:02:00')
    );

    $this->assertTrue($resultado['aplica']);
    $this->assertSame(1, $resultado['evento']);
    $this->assertSame('fecha_inicio', $resultado['ventana']);
  }

  public function test_en_solape_cerca_de_fecha_inicio_prioriza_salida(): void
  {
    $this->insertarNovedad([
      'id' => 'nov-solape-inicio',
      'id_persona' => '1095913073',
      'id_tipo_novedad' => 'tipo-permiso',
      'fecha_inicio' => '2026-04-21 10:00:00',
      'fecha_fin' => '2026-04-21 10:15:00',
      'estado' => 'APROBADO',
    ]);

    $service = new NovedadAsistenciaService();
    $resultado = $service->resolverEventoPermitido(
      '1095913073',
      Carbon::parse('2026-04-21 10:06:00')
    );

    $this->assertTrue($resultado['aplica']);
    $this->assertSame(1, $resultado['evento']);
    $this->assertSame('fecha_inicio', $resultado['ventana']);
  }

  public function test_en_solape_cerca_de_fecha_fin_prioriza_ingreso(): void
  {
    $this->insertarNovedad([
      'id' => 'nov-solape-fin',
      'id_persona' => '1095913073',
      'id_tipo_novedad' => 'tipo-permiso',
      'fecha_inicio' => '2026-04-21 10:00:00',
      'fecha_fin' => '2026-04-21 10:15:00',
      'estado' => 'APROBADO',
    ]);

    $service = new NovedadAsistenciaService();
    $resultado = $service->resolverEventoPermitido(
      '1095913073',
      Carbon::parse('2026-04-21 10:18:00')
    );

    $this->assertTrue($resultado['aplica']);
    $this->assertSame(2, $resultado['evento']);
    $this->assertSame('fecha_fin', $resultado['ventana']);
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

  private function crearTablasPruebaNovedades(): void
  {
    $schema = Schema::connection('oracle-360');

    $schema->dropIfExists('EMP_NOVEDADES');
    $schema->dropIfExists('EMP_NOVEDADES_TIPO');

    $schema->create('EMP_NOVEDADES_TIPO', function (Blueprint $table) {
      $table->string('id')->primary();
      $table->string('descripcion');
      $table->string('bloquea')->default('F');
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

  private function insertarCatalogoTipos(): void
  {
    DB::connection('oracle-360')->table('EMP_NOVEDADES_TIPO')->insert([
      ['id' => 'tipo-incapacidad', 'descripcion' => 'Incapacidad', 'bloquea' => 'F'],
      ['id' => 'tipo-permiso', 'descripcion' => 'Permiso', 'bloquea' => 'F'],
      ['id' => 'tipo-vacaciones', 'descripcion' => 'Vacaciones', 'bloquea' => 'F'],
    ]);
  }

  private function insertarNovedad(array $data): void
  {
    DB::connection('oracle-360')->table('EMP_NOVEDADES')->insert([
      'id' => $data['id'],
      'id_persona' => $data['id_persona'],
      'id_tipo_novedad' => $data['id_tipo_novedad'],
      'fecha_inicio' => $data['fecha_inicio'],
      'fecha_fin' => $data['fecha_fin'] ?? null,
      'estado' => $data['estado'] ?? 'APROBADO',
      'fecha_creacion' => '2026-04-21 00:00:00',
      'fecha_modifica' => null,
      'usuario_modifica' => null,
      'observacion' => null,
    ]);
  }
}
