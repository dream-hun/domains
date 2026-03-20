<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class OrderVpsLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('vps_order_license');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_type' => ['required', 'string', 'in:cPanel,Plesk'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_type.required' => 'A license type must be selected.',
            'license_type.in' => 'The selected license type is not supported.',
        ];
    }
}
