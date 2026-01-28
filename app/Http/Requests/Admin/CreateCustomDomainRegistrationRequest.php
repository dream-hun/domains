<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class CreateCustomDomainRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('domain_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Domain registration fields
            'domain_name' => ['required', 'string', 'max:255', 'unique:domains,name'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'years' => ['required', 'integer', 'min:1', 'max:10'],
            'registrant_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'admin_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'technical_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'billing_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'nameserver_1' => ['nullable', 'string', 'max:255'],
            'nameserver_2' => ['nullable', 'string', 'max:255'],
            'nameserver_3' => ['nullable', 'string', 'max:255'],
            'nameserver_4' => ['nullable', 'string', 'max:255'],

            // Custom domain price fields
            'domain_custom_price' => ['required', 'numeric', 'min:0'],
            'domain_custom_price_currency' => [
                'required',
                'string',
                'size:3',
                Rule::exists('currencies', 'code'),
            ],
            'domain_custom_price_notes' => ['nullable', 'string', 'max:1000'],

            // Subscription option
            'subscription_option' => ['required', Rule::in(['none', 'create_new', 'link_existing'])],

            // Fields for creating new subscription
            'hosting_plan_id' => ['required_if:subscription_option,create_new', 'nullable', 'integer', 'exists:hosting_plans,id'],
            'billing_cycle' => ['required_if:subscription_option,create_new', 'nullable', 'string', Rule::in(['monthly', 'annually'])],
            'hosting_starts_at' => ['required_if:subscription_option,create_new', 'nullable', 'date'],
            'hosting_expires_at' => ['required_if:subscription_option,create_new', 'nullable', 'date', 'after:hosting_starts_at'],
            'hosting_auto_renew' => ['sometimes', 'boolean'],

            // Custom hosting subscription price fields
            'hosting_custom_price' => ['nullable', 'numeric', 'min:0'],
            'hosting_custom_price_currency' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('currencies', 'code'),
                'required_with:hosting_custom_price',
            ],
            'hosting_custom_price_notes' => ['nullable', 'string', 'max:1000'],

            // Field for linking existing subscription
            'existing_subscription_id' => [
                'required_if:subscription_option,link_existing',
                'nullable',
                'integer',
                'exists:subscriptions,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Domain fields
            'domain_name.required' => 'Domain name is required.',
            'domain_name.unique' => 'This domain is already registered in the system.',
            'user_id.required' => 'Domain owner is required.',
            'user_id.exists' => 'Selected user does not exist.',
            'years.required' => 'Registration period is required.',
            'years.min' => 'Minimum registration period is 1 year.',
            'years.max' => 'Maximum registration period is 10 years.',
            'registrant_contact_id.required' => 'Registrant contact is required.',
            'registrant_contact_id.exists' => 'Selected registrant contact does not exist.',
            'admin_contact_id.required' => 'Admin contact is required.',
            'admin_contact_id.exists' => 'Selected admin contact does not exist.',
            'technical_contact_id.required' => 'Technical contact is required.',
            'technical_contact_id.exists' => 'Selected technical contact does not exist.',
            'billing_contact_id.required' => 'Billing contact is required.',
            'billing_contact_id.exists' => 'Selected billing contact does not exist.',

            // Domain custom price
            'domain_custom_price.required' => 'Domain custom price is required.',
            'domain_custom_price.numeric' => 'Domain custom price must be a number.',
            'domain_custom_price.min' => 'Domain custom price must be at least 0.',
            'domain_custom_price_currency.required' => 'Currency is required for domain custom price.',
            'domain_custom_price_currency.exists' => 'Selected currency does not exist.',

            // Subscription option
            'subscription_option.required' => 'Please select a subscription option.',
            'subscription_option.in' => 'Invalid subscription option selected.',

            // Hosting subscription fields
            'hosting_plan_id.required_if' => 'Hosting plan is required when creating a new subscription.',
            'hosting_plan_id.exists' => 'Selected hosting plan does not exist.',
            'billing_cycle.required_if' => 'Billing cycle is required when creating a new subscription.',
            'billing_cycle.in' => 'Billing cycle must be monthly or annually.',
            'hosting_starts_at.required_if' => 'Start date is required when creating a new subscription.',
            'hosting_expires_at.required_if' => 'Expiry date is required when creating a new subscription.',
            'hosting_expires_at.after' => 'Expiry date must be after the start date.',

            // Hosting custom price
            'hosting_custom_price.numeric' => 'Hosting custom price must be a number.',
            'hosting_custom_price.min' => 'Hosting custom price must be at least 0.',
            'hosting_custom_price_currency.required_with' => 'Currency is required when custom hosting price is provided.',
            'hosting_custom_price_currency.exists' => 'Selected currency does not exist.',

            // Existing subscription
            'existing_subscription_id.required_if' => 'Please select a subscription to link.',
            'existing_subscription_id.exists' => 'Selected subscription does not exist.',
        ];
    }
}
