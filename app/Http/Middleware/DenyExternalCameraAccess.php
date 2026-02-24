<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyExternalCameraAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) ($request->ip() ?? '');
        $allowedCidrs = $this->allowedCidrs();

        if ($ip === '') {
            return $this->deny($request, 'No se pudo validar la red de origen.');
        }

        if (!$this->isAllowed($ip, $allowedCidrs)) {
            return $this->deny($request, 'Este modulo solo esta disponible desde la red interna.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        }

        $previousUrl = url()->previous();
        $currentUrl = $request->fullUrl();
        $fallbackUrl = route('home');

        $redirectUrl = $previousUrl && $previousUrl !== $currentUrl
            ? $previousUrl
            : $fallbackUrl;

        return redirect()->to($redirectUrl)->with('alert', [
            'type' => 'warning',
            'title' => $message,
        ]);
    }

    /**
     * @return string[]
     */
    private function allowedCidrs(): array
    {
        $configured = config('services.camera.allowed_cidrs')
            ?? config('camera.allowed_networks', []);
        if (!is_array($configured) || count($configured) === 0) {
            return ['172.16.0.0/12', '127.0.0.1/32', '::1/128'];
        }

        return array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $configured
        )));
    }

    /**
     * @param string[] $allowedCidrs
     */
    private function isAllowed(string $ip, array $allowedCidrs): bool
    {
        foreach ($allowedCidrs as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            $cidr .= filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '/128' : '/32';
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $prefixLength = (int) $prefix;

        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false) {
            return false;
        }

        if (strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $maxBits = strlen($ipBinary) * 8;
        if ($prefixLength < 0 || $prefixLength > $maxBits) {
            return false;
        }

        $wholeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($wholeBytes > 0) {
            if (substr($ipBinary, 0, $wholeBytes) !== substr($subnetBinary, 0, $wholeBytes)) {
                return false;
            }
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        $ipByte = ord($ipBinary[$wholeBytes]);
        $subnetByte = ord($subnetBinary[$wholeBytes]);

        return ($ipByte & $mask) === ($subnetByte & $mask);
    }

}
