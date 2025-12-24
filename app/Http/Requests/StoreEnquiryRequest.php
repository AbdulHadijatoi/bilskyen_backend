<?php

namespace App\Http\Requests;

use App\Constants\Enquiries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('enquiry:create');
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
            'type.in' => 'Enquiry type must be one of the predefined values.',
            'status.in' => 'Enquiry status must be one of the predefined values.',
            'source.in' => 'Enquiry source must be one of the predefined values.',
            'contact_id.exists' => 'Please provide a valid contact reference.',
            'user_id.exists' => 'Please provide a valid user reference.',
            'vehicle_id.exists' => 'Please provide a valid vehicle reference.',
        ];
    }
}


