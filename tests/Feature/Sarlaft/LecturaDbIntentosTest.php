<?php

declare(strict_types=1);

namespace Tests\Feature\Sarlaft;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\IntentoOperacion;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use App\Modules\Sarlaft\Services\IntentoOperacionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LecturaDbIntentosTest extends TestCase
{
    private IntentoOperacionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // BD propia de SARLAFT (sqlite en memoria).
        config([
            'database.connections.mysql-sarlaft' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            // Conexion externa simulada (hace de "Oracle" inhouse).
            'database.connections.oracle-fake' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('mysql-sarlaft');
        DB::purge('oracle-fake');
        DB::connection('mysql-sarlaft')->getPdo();
        DB::connection('oracle-fake')->getPdo();

        $this->crearEsquemaSarlaft();
        $this->crearEsquemaOrigenExterno();

        $this->service = app(IntentoOperacionService::class);
    }

    public function test_lee_operaciones_de_la_bd_externa_y_genera_intentos_con_alertas(): void
    {
        $sistema = $this->crearSistemaDb();

        $this->insertarOperacionExterna([
            'TIPO_DOCUMENTO' => 'NIT',
            'NUMERO_DOCUMENTO' => '900123456-7',
            'NOMBRE' => 'TRANSPORTES EJEMPLO SAS',
            'TIPO_LISTA' => 'vinculante',
            'LISTA_NOMBRE' => 'OFAC SDN',
            'TIPO_OPERACION' => 'encomienda',
            'REFERENCIA' => 'ENC-2026-055',
            'SISTEMA_ORIGEN' => 'Logtrans',
        ]);

        $registrados = $this->service->ejecutarLecturaDb($sistema);

        $this->assertSame(1, $registrados);

        $intento = IntentoOperacion::query()->first();
        $this->assertNotNull($intento);
        $this->assertSame('db', $intento->modo_integracion);
        $this->assertSame('900123456-7', $intento->numero_documento);
        $this->assertSame('OFAC SDN', $intento->lista_nombre);
        $this->assertSame('ENC-2026-055', $intento->referencia);

        // La coincidencia vinculante genera alerta con nivel 'vinculante'.
        $alerta = Alerta::query()->first();
        $this->assertNotNull($alerta);
        $this->assertSame('vinculante', $alerta->nivel_riesgo);
        $this->assertSame($intento->id, $alerta->intento_id);
    }

    public function test_no_reprocesa_la_misma_fila_en_corridas_sucesivas(): void
    {
        $sistema = $this->crearSistemaDb();

        $this->insertarOperacionExterna([
            'REFERENCIA' => null,
            'SISTEMA_ORIGEN' => 'Logtrans',
        ]);

        $primera = $this->service->ejecutarLecturaDb($sistema);
        $sistema->refresh();
        $segunda = $this->service->ejecutarLecturaDb($sistema);

        // La fila se procesa una sola vez aunque el comando corra de nuevo,
        // gracias al control por ID (db_ultimo_id), no por referencia.
        $this->assertSame(1, $primera);
        $this->assertSame(0, $segunda);
        $this->assertSame(1, IntentoOperacion::query()->count());
    }

    public function test_conserva_todos_los_intentos_repetidos_del_usuario(): void
    {
        $sistema = $this->crearSistemaDb();

        // Misma persona intenta 3 veces (3 filas distintas, sin referencia).
        foreach (range(1, 3) as $i) {
            $this->insertarOperacionExterna([
                'REFERENCIA' => null,
                'NUMERO_DOCUMENTO' => '900123456-7',
                'SISTEMA_ORIGEN' => 'Logtrans',
            ]);
        }

        $registrados = $this->service->ejecutarLecturaDb($sistema);

        // Los 3 intentos quedan: son filas distintas (IDs distintos), no duplicados falsos.
        $this->assertSame(3, $registrados);
        $this->assertSame(3, IntentoOperacion::query()->count());
    }

    public function test_clasifica_vinculante_por_nombre_de_lista_aunque_tipo_lista_sea_generico(): void
    {
        config([
            'listas' => [
                'ofac_sdn' => [
                    'nombre' => 'OFAC SDN',
                    'url' => 'https://ejemplo.local/ofac.xml',
                    'parser' => 'ofac',
                ],
            ],
        ]);

        $sistema = $this->crearSistemaDb();

        // tipo_lista generico ('persona') pero lista_nombre es OFAC (vinculante).
        $this->insertarOperacionExterna([
            'REFERENCIA' => null,
            'TIPO_LISTA' => 'persona',
            'LISTA_NOMBRE' => 'OFAC SDN',
            'SISTEMA_ORIGEN' => 'Logtrans',
        ]);

        $this->service->ejecutarLecturaDb($sistema);

        $alerta = Alerta::query()->first();
        $this->assertNotNull($alerta);
        $this->assertSame('vinculante', $alerta->nivel_riesgo);
    }

    public function test_parsea_el_contexto_clave_valor_a_array_estructurado(): void
    {
        $sistema = $this->crearSistemaDb();

        $this->insertarOperacionExterna([
            'REFERENCIA' => null,
            'SISTEMA_ORIGEN' => 'Logtrans',
            'CONTEXTO' => 'evento=CONSULTAR_PAGOS_RECAUDOS|empresa=COMFACESAR - PAGOS|agencia=005 - AGUACHICA PASAJES|usuario=ANDREA JIMENEZ|resultadoConsulta=coincidencia=true\|listaId=OFAC SDN',
        ]);

        $this->service->ejecutarLecturaDb($sistema);

        $intento = IntentoOperacion::query()->first();
        $this->assertNotNull($intento);
        $this->assertIsArray($intento->contexto);
        $this->assertSame('CONSULTAR_PAGOS_RECAUDOS', $intento->contexto['evento']);
        $this->assertSame('COMFACESAR - PAGOS', $intento->contexto['empresa']);
        $this->assertSame('ANDREA JIMENEZ', $intento->contexto['usuario']);
        // El '\|' escapado se preserva como '|' literal dentro del valor.
        $this->assertSame('coincidencia=true|listaId=OFAC SDN', $intento->contexto['resultadoConsulta']);
    }

    public function test_solo_lee_filas_con_id_mayor_al_ultimo_procesado(): void
    {
        $sistema = $this->crearSistemaDb();

        $this->insertarOperacionExterna(['REFERENCIA' => null, 'SISTEMA_ORIGEN' => 'Logtrans']);
        $this->service->ejecutarLecturaDb($sistema);
        $sistema->refresh();

        // Llega una fila nueva (ID mayor) despues de la primera corrida.
        $this->insertarOperacionExterna(['REFERENCIA' => null, 'SISTEMA_ORIGEN' => 'Logtrans']);

        $registrados = $this->service->ejecutarLecturaDb($sistema);

        $this->assertSame(1, $registrados);
        $this->assertSame(2, IntentoOperacion::query()->count());
    }

    public function test_filtra_por_sistema_origen_cuando_la_tabla_es_compartida(): void
    {
        $sistema = $this->crearSistemaDb(['db_filtro_sistema_origen' => 'Odin']);

        $this->insertarOperacionExterna(['REFERENCIA' => 'R-LOG', 'SISTEMA_ORIGEN' => 'Logtrans']);
        $this->insertarOperacionExterna(['REFERENCIA' => 'R-ODIN', 'SISTEMA_ORIGEN' => 'Odin']);

        $registrados = $this->service->ejecutarLecturaDb($sistema);

        $this->assertSame(1, $registrados);
        $this->assertSame('R-ODIN', IntentoOperacion::query()->value('referencia'));
    }

    public function test_descarta_filas_sin_campos_minimos(): void
    {
        $sistema = $this->crearSistemaDb();

        // Sin tipo_lista ni lista_nombre -> no cumple campos minimos.
        $this->insertarOperacionExterna([
            'REFERENCIA' => 'R-INCOMPLETA',
            'TIPO_LISTA' => null,
            'LISTA_NOMBRE' => null,
            'SISTEMA_ORIGEN' => 'Logtrans',
        ]);

        $registrados = $this->service->ejecutarLecturaDb($sistema);

        $this->assertSame(0, $registrados);
        $this->assertSame(0, IntentoOperacion::query()->count());
    }

    public function test_actualiza_la_marca_de_ultima_lectura(): void
    {
        $sistema = $this->crearSistemaDb();
        $this->assertNull($sistema->db_ultima_lectura_at);

        $this->service->ejecutarLecturaDb($sistema);
        $sistema->refresh();

        $this->assertNotNull($sistema->db_ultima_lectura_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearSistemaDb(array $overrides = []): SistemaConsumidor
    {
        return SistemaConsumidor::query()->create(array_merge([
            'nombre' => 'Logtrans',
            'codigo' => 'logtrans',
            'api_token' => 'token-test',
            'estado' => 'activo',
            'modo_integracion' => 'db',
            'limite_requests_minuto' => 100,
            'db_conexion' => 'oracle-fake',
            'db_tabla' => 'sarlaft_operaciones',
            'db_filtro_sistema_origen' => null,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function insertarOperacionExterna(array $datos): void
    {
        DB::connection('oracle-fake')->table('sarlaft_operaciones')->insert(array_merge([
            'TIPO_DOCUMENTO' => 'NIT',
            'NUMERO_DOCUMENTO' => '900123456-7',
            'NOMBRE' => 'TRANSPORTES EJEMPLO SAS',
            'TIPO_LISTA' => 'restrictiva',
            'LISTA_NOMBRE' => 'copetran',
            'TIPO_OPERACION' => 'encomienda',
            'REFERENCIA' => 'REF-0001',
            'MONTO' => 45000,
            'DESCRIPCION' => 'Envio encomienda',
            'CONTEXTO' => null,
            'CREATED_AT' => now()->subHour()->toDateTimeString(),
            'SISTEMA_ORIGEN' => 'Logtrans',
        ], $datos));
    }

    private function crearEsquemaSarlaft(): void
    {
        Schema::connection('mysql-sarlaft')->create('sarlaft_sistemas_consumidores', static function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50);
            $table->string('api_token', 100)->nullable();
            $table->string('estado', 20)->default('activo');
            $table->string('modo_integracion', 20)->default('pull');
            $table->integer('limite_requests_minuto')->default(100);
            $table->string('pull_endpoint', 500)->nullable();
            $table->string('pull_token', 255)->nullable();
            $table->string('db_conexion', 100)->nullable();
            $table->string('db_tabla', 150)->nullable();
            $table->string('db_filtro_sistema_origen', 100)->nullable();
            $table->timestamp('db_ultima_lectura_at')->nullable();
            $table->unsignedBigInteger('db_ultimo_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql-sarlaft')->create('sarlaft_intentos_operacion', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sistema_id')->nullable();
            $table->string('sistema_origen')->nullable();
            $table->string('modo_integracion', 20)->default('push');
            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('nombre')->nullable();
            $table->string('tipo_lista')->nullable();
            $table->string('lista_nombre')->nullable();
            $table->string('tipo_operacion')->nullable();
            $table->string('referencia')->nullable();
            $table->string('referencia_externa')->nullable();
            $table->timestamp('fecha_operacion')->nullable();
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
            $table->decimal('monto', 18, 2)->nullable();
            $table->string('moneda')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('contexto')->nullable();
            $table->string('ip_origen')->nullable();
            $table->timestamps();
        });

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

    private function crearEsquemaOrigenExterno(): void
    {
        Schema::connection('oracle-fake')->create('sarlaft_operaciones', static function (Blueprint $table): void {
            $table->id('ID');
            $table->string('TIPO_DOCUMENTO', 20)->nullable();
            $table->string('NUMERO_DOCUMENTO', 50)->nullable();
            $table->string('NOMBRE', 200)->nullable();
            $table->string('TIPO_LISTA', 100)->nullable();
            $table->string('LISTA_NOMBRE', 200)->nullable();
            $table->string('TIPO_OPERACION', 50)->nullable();
            $table->string('REFERENCIA', 150)->nullable();
            $table->decimal('MONTO', 18, 2)->nullable();
            $table->text('DESCRIPCION')->nullable();
            $table->text('CONTEXTO')->nullable();
            $table->timestamp('CREATED_AT')->nullable();
            $table->string('SISTEMA_ORIGEN', 20)->nullable();
        });
    }
}
