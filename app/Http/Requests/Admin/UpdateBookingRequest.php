<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateBookingRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('booking_edit');
    }

    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                'integer',
            ],
            'name' => [
                'string',
                'required',
            ],
            'email' => [
                'required',
            ],
            'mobile' => [
                'string',
                'required',
            ],
            'date_and_time' => [
                'required',
                'date_format:'.config('panel.date_format').' '.config('panel.time_format'),
            ],
            'status' => [
                'required',
            ],
        ];
    }
}
