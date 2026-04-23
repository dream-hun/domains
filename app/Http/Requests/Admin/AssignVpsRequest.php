<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class AssignVpsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('vps_assign');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'instance_id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subscription_id.required' => 'A subscription must be selected.',
            'subscription_id.exists' => 'The selected subscription does not exist.',
            'instance_id.required' => 'A Contabo instance must be selected.',
            'instance_id.integer' => 'The instance ID must be a valid integer.',
        ];
    }
}
