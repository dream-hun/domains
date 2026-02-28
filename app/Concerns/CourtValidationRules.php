<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\CourtStatus;
use Illuminate\Validation\Rule;

trait CourtValidationRules
{
    /**
     * Get the validation rules used to validate courts.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array|string>>
     */
    protected function courtRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::enum(CourtStatus::class)],
        ];
    }
}
