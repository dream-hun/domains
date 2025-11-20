<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('audit_log_access');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:190'],
            'subject_type' => ['nullable', 'string', 'max:255'],
        ];
    }
}
