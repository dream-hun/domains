<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

final class StoreDomainPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('domain_pricing_create');
    }

    public function rules(): array
    {
        return [
            'tld' => [
                'string',
                'required',
                'unique:domain_pricings',
            ],
            'register_price' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'transfer_price' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'renewal_price' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'grace' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'redemption_price' => [
                'string',
                'required',
            ],
        ];
    }
}
