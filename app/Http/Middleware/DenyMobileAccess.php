<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyMobileAccess
{
    private const MOBILE_PATTERN = '/android|iphone|ipad|ipod|blackberry|bb10|iemobile|windows phone|opera mini|mobile/i';

    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = strtolower((string) $request->userAgent());

        if ($userAgent !== '' && preg_match(self::MOBILE_PATTERN, $userAgent) === 1) {
            $message = 'Este módulo no está disponible en móviles.';

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

        return $next($request);
    }
}
