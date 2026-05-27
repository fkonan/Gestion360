<?php

namespace App\Modules\Camara\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLiveRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'images' => ['required', 'array', 'min:1', 'max:3'],
      'images.*' => ['file', 'mimes:jpg,jpeg', 'max:3072'],
    ];
  }
}
