<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route middleware validates the integration token and scope.
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['nullable', 'string', 'max:100'],
            'trade' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'industry_description' => ['nullable', 'string', 'max:10000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_address_2' => ['nullable', 'string', 'max:255'],
            'company_address_city' => ['nullable', 'string', 'max:255'],
            'company_address_state' => ['nullable', 'string', 'max:255'],
            'company_address_zip' => ['nullable', 'string', 'max:20'],
            'company_address_country' => ['nullable', 'string', 'max:255'],
        ];
    }
}
