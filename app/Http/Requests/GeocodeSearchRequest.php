<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeocodeSearchRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     * TBC
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return ['q' => ['required', 'string', 'min:2', 'max:200'], 'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
