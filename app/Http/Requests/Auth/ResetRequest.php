<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class ResetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9])[a-zA-Z\d\W]+$/', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Token is required',
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password does not match',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character, and contain only letters, numbers, and symbols.',

        ];
    }
}
