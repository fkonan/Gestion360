<?php

namespace App\Modules\Camara\Http\Controllers;

use App\Http\Controllers\Controller;

class CamaraController extends Controller
{
  public function index()
  {
    return view('camara::camara.index');
  }

  public function enroll()
  {
    return view('camara::camara.enroll');
  }

  public function recognize()
  {
    return view('camara::camara.recognize');
  }

  public function recognizeIp()
  {
    return redirect()->route('camera.ip.preview');
  }

  public function verify()
  {
    return view('camara::camara.verify');
  }

  public function ipPreview()
  {
    $cameras = collect(config('camera.ip_cameras', []))
      ->filter(function ($camera) {
        return is_array($camera) && (bool) ($camera['enabled'] ?? false);
      })
      ->map(function ($camera) {
        return [
          'id' => trim((string) ($camera['id'] ?? '')),
          'name' => trim((string) ($camera['name'] ?? 'Camara IP')),
          'width' => isset($camera['width']) ? (int) $camera['width'] : null,
          'height' => isset($camera['height']) ? (int) $camera['height'] : null,
          'max_face_distance_meters' => isset($camera['max_face_distance_meters']) ? (float) $camera['max_face_distance_meters'] : null,
        ];
      })
      ->filter(function ($camera) {
        return $camera['id'] !== '';
      })
      ->values()
      ->all();

    return view('camara::camara.ip-preview', [
      'cameras' => $cameras,
      'defaultCameraId' => $cameras[0]['id'] ?? null,
    ]);
  }
}
