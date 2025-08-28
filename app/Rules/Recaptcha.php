<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

final class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $value,
            'ip' => request()->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success') || $response->json('score') <= config('services.recaptcha.min_score')) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'Failed to validate reCAPTCHA.';
    }
}
