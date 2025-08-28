<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class DomainTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'domain_name' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-\.]+$/'],
            'auth_info' => ['required', 'string', 'max:255'],
            'registrant_contact_id' => ['required', 'exists:contacts,id'],
            'admin_contact_id' => ['nullable', 'exists:contacts,id'],
            'tech_contact_id' => ['nullable', 'exists:contacts,id'],
            'billing_contact_id' => ['nullable', 'exists:contacts,id'],
            'nameservers' => ['array', 'min:2', 'max:4'],
            'nameservers.*' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9\-\.]+$/'],
        ];
    }
}
