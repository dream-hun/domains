<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DomainType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'domain' => ['required', 'string', 'max:255'],
            'domain_type' => ['required', 'string', Rule::in(array_column(DomainType::cases(), 'value'))],
        ];
    }
}
