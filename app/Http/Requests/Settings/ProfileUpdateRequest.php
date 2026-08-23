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
            'travel_allowance' => ['required', 'numeric', 'min:0'],
            'travel_allowance_currency' => ['required', 'string', 'size:3'],
            'salary' => ['required', 'numeric', 'min:0'],
            'start_shift' => ['nullable', 'required_with:end_shift', 'date_format:H:i'],
            'end_shift' => ['nullable', 'required_with:start_shift', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'salary.required' => 'Salary is required. Enter 0 if there is no salary amount.',
            'salary.numeric' => 'Salary must be a valid number.',
            'salary.min' => 'Salary cannot be negative.',
            'travel_allowance.required' => 'Travel allowance is required. Enter 0 if there is no travel allowance.',
            'travel_allowance.numeric' => 'Travel allowance must be a valid number.',
            'travel_allowance.min' => 'Travel allowance cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startShift = $this->input('start_shift');
            $endShift = $this->input('end_shift');

            if (! $startShift || ! $endShift) {
                return;
            }

            $overlappingShift = $this->user()
                ->companyWorkDetails()
                ->with('client:id,company_name')
                ->where('client_id', '!=', (int) $this->input('client_id'))
                ->whereNotNull('start_shift')
                ->whereNotNull('end_shift')
                ->get()
                ->first(fn ($workDetail) => $this->shiftsOverlap(
                    $startShift,
                    $endShift,
                    $workDetail->start_shift,
                    $workDetail->end_shift,
                ));

            if ($overlappingShift) {
                $validator->errors()->add(
                    'start_shift',
                    'This shift overlaps with ' . ($overlappingShift->client?->company_name ?? 'another company') . '.'
                );
            }
        });
    }

    private function shiftsOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        foreach ($this->shiftRanges($startA, $endA) as [$aStart, $aEnd]) {
            foreach ($this->shiftRanges($startB, $endB) as [$bStart, $bEnd]) {
                if ($aStart < $bEnd && $bStart < $aEnd) {
                    return true;
                }
            }
        }

        return false;
    }

    private function shiftRanges(string $start, string $end): array
    {
        $startMinutes = $this->timeToMinutes($start);
        $endMinutes = $this->timeToMinutes($end);

        if ($endMinutes <= $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return [
            [$startMinutes - (24 * 60), $endMinutes - (24 * 60)],
            [$startMinutes, $endMinutes],
            [$startMinutes + (24 * 60), $endMinutes + (24 * 60)],
        ];
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }
}
