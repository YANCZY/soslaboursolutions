<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route validates the integration token and users:create scope.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach ([
            'source_type',
            'source_id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'profile',
        ] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $updates[$field] = trim($value);
            }
        }

        if (isset($updates['email'])) {
            $updates['email'] = mb_strtolower($updates['email']);
        }

        $site = $this->input('site');

        if (is_array($site)) {
            foreach ($site as $key => $value) {
                if (is_string($value)) {
                    $site[$key] = trim($value);
                }
            }

            $updates['site'] = $site;
        }

        $this->merge($updates);
    }

    public function rules(): array
    {
        return [
            'source_type' => [
                'required',
                Rule::in(
                    $this->input('profile') === 'Contractor'
                        ? ['candidates']
                        : ['contacts'],
                ),
            ],

            'source_id' => [
                'required',
                'string',
                'max:100',
                'regex:/^[0-9]+$/',
            ],

            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],

            'profile' => [
                'required',
                Rule::in([
                    'Client Admin',
                    'Client Standard',
                    'Contractor',
                ]),
            ],

            'company_id' => ['required', 'integer', 'min:1'],

            'site_id' => [
                'nullable',
                'integer',
                'min:1',
                'prohibits:site',
            ],

            'site' => [
                'nullable',
                'array:site_name,street,city,state,postcode,country',
                'min:5',
                'prohibits:site_id',
                'prohibited_unless:profile,Contractor',
            ],

            'site.site_name' => ['nullable', 'string', 'max:255'],
            'site.street' => ['required_with:site', 'string', 'max:255'],
            'site.city' => ['required_with:site', 'string', 'max:100'],
            'site.state' => ['required_with:site', 'string', 'max:100'],
            'site.postcode' => ['required_with:site', 'string', 'max:20'],
            'site.country' => ['required_with:site', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_type.in' =>
                'Use contacts for client profiles and candidates for Contractor.',

            'site.prohibited_unless' =>
                'Only contractors may resolve or create a site by address.',

            'site_id.prohibits' =>
                'Provide site_id or site, not both.',

            'site.prohibits' =>
                'Provide site_id or site, not both.',
        ];
    }
}
