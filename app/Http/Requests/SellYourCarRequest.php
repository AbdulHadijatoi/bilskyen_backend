<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            // registration
            // dmr_fact_vehicle_id
            // vin
            // title
            // slug
            // dealer_id
            // user_id
            // km_per_liter
            // co2_emission
            // electrical_consumption
            // engine_power_kw
            // engine_power_hp
            // engine_size_cc
            // engine_displacement_litres
            // first_registration_date
            // first_registration_year
            // nox_emission
            // particle_filter
            // axle_count
            // door_count
            // gear_count
            // max_speed
            // model_year
            // ncap_test
            // seats_min
            // seats_max
            // maximum_weight_kg
            // registration_status
            // last_registration_change
            // measurement_norm_id
            // listing_type_id
            // sales_type_id
            // price_type_id
            // category_id
            // price
            // km_driven
            // towing_weight
            // is_import
            // is_factory_new
            // charging_type
            // gear_type_id
            // list_status_id
            // address
            // postcode
            // description
            // condition_id
            // servicebog
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            
        ];
    }
}

