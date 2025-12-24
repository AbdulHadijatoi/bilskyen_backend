<?php

namespace App\Http\Requests;

use App\Constants\Vehicles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $currentYear = (int) date('Y');
        
        return [
            'registration_number' => [
                'required',
                'string',
                'min:1',
                'max:20',
                'regex:/^[A-Z0-9\-/\s]+$/i',
            ],
            'vin' => [
                'required',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]+$/i',
            ],
            'engine_number' => [
                'required',
                'string',
                'min:6',
                'max:20',
                'regex:/^[A-Z0-9\-/]+$/i',
            ],
            'make' => [
                'required',
                Rule::in(Vehicles::MAKES),
            ],
            'model' => [
                'required',
                'string',
                'min:1',
                'max:50',
            ],
            'variant' => [
                'required',
                'string',
                'min:1',
                'max:20',
            ],
            'year' => [
                'required',
                'integer',
                'min:1886',
                'max:' . $currentYear,
                Rule::in(Vehicles::getYears()),
            ],
            'vehicle_type' => [
                'required',
                Rule::in(Vehicles::TYPES),
            ],
            'odometer' => [
                'required',
                'numeric',
                'min:0',
                'max:12000000000000',
            ],
            'status' => [
                'required',
                Rule::in(Vehicles::STATUSES),
            ],
            'transmission_type' => [
                'required',
                Rule::in(Vehicles::TRANSMISSION_TYPES),
            ],
            'fuel_type' => [
                'required',
                Rule::in(Vehicles::FUEL_TYPES),
            ],
            'color' => [
                'required',
                'string',
                'min:1',
                'max:30',
            ],
            'condition' => [
                'required',
                Rule::in(Vehicles::CONDITIONS),
            ],
            'ownership_count' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
            'accident_history' => [
                'required',
                'boolean',
            ],
            'blacklist_flags' => [
                'nullable',
                'array',
            ],
            'blacklist_flags.*' => [
                Rule::in(Vehicles::BLACKLIST_TYPES),
            ],
            'inventory_date' => [
                'required',
                'date',
            ],
            'features' => [
                'required',
                'array',
                'min:1',
                'max:30',
            ],
            'features.*' => [
                'string',
                'min:1',
                'max:50',
            ],
            'listing_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'images' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],
            'images.*' => [
                'nullable',
            ],
            'description' => [
                'required',
                'string',
                'min:1',
                'max:5000',
            ],
            'pending_works' => [
                'nullable',
                'array',
                'max:30',
            ],
            'pending_works.*' => [
                'string',
                'min:1',
                'max:50',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'registration_number.regex' => 'Registration number can only contain letters, numbers, hyphens, slashes, or spaces.',
            'vin.size' => 'VIN must be exactly 17 characters.',
            'vin.regex' => 'VIN can only contain letters (except I, O, Q) and numbers.',
            'engine_number.regex' => 'Engine number can only contain letters, numbers, hyphens or slashes.',
            'make.in' => 'Please select a valid vehicle make.',
            'vehicle_type.in' => 'Please select a valid vehicle type.',
            'status.in' => 'Please select a valid vehicle status.',
            'transmission_type.in' => 'Please select a valid transmission type.',
            'fuel_type.in' => 'Please select a valid fuel type.',
            'condition.in' => 'Please select a valid vehicle condition.',
            'blacklist_flags.*.in' => 'Please select a valid blacklist type.',
            'year.in' => 'The year must be between 1886 and ' . date('Y') . '.',
            'images.required' => 'Please upload at least one image.',
            'images.max' => 'You can upload a maximum of 20 photos.',
            'features.required' => 'Please add at least one feature.',
            'features.max' => 'You can add a maximum of 30 features.',
        ];
    }
}


