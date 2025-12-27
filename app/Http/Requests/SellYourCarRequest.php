<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellYourCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For "Sell Your Car", any authenticated user can create a vehicle listing
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'registration' => ['required', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'max:17'],
            'price' => ['required', 'integer', 'min:0'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'listing_type_id' => ['nullable', 'integer', 'exists:listing_types,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:models,id'],
            'model_year_id' => ['nullable', 'integer', 'exists:model_years,id'],
            'fuel_type_id' => ['required', 'integer', 'exists:fuel_types,id'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'battery_capacity' => ['nullable', 'integer', 'min:0'],
            'engine_power' => ['nullable', 'integer', 'min:0'],
            'towing_weight' => ['nullable', 'integer', 'min:0'],
            'ownership_tax' => ['nullable', 'integer', 'min:0'],
            'first_registration_date' => ['nullable', 'date'],
            'vehicle_list_status_id' => ['required', 'integer', 'exists:vehicle_list_statuses,id'],
            'published_at' => ['nullable', 'date'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['integer', 'exists:equipments,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            
            // Auto-creation fields (for brands/models/years that don't exist)
            'brand_name' => ['nullable', 'string', 'max:255'],
            'model_name' => ['nullable', 'string', 'max:255'],
            'model_year_name' => ['nullable', 'string', 'max:10'],
            'model_year' => ['nullable', 'string', 'max:10'],
            
            // Vehicle details fields
            'description' => ['nullable', 'string'],
            'vin_location' => ['nullable', 'string', 'max:255'],
            'type_id' => ['nullable', 'integer', 'exists:types,id'],
            'version' => ['nullable', 'string', 'max:255'],
            'type_name' => ['nullable', 'string', 'max:255'],
            'use_id' => ['nullable', 'integer', 'exists:vehicle_uses,id'],
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'body_type_id' => ['nullable', 'integer', 'exists:body_types,id'],
            'price_type_id' => ['nullable', 'integer', 'exists:price_types,id'],
            'condition_id' => ['nullable', 'integer', 'exists:conditions,id'],
            'gear_type_id' => ['nullable', 'integer', 'exists:gear_types,id'],
            'sales_type_id' => ['nullable', 'integer', 'exists:sales_types,id'],
            'total_weight' => ['nullable', 'integer', 'min:0'],
            'vehicle_weight' => ['nullable', 'integer', 'min:0'],
            'engine_displacement' => ['nullable', 'integer', 'min:0'],
            'engine_cylinders' => ['nullable', 'integer', 'min:0'],
            'doors' => ['nullable', 'integer', 'min:0'],
            'minimum_seats' => ['nullable', 'integer', 'min:0'],
            'maximum_seats' => ['nullable', 'integer', 'min:0'],
            'top_speed' => ['nullable', 'integer', 'min:0'],
            'fuel_efficiency' => ['nullable', 'numeric', 'min:0'],
            'airbags' => ['nullable', 'integer', 'min:0'],
            'ncap_five' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a vehicle title.',
            'registration.required' => 'Please provide a registration number.',
            'location_id.required' => 'Please select a location.',
            'location_id.exists' => 'Please select a valid location.',
            'fuel_type_id.required' => 'Please select a fuel type.',
            'fuel_type_id.exists' => 'Please select a valid fuel type.',
            'price.required' => 'Please provide a price.',
            'price.min' => 'Price must be a positive number.',
            'vehicle_list_status_id.required' => 'Please select a vehicle status.',
            'vehicle_list_status_id.exists' => 'Please select a valid vehicle status.',
            'images.*.image' => 'All uploaded files must be images.',
            'images.*.max' => 'Each image must be less than 10MB.',
        ];
    }
}

