<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class ModerateGameRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:approved,rejected,flagged'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
