<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateHostingPlanFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hosting_plan_feature_edit');
    }

    public function rules(): array
    {
        return [
            'hosting_category_id' => ['sometimes', 'required', 'integer', 'exists:hosting_categories,id'],
            'hosting_plan_id' => ['sometimes', 'required', 'integer', 'exists:hosting_plans,id'],
            'hosting_feature_id' => ['sometimes', 'required', 'integer', 'exists:hosting_features,id'],
            'feature_value' => ['nullable', 'string', 'max:255'],
            'is_unlimited' => ['nullable', 'boolean'],
            'custom_text' => ['nullable', 'string'],
            'is_included' => ['nullable', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'hosting_category_id.required' => 'The hosting category is required.',
            'hosting_category_id.exists' => 'The selected hosting category does not exist.',
            'hosting_plan_id.required' => 'The hosting plan is required.',
            'hosting_plan_id.exists' => 'The selected hosting plan does not exist.',
            'hosting_feature_id.required' => 'The hosting feature is required.',
            'hosting_feature_id.exists' => 'The selected hosting feature does not exist.',
            'sort_order.min' => 'The sort order must be at least 0.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hostingPlanFeature = $this->route('hosting_plan_feature');

            if ($hostingPlanFeature && $this->filled('hosting_plan_id') && $this->filled('hosting_feature_id')) {
                $exists = HostingPlanFeature::query()
                    ->where('hosting_plan_id', $this->input('hosting_plan_id'))
                    ->where('hosting_feature_id', $this->input('hosting_feature_id'))
                    ->where('id', '!=', $hostingPlanFeature->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('hosting_feature_id', 'This feature is already assigned to the selected hosting plan.');
                }
            }

            if ($this->filled('hosting_category_id') && $this->filled('hosting_plan_id')) {
                $planCategoryId = HostingPlan::query()
                    ->whereKey($this->input('hosting_plan_id'))
                    ->value('category_id');

                if ($planCategoryId !== null && (int) $planCategoryId !== (int) $this->input('hosting_category_id')) {
                    $validator->errors()->add('hosting_plan_id', 'The selected hosting plan does not belong to the chosen hosting category.');
                }
            }
        });
    }
}
