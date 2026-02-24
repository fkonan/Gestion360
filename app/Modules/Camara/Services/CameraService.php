<?php

namespace App\Modules\Camara\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CameraService
{
  private string $baseUrl;
  private ?string $apiKey;

  public function __construct()
  {
    $this->baseUrl = rtrim((string) config('camera.url', config('services.camera.url')), '/');
    $this->apiKey = config('camera.key', config('services.camera.key'));
  }

  private function client()
  {
    $client = Http::connectTimeout(2)->timeout(3)->acceptJson();
    if ($this->apiKey) {
      $client = $client->withHeaders(['X-API-Key' => $this->apiKey]);
    }
    return $client;
  }

  public function health(): Response
  {
    return $this->client()->get($this->baseUrl . '/health');
  }

  public function recognize(UploadedFile $file, ?string $identCrea = null): Response
  {
    $client = $this->client()
      ->asMultipart()
      ->attach(
        'files',
        fopen($file->getRealPath(), 'r'),
        $file->getClientOriginalName() ?: 'face.jpg'
      );

    $payload = [];
    if ($identCrea) {
      $payload['ident_crea'] = $identCrea;
    }

    return $client->post($this->baseUrl . '/recognize', $payload);
  }

  /**
   * @param UploadedFile[] $files
   */
  public function recognizeBatch(array $files, int $evento = 2, ?string $usrcreacion = null, ?string $identCrea = null): Response
  {
    $client = $this->client()->asMultipart();
    foreach ($files as $file) {
      $client = $client->attach(
        'files',
        fopen($file->getRealPath(), 'r'),
        $file->getClientOriginalName() ?: 'frame.jpg'
      );
    }
    $payload = ['evento' => $evento];
    if ($usrcreacion) {
      $payload['usrcreacion'] = $usrcreacion;
    }
    if ($identCrea) {
      $payload['ident_crea'] = $identCrea;
    }

    return $client->post($this->baseUrl . '/recognize', $payload);
  }

  /**
   * @param UploadedFile[] $files
   */
  public function enroll(array $files, string $identificacion, ?string $usrcreacion = null, ?string $identCrea = null): Response
  {
    $payload = ['identificacion' => $identificacion];
    if ($usrcreacion) {
      $payload['usrcreacion'] = $usrcreacion;
    }
    if ($identCrea) {
      $payload['ident_crea'] = $identCrea;
    }

    $client = $this->client()->asMultipart();
    foreach ($files as $file) {
      $client = $client->attach(
        'files',
        fopen($file->getRealPath(), 'r'),
        $file->getClientOriginalName() ?: 'face.jpg'
      );
    }

    return $client->post($this->baseUrl . '/enroll', $payload);
  }
}
