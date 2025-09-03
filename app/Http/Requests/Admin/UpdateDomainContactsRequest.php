<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDomainContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('domain_edit');
    }

    public function rules(): array
    {
        return [
            'registrant.contact_id' => ['required', 'exists:contacts,id'],
            'admin.contact_id' => ['required', 'exists:contacts,id'],
            'technical.contact_id' => ['required', 'exists:contacts,id'],
            'billing.contact_id' => ['required', 'exists:contacts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'registrant.contact_id.required' => 'A registrant contact is required',
            'admin.contact_id.required' => 'An administrative contact is required',
            'technical.contact_id.required' => 'A technical contact is required',
            'billing.contact_id.required' => 'A billing contact is required',
            '*.contact_id.exists' => 'The selected contact does not exist',
        ];
    }
}
