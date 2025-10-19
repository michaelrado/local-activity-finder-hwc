<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WeatherRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     * Auth TBC!
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
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
