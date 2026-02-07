<?php

namespace App\Modules\Camara\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'image' => ['required', 'file', 'mimes:jpg,jpeg', 'max:3072'],
      'identificacion' => ['required', 'string'],
    ];
  }
}
