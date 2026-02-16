<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateDomainPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('domain_pricing_edit');
    }

    public function rules(): array
    {
        $domainPrice = $this->route('price');
        $baseCurrency = $domainPrice->getBaseCurrency();
        $priceFields = ['register_price', 'renewal_price', 'transfer_price', 'redemption_price'];
        $hasPriceChange = false;

        foreach ($priceFields as $field) {
            $inputValue = $this->input($field);
            $currentValue = $domainPrice->getRawPriceForAdminForm($field, $baseCurrency);

            if ($field === 'redemption_price') {
                $inputValue = $inputValue === '' || $inputValue === null ? 0 : (int) $inputValue;
            } else {
                $inputValue = (int) $inputValue;
            }

            if ($inputValue !== $currentValue) {
                $hasPriceChange = true;
                break;
            }
        }

        return [
            'tld' => [
                'required',
                'string',
                Rule::unique('domain_prices', 'tld')->ignore($domainPrice),
            ],
            'register_price' => ['required', 'integer', 'min:0'],
            'renewal_price' => ['required', 'integer', 'min:0'],
            'transfer_price' => ['required', 'integer', 'min:0'],
            'redemption_price' => ['nullable', 'integer', 'min:0'],
            'min_years' => ['nullable', 'integer', 'min:1'],
            'max_years' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
            'reason' => $hasPriceChange ? ['required', 'string', 'min:3', 'max:1000'] : ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for the price change.',
            'reason.min' => 'The reason must be at least :min characters.',
            'reason.max' => 'The reason must not exceed :max characters.',
        ];
    }
}
