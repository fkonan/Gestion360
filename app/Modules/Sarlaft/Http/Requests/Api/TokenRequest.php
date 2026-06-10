<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grant_type' => 'nullable|string|in:client_credentials',
            'client_id' => 'required|string|max:120',
            'client_secret' => 'required|string|max:255',
            'scope' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'El campo client_id es obligatorio.',
            'client_secret.required' => 'El campo client_secret es obligatorio.',
            'grant_type.in' => 'El grant_type soportado es client_credentials.',
        ];
    }
}
