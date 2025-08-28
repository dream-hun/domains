<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('user_edit');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],

            'email' => [
                'required',
                'unique:users,email,'.request()->route('user')->id,
            ],
            'email_verified_at' => [
                'date',
                'nullable',
            ],
            'password' => [
                'string',
                'nullable',
            ],
            'roles.*' => [
                'integer',
            ],
            'roles' => [
                'required',
                'array',
            ],
        ];
    }
}
