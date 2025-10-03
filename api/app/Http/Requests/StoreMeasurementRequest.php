<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'access_token' => ['required', 'string', 'exists:user_devices,access_token'],
            'measure_type' => ['required', 'string', 'in:temperature,humidity,battery,pressure'],
            'value' => ['required', 'numeric'],
            'recorded_at' => ['nullable', 'date'],
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
            'access_token.required' => 'The access token is required.',
            'access_token.exists' => 'Invalid access token.',
            'measure_type.required' => 'The measurement type is required.',
            'measure_type.in' => 'Invalid measurement type. Allowed types: temperature, humidity, battery, pressure.',
            'value.required' => 'The measurement value is required.',
            'value.numeric' => 'The measurement value must be a number.',
        ];
    }
}
