<?php

namespace App\Modules\Camara\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CameraService
{
  private string $baseUrl;

  public function __construct()
  {
    $this->baseUrl = rtrim((string) config('services.camera.url'), '/');
  }

  private function client()
  {
    return Http::connectTimeout(2)->timeout(3)->acceptJson();
  }

  public function health(): Response
  {
    return $this->client()->get($this->baseUrl . '/health');
  }

  public function recognize(UploadedFile $file): Response
  {
    return $this->client()
      ->asMultipart()
      ->attach(
        'files',
        fopen($file->getRealPath(), 'r'),
        $file->getClientOriginalName() ?: 'face.jpg'
      )
      ->post($this->baseUrl . '/recognize');
  }

  /**
   * @param UploadedFile[] $files
   */
  public function recognizeBatch(array $files, int $evento = 2, ?string $usrcreacion = null): Response
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
    return $client->post($this->baseUrl . '/recognize', $payload);
  }

  public function enroll(UploadedFile $file, string $identificacion, ?string $usrcreacion = null): Response
  {
    $payload = ['identificacion' => $identificacion];
    if ($usrcreacion) {
      $payload['usrcreacion'] = $usrcreacion;
    }

    return $this->client()
      ->asMultipart()
      ->attach(
        'files',
        fopen($file->getRealPath(), 'r'),
        $file->getClientOriginalName() ?: 'face.jpg'
      )
      ->post($this->baseUrl . '/enroll', $payload);
  }
}
