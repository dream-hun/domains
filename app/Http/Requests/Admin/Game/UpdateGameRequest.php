<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateGameRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'played_at' => ['required', 'date'],
        ];
    }
}
