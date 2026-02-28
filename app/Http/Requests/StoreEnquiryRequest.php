<?php

namespace App\Http\Requests;

use App\Constants\Enquiries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Allow both authenticated and guest users to create enquiries.
     */
    public function authorize(): bool
    {
        // Allow guest users (user can be null)
        $user = $this->user();
        if (!$user) {
            return true;
        }
        
        // For authenticated users, check permission
        return $user->can('enquiry:create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'subject' => [
                'required',
                'string',
                'min:3',
                'max:200',
            ],
            'message' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
            'type' => [
                'required',
                Rule::in(Enquiries::TYPES),
            ],
            'status' => [
                'required',
                Rule::in(Enquiries::STATUSES),
            ],
            'source' => [
                'required',
                Rule::in(Enquiries::SOURCES),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id'),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicles', 'id'),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => __('messages.validation.enquiry.type_in'),
            'status.in' => __('messages.validation.enquiry.status_in'),
            'source.in' => __('messages.validation.enquiry.source_in'),
            'contact_id.exists' => __('messages.validation.enquiry.contact_id_exists'),
            'user_id.exists' => __('messages.validation.enquiry.user_id_exists'),
            'vehicle_id.exists' => __('messages.validation.enquiry.vehicle_id_exists'),
        ];
    }
}


