<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class DomainLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('domain_transfer');
    }

    public function rules(): array
    {
        return [
            'lock' => ['required', 'boolean'],
        ];
    }
}
