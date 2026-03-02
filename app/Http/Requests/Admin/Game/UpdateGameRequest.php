<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Game;

use App\Enums\ResultStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|array|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'played_at' => ['required', 'date'],
            'points' => ['nullable', 'integer'],
            'comments' => ['nullable', 'string', 'max:500'],
            'result' => ['nullable', Rule::enum(ResultStatus::class)],

        ];
    }
}
