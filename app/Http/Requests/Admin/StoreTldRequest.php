<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TldStatus;
use App\Enums\TldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreTldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('tld_create');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tld,name'],
            'type' => ['required', Rule::enum(TldType::class)],
            'status' => ['required', Rule::enum(TldStatus::class)],
        ];
    }
}
