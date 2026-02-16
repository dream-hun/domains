<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TldStatus;
use App\Enums\TldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateTldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('tld_edit');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tld', 'name')->ignore($this->route('tld'))],
            'type' => ['required', Rule::enum(TldType::class)],
            'status' => ['required', Rule::enum(TldStatus::class)],
        ];
    }
}
