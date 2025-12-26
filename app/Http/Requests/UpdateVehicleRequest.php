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
            'location_id' => ['sometimes', 'required', 'integer', 'exists:locations,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_year_id' => ['nullable', 'integer', 'exists:model_years,id'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'fuel_type_id' => ['sometimes', 'required', 'integer', 'exists:fuel_types,id'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'battery_capacity' => ['nullable', 'integer', 'min:0'],
            'engine_power' => ['nullable', 'integer', 'min:0'],
            'towing_weight' => ['nullable', 'integer', 'min:0'],
            'ownership_tax' => ['nullable', 'integer', 'min:0'],
            'first_registration_date' => ['nullable', 'date'],
            'vehicle_list_status_id' => ['sometimes', 'required', 'integer', 'exists:vehicle_list_statuses,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'location_id.exists' => 'Please select a valid location.',
            'fuel_type_id.exists' => 'Please select a valid fuel type.',
            'price.min' => 'Price must be a positive number.',
            'vehicle_list_status_id.exists' => 'Please select a valid vehicle status.',
            'vin.size' => 'VIN must be exactly 17 characters.',
            'vin.regex' => 'VIN can only contain letters (except I, O, Q) and numbers.',
            'category_id.exists' => 'Please select a valid category.',
            'brand_id.exists' => 'Please select a valid brand.',
            'model_year_id.exists' => 'Please select a valid model year.',
        ];
    }
}
