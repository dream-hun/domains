<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9])[a-zA-Z\d\W]+$/'],
            'recaptcha_token' => ['required', new Recaptcha()],
        ];
    }

    /**
     * Get the custom validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [

            'first_name.required' => 'Please enter your first name.',
            'first_name.string' => 'Your first name must be valid text.',
            'first_name.max' => 'Your first name cannot exceed 255 characters.',

            'last_name.required' => 'Please enter your last name.',
            'last_name.string' => 'Your last name must be valid text.',
            'last_name.max' => 'Your last name cannot exceed 255 characters.',

            'email.required' => 'Please enter your email address.',
            'email.string' => 'Your email address must be valid text.',
            'email.lowercase' => 'Your email address must be in lowercase.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Your email address cannot exceed 255 characters.',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',

            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character, and contain only letters, numbers, and symbols.',
            'password.min' => 'Your password must be at least 8 characters long.',

            'recaptcha_token.required' => 'Please complete the reCAPTCHA verification.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'password' => 'password',
            'recaptcha_token' => 'reCAPTCHA',
        ];
    }
}
