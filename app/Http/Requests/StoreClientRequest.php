<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['nullable', 'string', 'max:100'],
            'trade' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'industry_description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_address_2' => ['nullable', 'string', 'max:255'],
            'company_address_city' => ['nullable', 'string', 'max:255'],
            'company_address_state' => ['nullable', 'string', 'max:255'],
            'company_address_country' => ['nullable', 'string', 'max:255'],
            'next_action' => ['required', 'in:list,add'],
        ];
    }
}


