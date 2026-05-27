<?php

namespace App\Modules\Huellero\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\GestionRRHH\Services\JefeEquipoService;
use App\Modules\Huellero\Models\PrsCargos;
use App\Modules\Huellero\Models\PrsCentroCosto;
use App\Modules\Huellero\Models\PrsHorariosCargos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CargoHorarioController extends Controller
{
  public function __construct(
    private readonly JefeEquipoService $jefeEquipoService
  ) {}

  public function index(Request $request)
  {
    $usuario = $request->user();
    $esSuperAdmin = $usuario
      && method_exists($usuario, 'hasRole')
      && $usuario->hasRole(User::SUPER_ADMIN_ROLE);
    $identificacionUsuario = trim((string) ($usuario?->persona?->PerNumDoc ?? ''));
    $jefesDisponibles = collect();

    if ($esSuperAdmin) {
      try {
        $jefesDisponibles = $this->obtenerJefesConPersonalACargo();
      } catch (Throwable $e) {
        $jefesDisponibles = collect();
      }
    }

    $identificacionJefe = $esSuperAdmin
      ? trim((string) $request->query('identificacion', $identificacionUsuario))
      : $identificacionUsuario;

    if ($esSuperAdmin && $identificacionJefe === '' && $jefesDisponibles->isNotEmpty()) {
      $identificacionJefe = (string) ($jefesDisponibles->first()['identificacion'] ?? '');
    }

    if (
      $esSuperAdmin
      && $identificacionJefe !== ''
      && $jefesDisponibles->isNotEmpty()
      && !$jefesDisponibles->contains(function (array $jefe) use ($identificacionJefe) {
        return (string) ($jefe['identificacion'] ?? '') === $identificacionJefe;
      })
    ) {
      $identificacionJefe = '';
    }

    $empleados = collect();
    $errores = [];
    $datosJefe = null;

    if ($identificacionJefe !== '') {
      try {
        $datosJefe = $this->obtenerInformacionJefe($identificacionJefe);
        $empleados = $this->obtenerEmpleadosPorJefe($identificacionJefe);
        $this->sincronizarCargosFaltantes($empleados);
      } catch (Throwable $e) {
        $errores[] = 'No se pudo consultar los empleados del jefe seleccionado.';
      }
    }

    $catalogoCargo = $this->resolverCatalogoCargos();
    $empleados = $empleados->map(function (array $empleado) use ($catalogoCargo) {
      $cargoNormalizado = $this->normalizarCargo((string) ($empleado['cargo_empleado'] ?? ''));
      $nombreCentroCosto = $this->formatearNombreCentroCosto(
        (string) ($empleado['codigo_centro_costo'] ?? ''),
        (string) ($empleado['centro_costo'] ?? '')
      );
      $centroNormalizado = $this->normalizarCentroCosto($nombreCentroCosto);
      $clave = $this->claveCargoCentro($cargoNormalizado, $centroNormalizado);

      $empleado['centro_costo_parametrizacion'] = $nombreCentroCosto;
      $empleado['cargo_id'] = $catalogoCargo[$clave]['id'] ?? null;

      return $empleado;
    });

    $cargosDisponibles = $empleados
      ->filter(function (array $empleado) {
        return !empty($empleado['cargo_id']);
      })
      ->groupBy('cargo_id')
      ->map(function (Collection $items) {
        $primero = $items->first();

        return [
          'id' => (int) $primero['cargo_id'],
          'nombre' => (string) ($primero['cargo_empleado'] ?? ''),
          'centro_costo' => (string) ($primero['centro_costo_parametrizacion'] ?? ''),
          'total_empleados' => $items->count(),
        ];
      })
      ->sortBy(function (array $cargo) {
        return $cargo['nombre'] . '|' . $cargo['centro_costo'];
      })
      ->values();

    if ($cargosDisponibles->isNotEmpty()) {
      $cargoIds = $cargosDisponibles->pluck('id')->map(fn($id) => (int) $id)->values()->all();
      $conteoHorarios = PrsHorariosCargos::query()
        ->selectRaw('cargo_id, COUNT(*) as total_horarios')
        ->whereIn('cargo_id', $cargoIds)
        ->groupBy('cargo_id')
        ->get()
        ->mapWithKeys(function ($item) {
          return [(int) $item->cargo_id => (int) $item->total_horarios];
        });

      $cargosDisponibles = $cargosDisponibles->map(function (array $cargo) use ($conteoHorarios) {
        $totalHorarios = (int) ($conteoHorarios[(int) $cargo['id']] ?? 0);
        $cargo['total_horarios'] = $totalHorarios;
        $cargo['tiene_horario'] = $totalHorarios > 0;

        return $cargo;
      })->values();
    }

    $cargoSeleccionadoId = (int) $request->query('cargo_id', 0);
    if ($cargoSeleccionadoId <= 0 && $cargosDisponibles->isNotEmpty()) {
      $cargoSeleccionadoId = (int) $cargosDisponibles->first()['id'];
    }

    $horarios = collect();
    if ($cargoSeleccionadoId > 0) {
      $horarios = PrsHorariosCargos::query()
        ->where('cargo_id', $cargoSeleccionadoId)
        ->orderBy('jornada')
        ->orderBy('id')
        ->get()
        ->map(function ($horario) {
          return [
            'id' => (int) $horario->id,
            'cargo_id' => $horario->cargo_id !== null ? (int) $horario->cargo_id : null,
            'jornada' => (int) $horario->jornada,
            'dia_inicio' => (int) $horario->dia_inicio,
            'dia_fin' => (int) $horario->dia_fin,
            'hora_inicio' => $this->horaAFormatoInput($horario->hora_inicio),
            'hora_fin' => $this->horaAFormatoInput($horario->hora_fin),
            'estado' => (int) $horario->estado,
          ];
        });
    }

    $horarioEdicionId = (int) $request->query('horario_id', 0);
    $horarioEdicion = $horarioEdicionId > 0
      ? $horarios->firstWhere('id', $horarioEdicionId)
      : null;

    return view('huellero::fingerprint.cargos_horarios', [
      'identificacionJefe' => $identificacionJefe,
      'empleados' => $empleados,
      'cargosDisponibles' => $cargosDisponibles,
      'cargoSeleccionadoId' => $cargoSeleccionadoId,
      'horarios' => $horarios,
      'horarioEdicion' => $horarioEdicion,
      'diasSemana' => $this->diasSemana(),
      'erroresConsulta' => $errores,
      'esSuperAdmin' => $esSuperAdmin,
      'jefesDisponibles' => $jefesDisponibles,
      'datosJefe' => $datosJefe,
    ]);
  }

  public function guardarHorario(Request $request)
  {
    $horarioId = (int) $request->input('horario_id', 0);
    $esEdicion = $horarioId > 0;

    $validator = $esEdicion
      ? Validator::make($request->all(), [
        'horario_id' => ['required', 'integer', 'min:1'],
        'identificacion' => ['nullable', 'string', 'max:50'],
        'cargo_id' => ['required', 'integer', 'min:1'],
        'jornada' => ['required', 'integer', 'min:1', 'max:10'],
        'dia_inicio' => ['required', 'integer', 'between:1,7'],
        'dia_fin' => ['required', 'integer', 'between:1,7'],
        'hora_inicio' => ['required', 'date_format:H:i'],
        'hora_fin' => ['required', 'date_format:H:i'],
        'estado' => ['required', 'in:0,1'],
      ])
      : Validator::make($request->all(), [
        'identificacion' => ['nullable', 'string', 'max:50'],
        'cargo_id' => ['required', 'integer', 'min:1'],
        'modo_jornadas' => ['required', 'in:1,2'],
        'dia_inicio_1' => ['required', 'integer', 'between:1,7'],
        'dia_fin_1' => ['required', 'integer', 'between:1,7'],
        'hora_inicio_1' => ['required', 'date_format:H:i'],
        'hora_fin_1' => ['required', 'date_format:H:i'],
        'dia_inicio_2' => ['required_if:modo_jornadas,2', 'nullable', 'integer', 'between:1,7'],
        'dia_fin_2' => ['required_if:modo_jornadas,2', 'nullable', 'integer', 'between:1,7'],
        'hora_inicio_2' => ['required_if:modo_jornadas,2', 'nullable', 'date_format:H:i'],
        'hora_fin_2' => ['required_if:modo_jornadas,2', 'nullable', 'date_format:H:i'],
      ]);

    if ($validator->fails()) {
      return back()->withErrors($validator)->withInput();
    }

    $payload = $validator->validated();
    $cargoId = (int) $payload['cargo_id'];
    $cargoExiste = PrsCargos::query()->where('id', $cargoId)->exists();
    if (!$cargoExiste) {
      return back()
        ->withErrors(['cargo_id' => 'El cargo seleccionado no existe en PRS_CARGOS.'])
        ->withInput();
    }

    if ($esEdicion) {
      $horaInicio = Carbon::createFromFormat('H:i', (string) $payload['hora_inicio']);
      $horaFin = Carbon::createFromFormat('H:i', (string) $payload['hora_fin']);
      if ($horaInicio->equalTo($horaFin)) {
        return back()
          ->withErrors(['hora_fin' => 'La hora fin no puede ser igual a la hora inicio.'])
          ->withInput();
      }
    } else {
      $horaInicio1 = Carbon::createFromFormat('H:i', (string) $payload['hora_inicio_1']);
      $horaFin1 = Carbon::createFromFormat('H:i', (string) $payload['hora_fin_1']);
      if ($horaInicio1->equalTo($horaFin1)) {
        return back()
          ->withErrors(['hora_fin_1' => 'La hora fin de la jornada 1 no puede ser igual a la hora inicio.'])
          ->withInput();
      }

      if ((string) ($payload['modo_jornadas'] ?? '1') === '2') {
        $horaInicio2 = Carbon::createFromFormat('H:i', (string) $payload['hora_inicio_2']);
        $horaFin2 = Carbon::createFromFormat('H:i', (string) $payload['hora_fin_2']);
        if ($horaInicio2->equalTo($horaFin2)) {
          return back()
            ->withErrors(['hora_fin_2' => 'La hora fin de la jornada 2 no puede ser igual a la hora inicio.'])
            ->withInput();
        }
      }
    }

    try {
      DB::connection('oracle-360')->transaction(function () use ($payload, $cargoId, $esEdicion) {
        $horarioId = isset($payload['horario_id']) ? (int) $payload['horario_id'] : 0;
        if ($horarioId > 0) {
          $horaInicio = Carbon::createFromFormat('H:i', (string) $payload['hora_inicio']);
          $horaFin = Carbon::createFromFormat('H:i', (string) $payload['hora_fin']);

          $horario = PrsHorariosCargos::query()->where('id', $horarioId)->lockForUpdate()->first();
          if (!$horario) {
            throw new \RuntimeException('El horario no existe.');
          }
          $horario->cargo_id = $cargoId;
          $horario->jornada = (int) $payload['jornada'];
          $horario->dia_inicio = (string) $payload['dia_inicio'];
          $horario->dia_fin = (string) $payload['dia_fin'];
          $horario->hora_inicio = $this->fechaConHora($horaInicio);
          $horario->hora_fin = $this->fechaConHora($horaFin);
          $horario->estado = (int) $payload['estado'];
          $horario->fecha_modificacion = now();
          $horario->save();
          return;
        }

        $modoJornadas = (int) ($payload['modo_jornadas'] ?? 1);
        $jornadas = [1];
        if ($modoJornadas === 2) {
          $jornadas[] = 2;
        }

        $ultimo = PrsHorariosCargos::query()->orderByDesc('id')->lockForUpdate()->first(['id']);
        $siguienteId = (int) ($ultimo->id ?? 0) + 1;

        foreach ($jornadas as $jornadaNumero) {
          $sufijo = (string) $jornadaNumero;
          $horaInicio = Carbon::createFromFormat('H:i', (string) $payload['hora_inicio_' . $sufijo]);
          $horaFin = Carbon::createFromFormat('H:i', (string) $payload['hora_fin_' . $sufijo]);

          $horario = new PrsHorariosCargos();
          $horario->id = $siguienteId++;
          $horario->cargo_id = $cargoId;
          $horario->jornada = $jornadaNumero;
          $horario->dia_inicio = (string) $payload['dia_inicio_' . $sufijo];
          $horario->dia_fin = (string) $payload['dia_fin_' . $sufijo];
          $horario->hora_inicio = $this->fechaConHora($horaInicio);
          $horario->hora_fin = $this->fechaConHora($horaFin);
          $horario->estado = 1;
          $horario->fecha_creacion = now();
          $horario->fecha_modificacion = now();
          $horario->save();
        }
      });
    } catch (Throwable $e) {
      return back()
        ->withErrors(['general' => 'No se pudo guardar el horario.'])
        ->withInput();
    }

    return redirect()
      ->route('fingerprint.cargos-horarios.index', [
        'identificacion' => $payload['identificacion'] ?? null,
        'cargo_id' => $cargoId,
      ])
      ->with('status', $esEdicion ? 'Horario guardado correctamente.' : 'Horarios guardados correctamente.');
  }

  public function cambiarEstadoHorario(Request $request, int $id)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['nullable', 'string', 'max:50'],
      'cargo_id' => ['required', 'integer', 'min:1'],
      'estado' => ['required', 'in:0,1'],
    ]);

    if ($validator->fails()) {
      return back()->withErrors($validator);
    }

    $payload = $validator->validated();

    try {
      DB::connection('oracle-360')->transaction(function () use ($id, $payload) {
        $horario = PrsHorariosCargos::query()->where('id', $id)->lockForUpdate()->first();
        if (!$horario) {
          throw new \RuntimeException('El horario no existe.');
        }

        $horario->estado = (int) $payload['estado'];
        $horario->fecha_modificacion = now();
        $horario->save();
      });
    } catch (Throwable $e) {
      return back()->withErrors(['general' => 'No se pudo actualizar el estado del horario.']);
    }

    return redirect()
      ->route('fingerprint.cargos-horarios.index', [
        'identificacion' => $payload['identificacion'] ?? null,
        'cargo_id' => (int) $payload['cargo_id'],
      ])
      ->with('status', 'Estado del horario actualizado.');
  }

  private function obtenerEmpleadosPorJefe(string $identificacion): Collection
  {
    return $this->jefeEquipoService->obtenerEmpleadosDirectos($identificacion);
  }

  private function obtenerInformacionJefe(string $identificacion): ?array
  {
    $row = $this->jefeEquipoService->obtenerInformacionJefe($identificacion);
    if (! $row) {
      return null;
    }

    return [
      'identificacion' => trim((string) ($row['identificacion'] ?? '')),
      'nombre' => trim((string) ($row['nombre'] ?? '')),
      'cargo' => trim((string) ($row['cargo'] ?? '')),
      'centro_costo' => $this->formatearNombreCentroCosto(
        trim((string) ($row['codigo_centro_costo'] ?? '')),
        trim((string) ($row['centro_costo'] ?? ''))
      ),
    ];
  }

  private function obtenerJefesConPersonalACargo(): Collection
  {
    return $this->jefeEquipoService->obtenerJefesConPersonalACargo();
  }

  private function sincronizarCargosFaltantes(Collection $empleados): void
  {
    $cargosRequeridos = $empleados
      ->map(function (array $empleado) {
        $cargoNombre = $this->normalizarCargo((string) ($empleado['cargo_empleado'] ?? ''));
        $centroNombre = $this->formatearNombreCentroCosto(
          (string) ($empleado['codigo_centro_costo'] ?? ''),
          (string) ($empleado['centro_costo'] ?? '')
        );
        $centroNormalizado = $this->normalizarCentroCosto($centroNombre);

        if ($cargoNombre === '' || $centroNormalizado === '') {
          return null;
        }

        return [
          'cargo_nombre' => $cargoNombre,
          'centro_nombre' => $centroNormalizado,
        ];
      })
      ->filter()
      ->unique(function (array $item) {
        return $this->claveCargoCentro($item['cargo_nombre'], $item['centro_nombre']);
      })
      ->values();

    if ($cargosRequeridos->isEmpty()) {
      return;
    }

    DB::connection('oracle-360')->transaction(function () use ($cargosRequeridos) {
      $centroIdPorNombre = $this->resolverCentrosCostoPorNombre();
      $centrosFaltantes = $cargosRequeridos
        ->pluck('centro_nombre')
        ->filter()
        ->unique()
        ->reject(function (string $centroNombre) use ($centroIdPorNombre) {
          return isset($centroIdPorNombre[$centroNombre]);
        })
        ->values();

      if ($centrosFaltantes->isNotEmpty()) {
        $ultimoCentro = PrsCentroCosto::query()
          ->orderByDesc('id')
          ->lockForUpdate()
          ->first(['id']);
        $siguienteCentroId = (int) ($ultimoCentro->id ?? 0) + 1;

        foreach ($centrosFaltantes as $centroNombre) {
          $centro = new PrsCentroCosto();
          $centro->id = $siguienteCentroId++;
          $centro->nombre = (string) $centroNombre;
          $centro->estado = 1;
          $centro->fecha_creacion = now();
          $centro->save();
        }

        $centroIdPorNombre = $this->resolverCentrosCostoPorNombre();
      }

      $catalogo = $this->resolverCatalogoCargos();
      $faltantes = $cargosRequeridos
        ->filter(function (array $item) use ($catalogo) {
          $clave = $this->claveCargoCentro($item['cargo_nombre'], $item['centro_nombre']);

          return !isset($catalogo[$clave]);
        })
        ->values();

      if ($faltantes->isEmpty()) {
        return;
      }

      $ultimo = PrsCargos::query()->orderByDesc('id')->lockForUpdate()->first(['id']);
      $siguienteId = (int) ($ultimo->id ?? 0) + 1;
      foreach ($faltantes as $item) {
        $centroCostoId = (int) ($centroIdPorNombre[$item['centro_nombre']] ?? 0);
        if ($centroCostoId <= 0) {
          continue;
        }

        $cargo = new PrsCargos();
        $cargo->id = $siguienteId++;
        $cargo->centro_costo_id = $centroCostoId;
        $cargo->nombre = (string) $item['cargo_nombre'];
        $cargo->estado = 1;
        $cargo->fecha_creacion = now();
        $cargo->save();
      }
    });
  }

  private function resolverCatalogoCargos(): array
  {
    $centroNombrePorId = PrsCentroCosto::query()
      ->orderBy('id')
      ->get(['id', 'nombre'])
      ->reduce(function (array $carry, $item) {
        $carry[(int) $item->id] = $this->normalizarCentroCosto((string) ($item->nombre ?? ''));

        return $carry;
      }, []);

    return PrsCargos::query()
      ->orderBy('id')
      ->get(['id', 'nombre', 'centro_costo_id'])
      ->reduce(function (array $carry, $item) use ($centroNombrePorId) {
        $cargoNormalizado = $this->normalizarCargo((string) ($item->nombre ?? ''));
        $centroNormalizado = $centroNombrePorId[(int) ($item->centro_costo_id ?? 0)] ?? '';
        $clave = $this->claveCargoCentro($cargoNormalizado, $centroNormalizado);

        if ($cargoNormalizado === '' || $centroNormalizado === '' || isset($carry[$clave])) {
          return $carry;
        }

        $carry[$clave] = [
          'id' => (int) $item->id,
          'nombre' => (string) $item->nombre,
          'centro_costo_id' => (int) $item->centro_costo_id,
        ];

        return $carry;
      }, []);
  }

  private function normalizarCargo(string $valor): string
  {
    $compactado = preg_replace('/\s+/', ' ', trim($valor));

    return mb_strtoupper((string) $compactado, 'UTF-8');
  }

  private function normalizarCentroCosto(string $valor): string
  {
    $compactado = preg_replace('/\s+/', ' ', trim($valor));

    return mb_strtoupper((string) $compactado, 'UTF-8');
  }

  private function formatearNombreCentroCosto(string $codigo, string $descripcion): string
  {
    $descripcionNormalizada = $this->normalizarCentroCosto($descripcion);

    if ($descripcionNormalizada === '') {
      return 'SIN CENTRO DE COSTO';
    }

    $nombreCentro = preg_replace('/^\d+\s*-\s*/u', '', $descripcionNormalizada);
    $nombreCentro = preg_replace('/\bDE\b/u', ' ', (string) $nombreCentro);
    $nombreCentro = preg_replace('/\s+/', ' ', trim((string) $nombreCentro));

    if ($nombreCentro === '') {
      $nombreCentro = $descripcionNormalizada;
    }

    return mb_substr($nombreCentro, 0, 150, 'UTF-8');
  }

  private function claveCargoCentro(string $cargoNormalizado, string $centroNormalizado): string
  {
    return $cargoNormalizado . '|' . $centroNormalizado;
  }

  private function resolverCentrosCostoPorNombre(): array
  {
    return PrsCentroCosto::query()
      ->orderBy('id')
      ->get(['id', 'nombre'])
      ->reduce(function (array $carry, $item) {
        $normalizado = $this->normalizarCentroCosto((string) ($item->nombre ?? ''));
        if ($normalizado === '' || isset($carry[$normalizado])) {
          return $carry;
        }

        $carry[$normalizado] = (int) $item->id;

        return $carry;
      }, []);
  }

  private function fechaConHora(Carbon $hora): Carbon
  {
    return now()->copy()->setTime(
      (int) $hora->format('H'),
      (int) $hora->format('i'),
      0
    );
  }

  private function horaAFormatoInput(mixed $valor): ?string
  {
    if (!$valor) {
      return null;
    }

    try {
      return Carbon::parse((string) $valor)->format('H:i');
    } catch (Throwable $e) {
      return null;
    }
  }

  private function diasSemana(): array
  {
    return [
      1 => 'Lunes',
      2 => 'Martes',
      3 => 'Miercoles',
      4 => 'Jueves',
      5 => 'Viernes',
      6 => 'Sabado',
      7 => 'Domingo',
    ];
  }
}
