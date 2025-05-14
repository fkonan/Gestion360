<?php

use Illuminate\Support\Str;

if (!function_exists('normalizarNombre')) {
    function normalizarNombre($texto)
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = preg_replace('/[\s-]+/', '_', $texto);
        $texto = preg_replace('/[^A-Za-z0-9_]/', '', $texto);
        return strtolower($texto);
    }
}
