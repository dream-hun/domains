<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Hosting\HostingPlanStatus;
use App\Models\HostingPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateHostingPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('hosting_plan_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var HostingPlan|null $hostingPlan */
        $hostingPlan = $this->route('hosting_plan');
        $planId = $hostingPlan !== null ? $hostingPlan->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hosting_plans', 'slug')->ignore($planId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:255'],
            'is_popular' => ['nullable', 'boolean'],
            'status' => ['required', new Enum(HostingPlanStatus::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:hosting_categories,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A plan name is required.',
            'slug.required' => 'Please provide a unique slug for the plan.',
            'slug.unique' => 'That slug is already in use.',
            'tagline.required' => 'Tagline helps customers understand the plan quickly.',
            'status.required' => 'Select a status for the plan.',
            'category_id.required' => 'Select a hosting category.',
            'category_id.exists' => 'Selected category does not exist.',
        ];
    }
}
