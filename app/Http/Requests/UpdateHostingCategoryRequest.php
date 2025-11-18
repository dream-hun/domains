<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Hosting\CategoryStatus;
use App\Models\HostingCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

final class UpdateHostingCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('hosting_category_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $category = $this->route('hosting_category');

        $slugRule = ['required', 'string', 'max:255'];
        if ($category instanceof HostingCategory) {
            $slugRule[] = 'unique:hosting_categories,slug,'.$category->id;
        } else {
            $slugRule[] = 'unique:hosting_categories,slug';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'icon' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', new Enum(CategoryStatus::class)],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'slug.required' => 'Slug is required.',
            'slug.unique' => 'Slug has already been taken.',
            'icon.required' => 'Icon is required.',
            'status.required' => 'Status is required.',
            'status.enum' => 'Selected status is invalid.',
        ];
    }
}
