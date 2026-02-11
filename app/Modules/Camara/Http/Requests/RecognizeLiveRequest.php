<?php

namespace App\Modules\Camara\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecognizeLiveRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'images' => ['required', 'array', 'min:1', 'max:4'],
      'images.*' => ['file', 'mimes:jpg,jpeg', 'max:3072'],
      'evento' => ['required', 'in:1,2'],
    ];
  }
}
