<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivitiesRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     * No auth implimented yet, TBC!
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
            'radius' => ['nullable', 'integer', 'min:10', 'max:100000'], // cap radius
            'type' => ['nullable', 'in:indoor,outdoor,all'],
        ];
    }
}
