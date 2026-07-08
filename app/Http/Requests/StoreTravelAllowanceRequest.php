<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreTravelAllowanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'date' => ['required', 'date'],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('client_user', 'client_id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rate' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Date is required.',
            'client_id.required' => 'Company is required.',
            'rate.required' => 'Rate is required.',
            'quantity.required' => 'Quantity is required.',
        ];
    }
}
