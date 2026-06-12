<?php

namespace App\Modules\Camara\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Camara\Http\Requests\EnrollRequest;
use App\Modules\Camara\Http\Requests\RecognizeLiveRequest;
use App\Modules\Camara\Http\Requests\RecognizeRequest;
use App\Modules\Camara\Http\Requests\VerifyLiveRequest;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use App\Services\Asistencia\LiveAsistenciaEventFeedService;
use Carbon\Carbon;
use App\Modules\Camara\Services\CameraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CamaraApiController extends Controller
{
  public function __construct(
    private readonly CameraService $cameraService,
    private readonly RegistrarEventoEmpleadoService $registrarEventoEmpleadoService,
    private readonly LiveAsistenciaEventFeedService $liveAsistenciaEventFeedService
  ) {
  }

  public function health()
  {
    try {
      $response = $this->cameraService->health();
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function sessionKeepalive(Request $request)
  {
    // Fuerza escritura de actividad para extender la sesion en vistas de camara.
    $request->session()->put('camara_last_keepalive_at', now()->timestamp);

    return response()->json([
      'ok' => true,
      'timestamp' => now()->toIso8601String(),
    ]);
  }

  public function recognize(RecognizeRequest $request)
  {
    $file = $request->file('image');
    $identCrea = $request->user()?->persona?->PerNumDoc;

    try {
      $response = $this->cameraService->recognize($file, $identCrea);
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    if ($response->failed()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Servicio no disponible.',
      ], $response->status());
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function enroll(EnrollRequest $request)
  {
    $files = $request->file('images', []);
    $identificacion = trim((string) $request->input('identificacion'));
    $usrcreacion = $request->user()?->persona?->PerNumDoc;
    $identCrea = $usrcreacion;

    try {
      $response = $this->cameraService->enroll($files, $identificacion, $usrcreacion, $identCrea);
    } catch (\Throwable $e) {
      logger()->error('Camara enroll: no se pudo contactar el servicio', [
        'error' => $e->getMessage(),
        'class' => get_class($e),
      ]);

      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    if ($response->failed()) {
      logger()->warning('Camara enroll: API respondio error', [
        'status' => $response->status(),
      ]);

      $contentType = $response->header('Content-Type', '');
      if (str_contains($contentType, 'application/json')) {
        return response()->json(
          $response->json() ?? ['status' => 'error', 'message' => 'Error en API'],
          $response->status()
        );
      }

      return response($response->body(), $response->status())
        ->header('Content-Type', $contentType ?: 'text/plain');
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function recognizeLive(RecognizeLiveRequest $request)
  {
    $files = $request->file('images', []);
    $usrcreacion = $request->user()?->persona?->PerNumDoc;
    $identCrea = $usrcreacion;

    try {
      $response = $this->cameraService->recognizeBatch($files, $usrcreacion, $identCrea);
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    if ($response->failed()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Servicio no disponible.',
      ], $response->status());
    }

    $payload = $response->json();
    if (!is_array($payload) || !is_array($payload['personas'] ?? null)) {
      return response($response->body(), $response->status())
        ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    $personasProcesadas = [];
    $rechazados = [];
    $procesados = [];

    foreach ($payload['personas'] as $persona) {
      if (!is_array($persona)) {
        continue;
      }

      $identificacion = trim((string) ($persona['identificacion'] ?? ''));
      if ($identificacion === '') {
        continue;
      }

      if (isset($procesados[$identificacion])) {
        continue;
      }
      $procesados[$identificacion] = true;

      $resultado = $this->registrarEventoEmpleadoService->registrar(
        $identificacion,
        null,
        now(),
        $request->user()?->persona?->PerNumDoc,
        $request->user()?->IdUsuario,
        'camara'
      );

      if (($resultado['ok'] ?? false) !== true) {
        $rechazados[] = [
          'identificacion' => $identificacion,
          'motivo' => $resultado['motivo'] ?? 'No se pudo registrar el evento.',
        ];
        continue;
      }

      $fechaRegistro = null;
      if (!empty($resultado['fecha_evento'])) {
        try {
          $fechaRegistro = Carbon::parse($resultado['fecha_evento']);
        } catch (\Throwable $e) {
          $fechaRegistro = null;
        }
      }

      $persona['evento'] = (int) ($resultado['evento'] ?? 0);
      $persona['fecha_registro'] = $fechaRegistro
        ? $fechaRegistro->format('d/m/Y')
        : (string) ($persona['fecha_registro'] ?? '');
      $persona['hora_registro'] = $fechaRegistro
        ? $fechaRegistro->format('g:i a')
        : (string) ($persona['hora_registro'] ?? '');
      $persona['horario_cargo_id'] = $resultado['horario_cargo_id'] ?? null;
      $persona['cargo_id'] = $resultado['cargo_id'] ?? null;
      $persona['flags'] = $resultado['flags'] ?? [
        'llegada_tarde' => false,
        'cargo_especial' => false,
      ];

      $personasProcesadas[] = $persona;
    }

    $payload['personas'] = $personasProcesadas;
    if (!empty($rechazados)) {
      $payload['rechazados'] = $rechazados;
    }

    return response()->json($payload, $response->status());
  }

  public function verifyLive(VerifyLiveRequest $request)
  {
    $files = $request->file('images', []);
    $usrcreacion = $request->user()?->persona?->PerNumDoc;
    $identCrea = $usrcreacion;

    try {
      $response = $this->cameraService->verifyBatch($files, $usrcreacion, $identCrea);
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    if ($response->failed()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Servicio no disponible.',
      ], $response->status());
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function ipSnapshot(Request $request)
  {
    $cameraId = trim((string) $request->query('camera_id', ''));
    if ($cameraId === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'Debe enviar camera_id.',
      ], 422);
    }

    $camera = $this->findConfiguredIpCamera($cameraId);
    if ($camera === null) {
      return response()->json([
        'status' => 'error',
        'message' => 'Camara IP no encontrada o deshabilitada.',
      ], 404);
    }

    $rtspUrl = trim((string) ($camera['rtsp_url'] ?? ''));
    if ($rtspUrl === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'La camara no tiene RTSP configurada.',
      ], 422);
    }

    $ffmpegBinary = trim((string) config('camera.ip_ffmpeg_binary', 'ffmpeg'));
    if ($ffmpegBinary === '') {
      $ffmpegBinary = 'ffmpeg';
    }
    $timeoutSeconds = (int) config('camera.ip_snapshot_timeout_seconds', 8);
    $timeoutSeconds = max(2, min($timeoutSeconds, 30));
    $tcpResult = $this->runFfmpegSnapshot($ffmpegBinary, $rtspUrl, $timeoutSeconds, 'tcp');
    if ($tcpResult['ok'] === true) {
      return $this->jpegSnapshotResponse((string) $tcpResult['output'], 'tcp');
    }

    $stderrLower = mb_strtolower((string) ($tcpResult['stderr'] ?? ''));
    if (str_contains($stderrLower, 'error number -10106')) {
      // Fallback pragmático: algunos equipos/cámaras funcionan por UDP aunque TCP falle.
      $udpResult = $this->runFfmpegSnapshot($ffmpegBinary, $rtspUrl, $timeoutSeconds, 'udp');
      if ($udpResult['ok'] === true) {
        logger()->info('Camara IP snapshot: fallback a UDP exitoso', [
          'camera_id' => $cameraId,
          'sapi' => php_sapi_name(),
        ]);

        return $this->jpegSnapshotResponse((string) $udpResult['output'], 'udp');
      }
    }

    logger()->warning('Camara IP snapshot fallo ffmpeg', [
      'camera_id' => $cameraId,
      'ffmpeg_binary' => $ffmpegBinary,
      'exit_code' => $tcpResult['exit_code'] ?? null,
      'stderr_excerpt' => mb_substr((string) ($tcpResult['stderr'] ?? ''), 0, 400),
      'transport' => 'tcp',
      'sapi' => php_sapi_name(),
    ]);

    if (($tcpResult['timed_out'] ?? false) === true) {
      return response()->json([
        'status' => 'error',
        'message' => 'Timeout al consultar la camara IP.',
      ], 504);
    }

    $stderr = trim((string) ($tcpResult['stderr'] ?? ''));
    $stderrExcerpt = mb_substr($stderr, 0, 400);
    $lowerStderr = mb_strtolower($stderr);

    if (str_contains($lowerStderr, 'error number -10106')) {
      return response()->json([
        'status' => 'error',
        'message' => 'El proceso web abre el puerto RTSP, pero ffmpeg falla al iniciar el socket de red (error -10106). Prueba ejecutar con Apache/XAMPP o revisar Winsock del equipo.',
        'detail' => app()->isLocal() ? $stderrExcerpt : null,
      ], 503);
    }

    if (
      str_contains($lowerStderr, 'not recognized as an internal or external command') ||
      str_contains($lowerStderr, 'no such file or directory') ||
      str_contains($lowerStderr, 'not found')
    ) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se encontro ffmpeg. Configura CAMERA_IP_FFMPEG_BINARY con la ruta completa del ejecutable.',
      ], 503);
    }

    if (str_contains($lowerStderr, '401 unauthorized')) {
      return response()->json([
        'status' => 'error',
        'message' => 'Credenciales RTSP invalidas para la camara IP.',
      ], 503);
    }

    if (
      str_contains($lowerStderr, 'connection timed out') ||
      str_contains($lowerStderr, 'timed out')
    ) {
      return response()->json([
        'status' => 'error',
        'message' => 'Timeout de conexion RTSP hacia la camara IP.',
      ], 504);
    }

    if (
      str_contains($lowerStderr, 'connection refused') ||
      str_contains($lowerStderr, 'no route to host') ||
      str_contains($lowerStderr, 'network is unreachable')
    ) {
      return response()->json([
        'status' => 'error',
        'message' => 'No hay conectividad de red hacia la camara IP.',
      ], 503);
    }

    return response()->json([
      'status' => 'error',
      'message' => 'No se pudo capturar imagen desde la camara IP.',
      'detail' => app()->isLocal() ? $stderrExcerpt : null,
    ], 503);
  }

  public function ipMjpegStream(Request $request)
  {
    $cameraId = trim((string) $request->query('camera_id', ''));
    if ($cameraId === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'Debe enviar camera_id.',
      ], 422);
    }

    $camera = $this->findConfiguredIpCamera($cameraId);
    if ($camera === null) {
      return response()->json([
        'status' => 'error',
        'message' => 'Camara IP no encontrada o deshabilitada.',
      ], 404);
    }

    $rtspUrl = trim((string) ($camera['rtsp_url'] ?? ''));
    if ($rtspUrl === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'La camara no tiene RTSP configurada.',
      ], 422);
    }

    $ffmpegBinary = trim((string) config('camera.ip_ffmpeg_binary', 'ffmpeg'));
    if ($ffmpegBinary === '') {
      $ffmpegBinary = 'ffmpeg';
    }

    $timeoutSeconds = (int) config('camera.ip_snapshot_timeout_seconds', 8);
    $timeoutSeconds = max(2, min($timeoutSeconds, 30));

    $transport = strtolower(trim((string) config('camera.ip_stream_transport', 'tcp')));
    if (!in_array($transport, ['tcp', 'udp'], true)) {
      $transport = 'tcp';
    }

    $fps = (int) config('camera.ip_stream_fps', 12);
    $fps = max(1, min($fps, 30));

    $quality = (int) config('camera.ip_stream_quality', 6);
    $quality = max(2, min($quality, 31));

    return response()->stream(function () use (
      $cameraId,
      $ffmpegBinary,
      $rtspUrl,
      $transport,
      $fps,
      $quality,
      $timeoutSeconds
    ) {
      @set_time_limit(0);
      @ini_set('zlib.output_compression', '0');
      @ini_set('output_buffering', 'off');
      @ini_set('implicit_flush', '1');

      while (ob_get_level() > 0) {
        @ob_end_flush();
      }
      @ob_implicit_flush(true);

      $process = new Process(
        [
          $ffmpegBinary,
          '-hide_banner',
          '-loglevel',
          'error',
          '-rtsp_transport',
          $transport,
          '-fflags',
          'nobuffer',
          '-flags',
          'low_delay',
          '-analyzeduration',
          '0',
          '-probesize',
          '32768',
          '-i',
          $rtspUrl,
          '-an',
          '-vf',
          'fps=' . $fps,
          '-q:v',
          (string) $quality,
          '-f',
          'mpjpeg',
          '-boundary_tag',
          'frame',
          'pipe:1',
        ],
        base_path(),
        $this->buildFfmpegEnv()
      );

      $process->setTimeout(null);
      $process->setIdleTimeout(null);

      $stderr = '';
      $process->start(function (string $type, string $buffer) use (&$stderr) {
        if ($type === Process::ERR) {
          $stderr .= $buffer;
          return;
        }

        echo $buffer;
        @flush();
      });

      // Espera un primer periodo corto para detectar fallos inmediatos.
      $firstWaitDeadline = microtime(true) + $timeoutSeconds;
      while ($process->isRunning() && microtime(true) < $firstWaitDeadline) {
        if (connection_aborted()) {
          $process->stop(1);
          return;
        }
        usleep(100000);
      }

      if (!$process->isRunning() && !$process->isSuccessful()) {
        logger()->warning('Camara IP stream fallo al iniciar', [
          'camera_id' => $cameraId,
          'transport' => $transport,
          'exit_code' => $process->getExitCode(),
          'stderr_excerpt' => mb_substr($this->sanitizeRtspMessage($stderr), 0, 500),
          'sapi' => php_sapi_name(),
        ]);
        return;
      }

      while ($process->isRunning()) {
        if (connection_aborted()) {
          $process->stop(1);
          break;
        }
        usleep(200000);
      }

      if (!$process->isSuccessful() && !connection_aborted()) {
        logger()->warning('Camara IP stream finalizo con error', [
          'camera_id' => $cameraId,
          'transport' => $transport,
          'exit_code' => $process->getExitCode(),
          'stderr_excerpt' => mb_substr($this->sanitizeRtspMessage($stderr), 0, 500),
          'sapi' => php_sapi_name(),
        ]);
      }
    }, 200, [
      'Content-Type' => 'multipart/x-mixed-replace; boundary=frame',
      'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
      'Pragma' => 'no-cache',
      'Expires' => '0',
      'X-Accel-Buffering' => 'no',
    ]);
  }

  public function ipDiagnostic(Request $request)
  {
    $cameraId = trim((string) $request->query('camera_id', ''));
    if ($cameraId === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'Debe enviar camera_id.',
      ], 422);
    }

    $camera = $this->findConfiguredIpCamera($cameraId);
    if ($camera === null) {
      return response()->json([
        'status' => 'error',
        'message' => 'Camara IP no encontrada o deshabilitada.',
      ], 404);
    }

    $rtspUrl = trim((string) ($camera['rtsp_url'] ?? ''));
    if ($rtspUrl === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'La camara no tiene RTSP configurada.',
      ], 422);
    }

    $ffmpegBinary = trim((string) config('camera.ip_ffmpeg_binary', 'ffmpeg'));
    if ($ffmpegBinary === '') {
      $ffmpegBinary = 'ffmpeg';
    }

    $timeoutSeconds = (int) config('camera.ip_snapshot_timeout_seconds', 8);
    $timeoutSeconds = max(2, min($timeoutSeconds, 30));

    $parsedRtsp = parse_url($rtspUrl);
    $host = (string) ($parsedRtsp['host'] ?? '');
    $port = isset($parsedRtsp['port']) ? (int) $parsedRtsp['port'] : 554;

    $tcp = [
      'host' => $host,
      'port' => $port,
      'ok' => false,
      'error_no' => null,
      'error_text' => null,
    ];

    if ($host !== '' && $port > 0) {
      $errno = 0;
      $errstr = '';
      $socket = @fsockopen($host, $port, $errno, $errstr, 3.0);
      if ($socket !== false) {
        $tcp['ok'] = true;
        fclose($socket);
      } else {
        $tcp['error_no'] = $errno;
        $tcp['error_text'] = $errstr !== '' ? $errstr : null;
      }
    }

    $ffmpegResultTcp = $this->runFfmpegSnapshot($ffmpegBinary, $rtspUrl, $timeoutSeconds, 'tcp');
    $ffmpegResultUdp = $this->runFfmpegSnapshot($ffmpegBinary, $rtspUrl, $timeoutSeconds, 'udp');
    $ffmpegEnv = $this->buildFfmpegEnv();

    return response()->json([
      'status' => 'ok',
      'camera_id' => $cameraId,
      'sapi' => php_sapi_name(),
      'php_binary' => PHP_BINARY,
      'username' => getenv('USERNAME') ?: null,
      'computername' => getenv('COMPUTERNAME') ?: null,
      'system_root' => getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: null,
      'ffmpeg_binary' => $ffmpegBinary,
      'timeout_seconds' => $timeoutSeconds,
      'tcp_check' => $tcp,
      'ffmpeg_env_keys' => array_values(array_keys($ffmpegEnv)),
      'ffmpeg_env_probe' => [
        'SystemRoot' => $ffmpegEnv['SystemRoot'] ?? $ffmpegEnv['SYSTEMROOT'] ?? null,
        'WINDIR' => $ffmpegEnv['WINDIR'] ?? null,
        'ComSpec' => $ffmpegEnv['ComSpec'] ?? $ffmpegEnv['COMSPEC'] ?? null,
        'Path_exists' => isset($ffmpegEnv['Path']) || isset($ffmpegEnv['PATH']),
      ],
      'ffmpeg_check_tcp' => $ffmpegResultTcp,
      'ffmpeg_check_udp' => $ffmpegResultUdp,
      'rtsp_sanitized' => $this->sanitizeRtspMessage($rtspUrl),
    ]);
  }

  public function personas(Request $request)
  {
    $query = trim((string) $request->query('query', ''));
    $query = preg_replace('/[^\pL\pN\s]/u', '', $query);
    $query = trim((string) preg_replace('/\s+/', ' ', $query));
    if ($query === '') {
      return response()->json([]);
    }

    $personasQuery = PerPersonas::query()
      ->where('tipdocumento', 1)
      ->where('estado', 'ACTIVO')
      ->where('estborrado', 0);

    // Para busqueda por documento, usar prefijo permite aprovechar mejor indices.
    if (preg_match('/^\d+$/', $query)) {
      $personasQuery->where('identificacion', 'like', $query . '%');
    } else {
      $personasQuery->where(function ($q) use ($query) {
        $q->where('pnombre', 'like', '%' . $query . '%')
          ->orWhere('snombre', 'like', '%' . $query . '%')
          ->orWhere('papellido', 'like', '%' . $query . '%')
          ->orWhere('sapellido', 'like', '%' . $query . '%');
      });
    }

    $personas = $personasQuery
      ->orderBy('identificacion')
      ->limit(20)
      ->get(['id', 'identificacion', 'pnombre', 'snombre', 'papellido', 'sapellido']);

    $data = $personas->map(function ($persona) {
      $nombre = trim(
        trim((string) $persona->pnombre . ' ' . (string) $persona->snombre) . ' ' .
          trim((string) $persona->papellido . ' ' . (string) $persona->sapellido)
      );
      $documento = (string) $persona->identificacion;

      return [
        'id' => $persona->id,
        'text' => $documento . ' - ' . $nombre,
        'documento' => $documento,
        'nombre' => $nombre,
      ];
    });

    return response()->json($data);
  }

  public function ultimosEventos()
  {
    $limit = (int) request()->query('limit', 10);
    if ($limit <= 0) {
      $limit = 10;
    }
    if ($limit > 100) {
      $limit = 100;
    }

    $rows = DB::connection('oracle-360')
      ->table('PRS_EVENTOS as e')
      ->leftJoin('PRS_PERSONAS as p', 'p.numero_documento', '=', 'e.identificacion')
      ->whereIn('e.evento', [1, 2])
      ->orderByDesc('e.fecha_creacion')
      ->limit($limit)
      ->get([
        'e.id as evento_id',
        'e.identificacion',
        'e.evento',
        'e.descripcion',
        'e.fecha_creacion',
        'p.NOMBRES as nombres',
        'p.PRIMER_APELLIDO as primer_apellido',
        'p.SEGUNDO_APELLIDO as segundo_apellido',
      ]);

    $data = $this->mapEventoRows($rows);

    return response()->json([
      'ok' => true,
      'data' => $data,
    ]);
  }

  public function ultimosEventosStream(Request $request)
  {
    $rawLastEventId = trim((string) (
      $request->header('Last-Event-ID')
      ?? $request->query('last_event_id', '')
    ));

    $cursor = ctype_digit($rawLastEventId) ? (int) $rawLastEventId : 0;
    if ($cursor < 0) {
      $cursor = 0;
    }

    // Si es una conexion nueva sin cursor, arrancar desde "ahora"
    // para enviar solo eventos nuevos y no backlog historico.
    if ($cursor === 0) {
      $cursor = $this->liveAsistenciaEventFeedService->latestId();
    }

    return response()->stream(function () use ($cursor) {
      @ini_set('output_buffering', 'off');
      @ini_set('zlib.output_compression', '0');
      @set_time_limit(0);

      $startedAt = microtime(true);
      $maxWaitSeconds = 25;
      $lastSentId = $cursor;
      $loopSleepMicros = 500000;

      while (!connection_aborted()) {
        $batch = $this->liveAsistenciaEventFeedService->pullAfter($lastSentId, 50);
        $events = is_array($batch['events'] ?? null) ? $batch['events'] : [];

        if (!empty($events)) {
          foreach ($events as $eventItem) {
            $origenEvento = strtolower(trim((string) ($eventItem['origen'] ?? '')));
            if ($origenEvento !== '' && !in_array($origenEvento, ['api', 'camara'], true)) {
              continue;
            }

            $streamId = (int) ($eventItem['stream_id'] ?? 0);
            if ($streamId <= 0) {
              continue;
            }

            $lastSentId = max($lastSentId, $streamId);
            echo 'id: ' . $streamId . "\n";
            echo "event: recognized\n";
            echo 'data: ' . json_encode($eventItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
          }

          @ob_flush();
          flush();
          return;
        }

        if ((microtime(true) - $startedAt) >= $maxWaitSeconds) {
          echo ": keepalive\n\n";
          @ob_flush();
          flush();
          return;
        }

        usleep($loopSleepMicros);
      }
    }, 200, [
      'Content-Type' => 'text/event-stream',
      'Cache-Control' => 'no-cache, no-transform',
      'Connection' => 'keep-alive',
      'X-Accel-Buffering' => 'no',
    ]);
  }

  public function eventosHoyPorIdentificacion(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'ok' => false,
        'message' => 'Identificacion invalida.',
        'errors' => $validator->errors(),
      ], 422);
    }

    $identificacion = preg_replace('/\D+/', '', (string) $request->input('identificacion'));
    $identificacion = trim((string) $identificacion);

    $rows = DB::connection('oracle-360')
      ->table('PRS_EVENTOS as e')
      ->leftJoin('PRS_PERSONAS as p', 'p.numero_documento', '=', 'e.identificacion')
      ->where('e.identificacion', $identificacion)
      ->whereIn('e.evento', [1, 2])
      ->whereRaw('TRUNC(e.fecha_creacion) = TRUNC(SYSDATE)')
      ->orderByDesc('e.fecha_creacion')
      ->limit(30)
      ->get([
        'e.id as evento_id',
        'e.identificacion',
        'e.evento',
        'e.descripcion',
        'e.fecha_creacion',
        'p.NOMBRES as nombres',
        'p.PRIMER_APELLIDO as primer_apellido',
        'p.SEGUNDO_APELLIDO as segundo_apellido',
      ]);

    return response()->json([
      'ok' => true,
      'identificacion' => $identificacion,
      'data' => $this->mapEventoRows($rows),
    ]);
  }

  private function mapEventoRows($rows)
  {
    return collect($rows)->map(function ($row) {
      $nombre = trim(
        trim((string) ($row->nombres ?? '')) . ' ' .
        trim((string) ($row->primer_apellido ?? '') . ' ' . (string) ($row->segundo_apellido ?? ''))
      );

      $horaEvento = null;
      if (!empty($row->fecha_creacion)) {
        try {
          $horaEvento = Carbon::parse($row->fecha_creacion)->format('g:i a');
        } catch (\Throwable $e) {
          $horaEvento = null;
        }
      }

      return [
        'evento_id' => isset($row->evento_id) ? (string) $row->evento_id : null,
        'identificacion' => (string) ($row->identificacion ?? ''),
        'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
        'descripcion' => (string) ($row->descripcion ?? ''),
        'hora_evento' => $horaEvento,
        'fecha_evento' => !empty($row->fecha_creacion) ? (string) $row->fecha_creacion : null,
        'evento' => (int) ($row->evento ?? 0),
      ];
    })->values();
  }

  private function findConfiguredIpCamera(string $cameraId): ?array
  {
    $cameras = config('camera.ip_cameras', []);
    if (!is_array($cameras)) {
      return null;
    }

    foreach ($cameras as $camera) {
      if (!is_array($camera)) {
        continue;
      }

      if ((bool) ($camera['enabled'] ?? false) === false) {
        continue;
      }

      $id = trim((string) ($camera['id'] ?? ''));
      if ($id === '' || $id !== $cameraId) {
        continue;
      }

      return $camera;
    }

    return null;
  }

  private function runFfmpegSnapshot(
    string $ffmpegBinary,
    string $rtspUrl,
    int $timeoutSeconds,
    string $transport = 'tcp'
  ): array {
    $process = new Process(
      [
        $ffmpegBinary,
        '-loglevel',
        'error',
        '-rtsp_transport',
        $transport,
        '-i',
        $rtspUrl,
        '-frames:v',
        '1',
        '-f',
        'image2pipe',
        '-vcodec',
        'mjpeg',
        'pipe:1',
      ],
      base_path(),
      $this->buildFfmpegEnv()
    );
    $process->setTimeout($timeoutSeconds);

    try {
      $process->mustRun();

      return [
        'ok' => true,
        'transport' => $transport,
        'exit_code' => $process->getExitCode(),
        'stderr' => null,
        'output' => $process->getOutput(),
        'output_len' => strlen($process->getOutput()),
        'timed_out' => false,
        'exception' => null,
      ];
    } catch (ProcessTimedOutException $e) {
      return [
        'ok' => false,
        'transport' => $transport,
        'exit_code' => $process->getExitCode(),
        'stderr' => mb_substr($this->sanitizeRtspMessage($process->getErrorOutput()), 0, 800),
        'output' => '',
        'output_len' => 0,
        'timed_out' => true,
        'exception' => get_class($e),
      ];
    } catch (\Throwable $e) {
      return [
        'ok' => false,
        'transport' => $transport,
        'exit_code' => $process->getExitCode(),
        'stderr' => mb_substr($this->sanitizeRtspMessage($process->getErrorOutput()), 0, 800),
        'output' => '',
        'output_len' => 0,
        'timed_out' => false,
        'exception' => get_class($e),
      ];
    }
  }

  private function buildFfmpegEnv(): array
  {
    $env = getenv();
    if (!is_array($env)) {
      $env = [];
    }

    $systemRoot = (string) (getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\\WINDOWS');
    if ($systemRoot !== '') {
      $env['SystemRoot'] = $systemRoot;
      $env['SYSTEMROOT'] = $systemRoot;
    }

    $windir = (string) (getenv('WINDIR') ?: $systemRoot);
    if ($windir !== '') {
      $env['WINDIR'] = $windir;
    }

    $comSpec = (string) (getenv('ComSpec') ?: getenv('COMSPEC') ?: ($systemRoot !== '' ? $systemRoot . '\\System32\\cmd.exe' : ''));
    if ($comSpec !== '') {
      $env['ComSpec'] = $comSpec;
      $env['COMSPEC'] = $comSpec;
    }

    $pathValue = (string) (getenv('Path') ?: getenv('PATH') ?: '');
    if ($pathValue !== '') {
      $env['Path'] = $pathValue;
      $env['PATH'] = $pathValue;
    }

    return $env;
  }

  private function jpegSnapshotResponse(string $snapshot, string $transport)
  {
    if ($snapshot === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'La camara no devolvio una imagen valida.',
      ], 503);
    }

    return response($snapshot, 200)
      ->header('Content-Type', 'image/jpeg')
      ->header('X-Camera-Transport', $transport)
      ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
      ->header('Pragma', 'no-cache')
      ->header('Expires', '0');
  }

  private function sanitizeRtspMessage(string $message): string
  {
    $trimmed = trim($message);
    if ($trimmed === '') {
      return '';
    }

    return (string) preg_replace(
      '/rtsp:\/\/([^:\s\/@]+):([^@\s\/]+)@/i',
      'rtsp://$1:***@',
      $trimmed
    );
  }
}
