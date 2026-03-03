<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class OverrideGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,rejected'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
