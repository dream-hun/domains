<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AddDomainRenewalToCartRequest extends FormRequest
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
            'years' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'years.required' => 'Please specify the number of years for renewal.',
            'years.integer' => 'The number of years must be a valid integer.',
            'years.min' => 'The minimum renewal period is 1 year.',
            'years.max' => 'The maximum renewal period is 10 years.',
        ];
    }
}
