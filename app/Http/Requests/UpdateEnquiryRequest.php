<?php

namespace App\Http\Requests;

use App\Constants\Enquiries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('enquiry:update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'subject' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:200',
            ],
            'message' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
            'type' => [
                'sometimes',
                'required',
                Rule::in(Enquiries::TYPES),
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(Enquiries::STATUSES),
            ],
            'source' => [
                'sometimes',
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


