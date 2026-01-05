<?php

namespace App\Services\Huellero;

use App\Exceptions\Huellero\DeviceNotConnectedException;
use App\Exceptions\Huellero\FingerprintCaptureException;
use App\Exceptions\Huellero\InvalidFingerprintDataException;
use Illuminate\Support\Facades\Log;

class DigitalPersonaService
{
    private $webSdkUrl;
    private $timeout;

    public function __construct()
    {
        $this->webSdkUrl = config('fingerprint.websdk_url', 'https://127.0.0.1:52181');
        $this->timeout = config('fingerprint.timeout', 30);
    }

    /**
     * Verificar si el servicio Digital Persona está disponible
     */
    public function isServiceAvailable(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/get_connection');
            return $response !== false;
        } catch (\Exception $e) {
            Log::warning('Digital Persona service not available', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtener la lista de dispositivos conectados
     */
    public function getConnectedDevices(): array
    {
        if (!$this->isServiceAvailable()) {
            throw new DeviceNotConnectedException();
        }

        try {
            $response = $this->makeRequest('POST', '/devices/enumerate');

            if (!$response || !isset($response['devices'])) {
                return [];
            }

            return $response['devices'];
        } catch (\Exception $e) {
            Log::error('Error getting connected devices', ['error' => $e->getMessage()]);
            throw new DeviceNotConnectedException('Error al obtener dispositivos: ' . $e->getMessage());
        }
    }

    /**
     * Obtener información de un dispositivo específico
     */
    public function getDeviceInfo(string $deviceId): ?array
    {
        try {
            $response = $this->makeRequest('POST', '/devices/info', [
                'deviceId' => $deviceId
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Error getting device info', [
                'deviceId' => $deviceId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Iniciar captura de huella
     */
    public function startCapture(string $deviceId, string $format = 'PngImage'): bool
    {
        try {
            $response = $this->makeRequest('POST', '/capture/start', [
                'deviceId' => $deviceId,
                'format' => $format
            ]);

            return isset($response['success']) && $response['success'];
        } catch (\Exception $e) {
            Log::error('Error starting fingerprint capture', [
                'deviceId' => $deviceId,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            throw new FingerprintCaptureException('Error al iniciar captura: ' . $e->getMessage());
        }
    }

    /**
     * Detener captura de huella
     */
    public function stopCapture(string $deviceId): bool
    {
        try {
            $response = $this->makeRequest('POST', '/capture/stop', [
                'deviceId' => $deviceId
            ]);

            return isset($response['success']) && $response['success'];
        } catch (\Exception $e) {
            Log::error('Error stopping fingerprint capture', [
                'deviceId' => $deviceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Procesar datos de huella capturada
     */
    public function processCapturedFingerprint(array $sampleData): array
    {
        if (!isset($sampleData['samples']) || empty($sampleData['samples'])) {
            throw new InvalidFingerprintDataException('No se encontraron muestras en los datos');
        }

        $samples = is_string($sampleData['samples'])
            ? json_decode($sampleData['samples'], true)
            : $sampleData['samples'];

        if (!is_array($samples) || empty($samples)) {
            throw new InvalidFingerprintDataException('Formato de muestras inválido');
        }

        $processedData = [
            'raw_data' => $samples[0] ?? null,
            'quality' => $sampleData['quality'] ?? 0,
            'format' => $sampleData['format'] ?? 'PngImage',
            'timestamp' => now(),
            'processed_template' => null
        ];

        // Procesar según el formato
        switch ($sampleData['format'] ?? 'PngImage') {
            case 'PngImage':
                $processedData['image_data'] = 'data:image/png;base64,' . $samples[0];
                break;

            case 'Raw':
                $processedData['processed_template'] = $this->processRawData($samples[0]);
                break;

            case 'Intermediate':
                $processedData['processed_template'] = $samples[0];
                break;

            case 'Compressed':
                $processedData['wsq_data'] = $this->processCompressedData($samples[0]);
                break;
        }

        return $processedData;
    }

    /**
     * Realizar petición HTTP al servicio Digital Persona
     */
    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $url = $this->webSdkUrl . $endpoint;

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'content' => $method === 'POST' ? json_encode($data) : null,
                'timeout' => $this->timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Procesar datos raw de huella
     */
    private function processRawData(string $rawData): string
    {
        // Decodificar y procesar datos raw
        $decoded = base64_decode($rawData);
        $decodedData = json_decode($decoded, true);

        return base64_decode($decodedData['Data'] ?? '');
    }

    /**
     * Procesar datos comprimidos WSQ
     */
    private function processCompressedData(string $compressedData): string
    {
        // Procesar datos WSQ comprimidos
        $decoded = base64_decode($compressedData);
        $decodedData = json_decode($decoded, true);

        return 'data:application/octet-stream;base64,' . base64_encode($decodedData['Data'] ?? '');
    }
}
