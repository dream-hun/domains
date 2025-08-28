<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StorePermissionRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('permission_create');
    }

    public function rules(): array
    {
        return [
            'title' => [
                'string',
                'required',
            ],
        ];
    }
}
