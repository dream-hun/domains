<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Ranking;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRankingConfigurationRequest extends FormRequest
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
            'win_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'loss_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'game_count_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'frequency_weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
