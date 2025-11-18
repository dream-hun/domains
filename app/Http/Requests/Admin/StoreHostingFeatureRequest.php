<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreHostingFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hosting_feature_create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:hosting_features,slug'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'feature_category_id' => ['nullable', 'integer', 'exists:feature_categories,id'],
            'value_type' => ['required', 'string', Rule::in(['boolean', 'numeric', 'text', 'unlimited'])],
            'unit' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_highlighted' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The feature name is required.',
            'name.max' => 'The feature name may not be greater than 255 characters.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'feature_category_id.exists' => 'The selected feature category does not exist.',
            'value_type.in' => 'The value type must be one of: boolean, numeric, text, or unlimited.',
        ];
    }
}
