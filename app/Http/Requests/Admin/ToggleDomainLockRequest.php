<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Domain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class ToggleDomainLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Domain|null $domain */
        $domain = $this->route('domain');

        if ($domain instanceof Domain) {
            return Gate::allows('domain_edit') || $domain->owner_id === $this->user()?->id;
        }

        return Gate::allows('domain_edit');
    }

    public function rules(): array
    {
        return [
            'lock' => ['nullable', 'boolean'],
        ];
    }
}
