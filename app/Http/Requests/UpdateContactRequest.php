<?php

namespace App\Http\Requests;

use App\Constants\Contacts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('contact:update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'type' => [
                'sometimes',
                'required',
                Rule::in(Contacts::TYPES),
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^[+\d]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'source' => [
                'sometimes',
                'required',
                Rule::in(Contacts::SOURCES),
            ],
            'address' => [
                'sometimes',
                'required',
                'string',
                'min:1',
            ],
            'images' => [
                'nullable',
                'array',
                'max:20',
            ],
            'images.*' => [
                'nullable',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ];

        // Conditional validation based on type
        if ($this->input('type') === 'individual') {
            $rules['name'] = [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
            ];
        } elseif ($this->input('type') === 'business') {
            $rules['company_name'] = [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
            ];
            $rules['contact_person'] = [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
            ];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type', $this->route('contact')->type ?? null);
            
            if ($type === 'individual' && $this->has('name') && empty($this->input('name'))) {
                $validator->errors()->add('name', 'Name is required for individual contacts.');
            }
            
            if ($type === 'business') {
                if ($this->has('company_name') && empty($this->input('company_name'))) {
                    $validator->errors()->add('company_name', 'Company name is required for business contacts.');
                }
                if ($this->has('contact_person') && empty($this->input('contact_person'))) {
                    $validator->errors()->add('contact_person', 'Contact person is required for business contacts.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Contact type must be either individual or business.',
            'phone.regex' => 'Phone number must contain only digits and optionally a plus sign.',
            'phone.min' => 'Phone number must be at least 8 digits.',
            'phone.max' => 'Phone number must not exceed 15 digits.',
            'source.in' => 'Contact source must be one of the predefined values.',
            'images.max' => 'You can upload a maximum of 20 photos.',
        ];
    }
}


