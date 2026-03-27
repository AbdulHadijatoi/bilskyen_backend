<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicle:update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'registration' => ['nullable', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]+$/i'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'dmr_fact_vehicle_id' => ['sometimes', 'required', 'integer', 'exists:dmr_fact_vehicles,id'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'towing_weight' => ['nullable', 'integer', 'min:0'],
            'first_registration_date' => ['nullable', 'date'],
            'list_status_id' => ['sometimes', 'required', 'integer', 'exists:vehicle_list_statuses,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.min' => __('messages.validation.update_vehicle.price_min'),
            'list_status_id.exists' => __('messages.validation.update_vehicle.vehicle_list_status_exists'),
            'vin.size' => __('messages.validation.update_vehicle.vin_size'),
            'vin.regex' => __('messages.validation.update_vehicle.vin_regex'),
            'category_id.exists' => __('messages.validation.update_vehicle.category_exists'),
        ];
    }
}
