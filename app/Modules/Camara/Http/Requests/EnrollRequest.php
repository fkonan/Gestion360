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
      'images' => ['required', 'array', 'size:3'],
      'images.*' => ['required', 'file', 'mimes:jpg,jpeg', 'max:3072'],
      'identificacion' => ['required', 'string'],
    ];
  }
}
