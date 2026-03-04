<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Allocation;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateAllocationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'insurance_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'savings_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'pathway_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'administration_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $this->only([
                'insurance_percentage',
                'savings_percentage',
                'pathway_percentage',
                'administration_percentage',
            ]);

            $sum = array_sum(array_map(floatval(...), $data));

            if (abs($sum - 100.0) > 0.001) {
                $v->errors()->add('insurance_percentage', 'The percentages must sum to 100.');
            }
        });
    }
}
