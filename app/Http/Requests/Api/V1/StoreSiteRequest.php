<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route middleware validates the integration token and scope.
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('is_active', true),
            ],
            'site_name' => ['nullable', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' =>
                'The selected company does not exist or is inactive.',
        ];
    }
}
