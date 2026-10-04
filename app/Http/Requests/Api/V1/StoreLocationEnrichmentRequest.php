<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLocationEnrichmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:255'],
            'radius_km' => ['required', 'numeric', 'min:0.001', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id is required.',
            'id.string' => 'id must be a string.',
            'id.max' => 'id must not exceed 100 characters.',

            'city.required' => 'city is required.',
            'city.string' => 'city must be a string.',
            'city.max' => 'city must not exceed 255 characters.',

            'radius_km.required' =>
                'radius_km is required. Send a radius in kilometers, for example 20.',
            'radius_km.numeric' =>
                'radius_km must be a number.',
            'radius_km.min' =>
                'radius_meters must be at least 1 metre.',
            'radius_meters.max' =>
                'radius_meters must not exceed 100000 metres (100 km).',
        ];
    }
}
