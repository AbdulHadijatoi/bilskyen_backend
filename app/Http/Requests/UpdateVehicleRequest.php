<?php

namespace App\Http\Requests;

use App\Constants\Vehicles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $currentYear = (int) date('Y');
        
        return [
            'registration_number' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:20',
                'regex:/^[A-Z0-9\-/\s]+$/i',
            ],
            'vin' => [
                'sometimes',
                'required',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]+$/i',
            ],
            'engine_number' => [
                'sometimes',
                'required',
                'string',
                'min:6',
                'max:20',
                'regex:/^[A-Z0-9\-/]+$/i',
            ],
            'make' => [
                'sometimes',
                'required',
                Rule::in(Vehicles::MAKES),
            ],
            'model' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:50',
            ],
            'variant' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:20',
            ],
            'year' => [
                'sometimes',
                'required',
                'integer',
                'min:1886',
                'max:' . $currentYear,
                Rule::in(Vehicles::getYears()),
            ],
            'vehicle_type' => [
                'sometimes',
                'required',
                Rule::in(Vehicles::TYPES),
            ],
            'odometer' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:12000000000000',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(Vehicles::STATUSES),
            ],
            'transmission_type' => [
                'sometimes',
                'required',
                Rule::in(Vehicles::TRANSMISSION_TYPES),
            ],
            'fuel_type' => [
                'sometimes',
                'required',
                Rule::in(Vehicles::FUEL_TYPES),
            ],
            'color' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:30',
            ],
            'condition' => [
                'sometimes',
                'required',
                Rule::in(Vehicles::CONDITIONS),
            ],
            'ownership_count' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
            'accident_history' => [
                'sometimes',
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
                'sometimes',
                'required',
                'date',
            ],
            'features' => [
                'sometimes',
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
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'images' => [
                'sometimes',
                'array',
                'min:1',
                'max:20',
            ],
            'images.*' => [
                'nullable',
            ],
            'description' => [
                'sometimes',
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
            'images.max' => 'You can upload a maximum of 20 photos.',
            'features.max' => 'You can add a maximum of 30 features.',
        ];
    }
}


