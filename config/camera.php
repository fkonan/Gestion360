<?php

$parseIpCameras = static function (): array {
    $raw = trim((string) env('CAMERAS_JSON', '[]'));
    if ($raw === '') {
        return [];
    }

    $decodeCandidates = [$raw];

    // Some production environments keep literal wrapper quotes in env values.
    $firstChar = substr($raw, 0, 1);
    $lastChar = substr($raw, -1);
    if (
        ($firstChar === "'" && $lastChar === "'")
        || ($firstChar === '"' && $lastChar === '"')
    ) {
        $unwrapped = trim($raw, "'\" \t\n\r\0\x0B");
        if ($unwrapped !== '' && $unwrapped !== $raw) {
            $decodeCandidates[] = $unwrapped;
        }
    }

    $decoded = null;
    foreach ($decodeCandidates as $candidate) {
        $tryDecoded = json_decode($candidate, true);
        if (! is_array($tryDecoded) && is_string($tryDecoded)) {
            // Accept values serialized as a JSON string, e.g. "[{\"id\":\"cam1\"}]"
            $tryDecoded = json_decode($tryDecoded, true);
        }

        if (is_array($tryDecoded)) {
            $decoded = $tryDecoded;
            break;
        }
    }

    if (! is_array($decoded)) {
        return [];
    }

    $toBool = static function ($value, bool $default = true): bool {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value !== 0;
        }
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    };

    $normalized = [];
    foreach ($decoded as $camera) {
        if (! is_array($camera)) {
            continue;
        }

        $id = trim((string) ($camera['id'] ?? ''));
        $rtspUrl = trim((string) ($camera['rtsp_url'] ?? ''));
        if ($id === '' || $rtspUrl === '') {
            continue;
        }

        $normalized[] = [
            'id' => $id,
            'name' => trim((string) ($camera['name'] ?? $id)),
            'rtsp_url' => $rtspUrl,
            'enabled' => $toBool($camera['enabled'] ?? true, true),
            'width' => isset($camera['width']) ? (int) $camera['width'] : null,
            'height' => isset($camera['height']) ? (int) $camera['height'] : null,
            'focal_length_px' => isset($camera['focal_length_px']) ? (float) $camera['focal_length_px'] : null,
            'max_face_distance_meters' => isset($camera['max_face_distance_meters']) ? (float) $camera['max_face_distance_meters'] : null,
        ];
    }

    return $normalized;
};

return [
    'url' => env('CAMERA_SERVICE_URL'),
    'key' => env('CAMERA_SERVICE_KEY'),
    'allowed_networks' => array_values(array_filter(array_map(
        static fn ($value) => trim((string) $value),
        explode(',', (string) env('CAMERA_ALLOWED_CIDRS', '172.16.0.0/12,127.0.0.1/32,::1/128'))
    ))),
    'ip_cameras' => $parseIpCameras(),
    'ip_ffmpeg_binary' => trim((string) env('CAMERA_IP_FFMPEG_BINARY', 'ffmpeg')),
    'ip_snapshot_timeout_seconds' => (int) env('CAMERA_IP_SNAPSHOT_TIMEOUT_SECONDS', 8),
    'ip_stream_transport' => trim((string) env('CAMERA_IP_STREAM_TRANSPORT', 'tcp')),
    'ip_stream_fps' => (int) env('CAMERA_IP_STREAM_FPS', 12),
    'ip_stream_quality' => (int) env('CAMERA_IP_STREAM_QUALITY', 6),
];
