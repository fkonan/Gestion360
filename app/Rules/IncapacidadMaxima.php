<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IncapacidadMaxima implements ValidationRule
{
    protected $fechaInicio;

    public function __construct($fechaInicio)
    {
        $this->fechaInicio = $fechaInicio;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fechaInicio = Carbon::parse($this->fechaInicio);
        $fechaFin = Carbon::parse($value);
        $maxFechaFin = $fechaInicio->copy()->addMonths(3);

        if ($fechaFin->greaterThan($maxFechaFin)) {
            $fail('La incapacidad no puede ser mayor a 3 meses.');
        }
    }
}
