<?php

use App\Models\GESTIONADMIN\Permisos;
use Illuminate\Http\JsonResponse;

//Formatea el nombre de los modulos en un formato que permita relacionarlos con los permisos
if (!function_exists('normalizarNombre')) {
    function normalizarNombre($texto)
    {
        // Reemplaza letras con tilde por su equivalente sin tilde
        $acentos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N'
        ];
        $texto = strtr($texto, $acentos);
        $texto = preg_replace('/[\s-]+/', '_', $texto);
        $texto = preg_replace('/[^A-Za-z0-9_]/', '', $texto);
        return strtolower($texto);
    }
}

//Permite usar los toast de forma mas directa
if (!function_exists('toast')) {
    function toast($message, $type = 'primary', $redirect = null) {
        if (is_string($redirect)) {
            $redirect = redirect($redirect);
        } elseif (!$redirect) {
            $redirect = redirect()->back();
        }

        return $redirect->with('toast', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}

//Permite lanzar la alerta toast en un modal (como resultado de una peticion ajax)
if (!function_exists('toastModal')) {
    function toastModal($message, $type = 'success', $redirect = null): JsonResponse
    {
        return response()->json([
            'title' => $message,
            'type' => $type,
            'redirect' => $redirect ?? '#',
        ]);
    }
}

//Verifica si un permiso existe en la base de datos
if (!function_exists('permisoExiste')) {
    function permisoExiste($permiso, $guard = 'web'): bool
    {
        if (empty($permiso)) return false;

        $permisos = Permisos::where('guard_name', $guard)
            ->pluck('name')
            ->toArray();

        //cacheado
       /*  $permisos = Cache::remember("permisos_guard_{$guard}", 3600, function () use ($guard) {
            return Permisos::where('guard_name', $guard)
                ->pluck('name')
                ->toArray();
        }); */

        return in_array($permiso, $permisos);
    }
}


if (!function_exists('sweetAlert')) {
    /**
     * Crear una alerta con SweetAlert2
     *
     * @param string $message Mensaje de la alerta
     * @param string $type Tipo de alerta: success, error, warning, info
     * @param string|null $redirectUrl URL de redirección después de aceptar (opcional)
     */
    function sweetAlert($message, $type = 'success', $redirectUrl = null) {
        $alertData = [
            'type' => $type,
            'title' => $message,
        ];

        if ($redirectUrl) {
            $alertData['redirect_url'] = $redirectUrl;
        }

        return redirect()->back()->with('alert', $alertData);
    }
}


