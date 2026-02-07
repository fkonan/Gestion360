<?php

namespace App\Modules\Huellero\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class FingerprintController extends Controller
{
    public function enroll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dedo' => ['required', 'string'],
            'identificacion' => ['required', 'string'],
            'huellas' => ['required', 'array', 'min:1'],
            'huellas.*' => ['required', 'string'],
            'idCreacion' => ['nullable'],
            'tipo' => ['required', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            $this->huelleroLogger()->warning('Huellero API enroll validation failed', [
                'identificacion' => $request->input('identificacion'),
                'error' => $validator->errors()->first(),
            ]);
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $payload = $validator->validated();
        if (!array_key_exists('idCreacion', $payload)) {
            $payload['idCreacion'] = null;
        }

        return $this->proxyToService('enroll', $payload);
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'huella' => ['required', 'string'],
            'tipo' => ['required', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            $this->huelleroLogger()->warning('Huellero API verify validation failed', [
                'has_huella' => (bool) $request->input('huella'),
                'error' => $validator->errors()->first(),
            ]);
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        return $this->proxyToService('verify', $validator->validated());
    }

    public function verifyDetailed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identificacion' => ['nullable', 'string', 'required_without:huella'],
            'huella' => ['nullable', 'string', 'required_without:identificacion'],
        ]);

        if ($validator->fails()) {
            $this->huelleroLogger()->warning('Huellero API verifyDetailed validation failed', [
                'identificacion' => $request->input('identificacion'),
                'has_huella' => (bool) $request->input('huella'),
                'error' => $validator->errors()->first(),
            ]);
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors()->first(),
            ], 422);
        }

        return $this->proxyVerifyDetailed($validator->validated());
    }

    private function proxyToService(string $endpoint, array $payload)
    {
        $mode = strtolower((string) env('HUELLA_API_MODE', 'java'));
        //MOCK datos de prueba
        if ($mode === 'mock') {
            $identificaciones = array_filter(array_map('trim', explode(',', (string) env('HUELLA_API_IDENTIFICACIONES', ''))));
            $identificacion = null;
            if (!empty($identificaciones)) {
                $identificacion = $identificaciones[array_rand($identificaciones)];
            } else {
                $identificacion = $payload['identificacion'] ?? null;
            }

            if (!$identificacion) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Identificacion requerida.',
                ], 422);
            }

            $mockStatus = strtolower((string) env('HUELLA_API_MOCK_STATUS', 'success'));
            $isSuccess = $mockStatus === 'success';
            $errorMessage = $endpoint === 'verify'
                ? 'No fue posible identificar al empleado.'
                : 'No fue posible completar el enrolamiento.';

            if (!$isSuccess) {
                $this->huelleroLogger()->warning('Huellero API mock error response', [
                    'endpoint' => $endpoint,
                    'identificacion' => (string) $identificacion,
                ]);
            }
            return response()->json([
                'ok' => $isSuccess,
                'data' => [
                    'status' => $isSuccess ? 'success' : 'error',
                    'identificacion' => (string) $identificacion,
                ],
                'error' => $isSuccess ? null : $errorMessage,
            ]);
        }

        $baseUrl = config('services.fingerprint.url');
        if (!$baseUrl) {
            $this->huelleroLogger()->warning('Huellero API missing base URL', [
                'endpoint' => $endpoint,
            ]);
            return response()->json([
                'ok' => false,
                'error' => 'Fingerprint service URL not configured.',
            ], 503);
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->acceptJson()
                ->withHeaders(['X-API-KEY' => config('services.fingerprint.key')])
                ->post($url, $payload);
        } catch (Throwable $e) {
            $this->huelleroLogger()->warning('Huellero API request failed', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'ok' => false,
                'error' => 'Fingerprint service unavailable.',
            ], 502);
        }

        if ($response->successful()) {
            $data = $response->json() ?? [];
            $status = strtolower((string) ($data['status'] ?? 'success'));
            $isSuccess = in_array($status, ['success', 'ok'], true);
            $errorMessage = $endpoint === 'verify'
                ? 'No fue posible identificar al empleado.'
                : 'No fue posible completar el enrolamiento.';

            return response()->json([
                'ok' => $isSuccess,
                'data' => $data,
                'error' => $isSuccess ? null : ($data['error'] ?? $errorMessage),
            ]);
        }

        $this->huelleroLogger()->warning('Huellero API error response', [
            'endpoint' => $endpoint,
            'http_status' => $response->status(),
        ]);
        return response()->json([
            'ok' => false,
            'error' => 'Fingerprint service error (' . $response->status() . ').',
            'data' => $response->json(),
        ], $response->status());
    }

    private function proxyVerifyDetailed(array $payload)
    {
        $mode = strtolower((string) env('HUELLA_API_MODE', 'java'));
        if ($mode === 'mock') {
            $identificacion = $payload['identificacion'] ?? null;
            if (!$identificacion) {
                $pool = array_filter(array_map('trim', explode(',', (string) env('HUELLA_API_IDENTIFICACIONES', ''))));
                $identificacion = !empty($pool) ? $pool[array_rand($pool)] : null;
            }

            if (!$identificacion) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Identificacion requerida.',
                ], 422);
            }

            $mockStatus = strtolower((string) env('HUELLA_API_MOCK_STATUS', 'success'));
            if ($mockStatus !== 'success') {
                $this->huelleroLogger()->warning('Huellero API verifyDetailed mock error response', [
                    'identificacion' => (string) $identificacion,
                ]);
                return response()->json([
                    'status' => 'error',
                ]);
            }

            return response()->json([
                'status' => 'ok',
                'data' => [
                    'identificacion' => (string) $identificacion,
                    'cargo' => 'ANALISTA DE SISTEMAS',
                    'estadoContrato' => '1',
                ],
            ]);
        }

        $baseUrl = config('services.fingerprint.url');
        if (!$baseUrl) {
            $this->huelleroLogger()->warning('Huellero API verifyDetailed missing base URL');
            return response()->json([
                'status' => 'error',
                'error' => 'Fingerprint service URL not configured.',
            ], 503);
        }

        $base = rtrim($baseUrl, '/');
        $url = str_ends_with($base, '/huellero')
            ? $base . '/verifyDetailed'
            : $base . '/huellero/verifyDetailed';

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->acceptJson()
                ->withHeaders(['X-API-KEY' => config('services.fingerprint.key')])
                ->post($url, $payload);
        } catch (Throwable $e) {
            $this->huelleroLogger()->warning('Huellero API verifyDetailed request failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'error' => 'Fingerprint service unavailable.',
            ], 502);
        }


        if ($response->successful()) {
            $data = $response->json();
            if ($data === null) {
                $raw = trim((string) $response->body());
                if (str_starts_with($raw, 'b"') || str_starts_with($raw, "b'")) {
                    $raw = substr($raw, 2);
                }
                if ((str_starts_with($raw, '"') && str_ends_with($raw, '"')) ||
                    (str_starts_with($raw, "'") && str_ends_with($raw, "'"))) {
                    $raw = substr($raw, 1, -1);
                }

                $decoded = json_decode($raw, true);
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }
                if ($decoded === null && str_contains($raw, '\\"')) {
                    $unescaped = stripcslashes($raw);
                    $decoded = json_decode($unescaped, true);
                }

                $data = is_array($decoded) ? $decoded : null;
            }

            $data = $data ?? ['status' => 'error'];
            $status = strtolower((string) ($data['status'] ?? 'error'));
            if ($status !== 'ok') {
                $this->huelleroLogger()->warning('Huellero API verifyDetailed non-ok response', [
                    'has_identificacion' => (bool) ($payload['identificacion'] ?? null),
                    'huella_len' => isset($payload['huella']) ? strlen((string) $payload['huella']) : 0,
                    'status' => $data['status'] ?? null,
                ]);
            }
            return response()->json($data);
        }

        $this->huelleroLogger()->warning('Huellero API verifyDetailed error response', [
            'http_status' => $response->status(),
        ]);
        return response()->json([
            'status' => 'error',
            'error' => 'Fingerprint service error (' . $response->status() . ').',
            'data' => $response->json(),
        ], $response->status());
    }

    private function huelleroLogger()
    {
        return Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/huellero/huellero.log'),
            'days' => 7,
        ]);
    }
}
