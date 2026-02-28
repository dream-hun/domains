<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class PlayerProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date_of_birth' => ['required', 'date', 'before:today'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'city' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'bio' => ['required', 'string'],
            'position' => ['required', 'string', 'max:100'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
