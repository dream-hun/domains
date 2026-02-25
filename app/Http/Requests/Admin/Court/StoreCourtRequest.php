<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Court;

use App\Concerns\CourtValidationRules;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCourtRequest extends FormRequest
{
    use CourtValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->courtRules();
    }
}
