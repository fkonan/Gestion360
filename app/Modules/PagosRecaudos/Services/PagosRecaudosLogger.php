<?php

namespace App\Modules\PagosRecaudos\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PagosRecaudosLogger
{
    private const CHANNEL = 'pagos_recaudos';

    private const REQUEST_ID_ATTRIBUTE = 'pagos_recaudos_request_id';

    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('critical', $message, $context);
    }

    public static function exception(string $message, Throwable $exception, array $context = [], string $level = 'error'): void
    {
        self::write($level, $message, array_merge($context, [
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
        ]));
    }

    public static function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->{$level}($message, self::context($context));
    }

    private static function context(array $context = []): array
    {
        return self::filterNulls(array_merge([
            'module' => 'pagos_recaudos',
            'request_id' => self::requestId(),
            'route' => request()?->route()?->getName(),
            'method' => request()?->method(),
            'auth_user_id' => Auth::id(),
            'auth_persona_documento' => Auth::user()?->persona?->PerNumDoc,
        ], $context));
    }

    private static function requestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();
        if (! $request) {
            return null;
        }

        $requestId = $request->attributes->get(self::REQUEST_ID_ATTRIBUTE);
        if ($requestId) {
            return $requestId;
        }

        $requestId = (string) Str::uuid();
        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);

        return $requestId;
    }

    private static function filterNulls(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = self::filterNulls($value);
            }

            if ($value === null || $value === []) {
                unset($context[$key]);
                continue;
            }

            $context[$key] = $value;
        }

        return $context;
    }
}
