<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'client_id' => [
                'required',
                'integer',
                Rule::exists('client_user', 'client_id')->where(
                    fn($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'job_role' => ['nullable', 'string', 'max:255'],
            'travel_allowance' => ['nullable', 'numeric', 'min:0'],
            'travel_allowance_currency' => ['required', 'string', 'size:3'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'start_shift' => ['nullable', 'date_format:H:i'],
            'end_shift' => ['nullable', 'date_format:H:i'],
        ];
    }
}
