<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreSettingRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('setting_create');
    }

    public function rules(): array
    {
        return [
            'title' => [
                'string',
                'nullable',
            ],
            'mobile' => [
                'string',
                'required',
            ],
            'email' => [
                'required',
            ],
            'address' => [
                'string',
                'nullable',
            ],
            'instagram' => [
                'string',
                'nullable',
            ],
            'tiktok' => [
                'string',
                'nullable',
            ],
            'facebook' => [
                'string',
                'nullable',
            ],
            'twitter' => [
                'string',
                'nullable',
            ],
            'youtube' => [
                'string',
                'nullable',
            ],
        ];
    }
}
