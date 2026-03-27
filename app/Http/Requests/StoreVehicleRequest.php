<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicle:create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'registration' => ['nullable', 'string', 'max:20'],
            'dmr_fact_vehicle_id' => ['required', 'integer', 'exists:dmr_fact_vehicles,id'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'gear_type_id' => ['nullable', 'integer', 'exists:gear_types,id'],
            'price' => ['required', 'integer', 'min:0'],
            'charging_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'condition_id' => ['nullable', 'integer', 'exists:conditions,id'],
            'servicebog' => ['nullable', 'string', 'max:50'],
            'list_status_id' => ['required', 'integer', 'exists:vehicle_list_statuses,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a vehicle title.',
            'dmr_fact_vehicle_id.required' => 'A DMR vehicle record is required.',
            'dmr_fact_vehicle_id.exists' => 'Please select a valid DMR vehicle.',
            'price.required' => 'Please provide a price.',
            'price.min' => 'Price must be a positive number.',
            'list_status_id.required' => 'Please select a vehicle status.',
            'list_status_id.exists' => 'Please select a valid vehicle status.',
        ];
    }
}
