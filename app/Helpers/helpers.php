<?php

use Illuminate\Http\JsonResponse;

//Formatea el nombre de los modulos en un formato que permita relacionarlos con los permisos
if (!function_exists('normalizarNombre')) {
    function normalizarNombre($texto)
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = preg_replace('/[\s-]+/', '_', $texto);
        $texto = preg_replace('/[^A-Za-z0-9_]/', '', $texto);
        return strtolower($texto);
    }
}

//Permite usar los toast de forma mas directa
if (!function_exists('toast')) {
    function toast($message, $type = 'primary', $redirect = null) {
        $redirect = $redirect ?? redirect()->back();
        return $redirect->with('toast', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}

//Permite lanzar el mensaje de sweet alert al finalizar una accion en un MODAL (estos van con ajax)
if (!function_exists('sweetAlertJson')) {
    function sweetAlertJson($message, $type = 'success', $redirect = null): JsonResponse
    {
        return response()->json([
            'title' => $message,
            'type' => $type,
            'redirect' => $redirect ?? '#',
        ]);
    }
}


