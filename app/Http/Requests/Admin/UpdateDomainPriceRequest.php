<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateDomainPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('domain_pricing_edit');
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid') ?? $this->route('domain-price') ?? null;

        $tldRules = ['required', 'string'];

        $tldRules[] = $uuid ? 'unique:domain_prices,tld,'.$uuid.',uuid' : 'unique:domain_prices,tld';

        return [
            'tld' => $tldRules,
            'type' => ['required', 'string', 'in:local,international'],
            'register_price' => ['required', 'integer', 'min:0'],
            'renewal_price' => ['required', 'integer', 'min:0'],
            'transfer_price' => ['required', 'integer', 'min:0'],
            'redemption_price' => ['nullable', 'integer', 'min:0'],
            'min_years' => ['nullable', 'integer', 'min:1'],
            'max_years' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }
}
