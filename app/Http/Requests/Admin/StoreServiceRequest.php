<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreServiceRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('service_create');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'price' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'featured_image' => [
                'required',
            ],
            'duration' => [
                'required',
                'string',
            ],
        ];
    }
}
