<?php

namespace App\Http\Requests;

use App\Constants\Accountings;
use App\Constants\Sales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sale:update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('vehicles', 'id'),
            ],
            'contact_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('contacts', 'id'),
            ],
            'received_to_financial_account_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id'),
            ],
            'sale_date' => [
                'sometimes',
                'required',
                'date',
                'before_or_equal:today',
            ],
            'sale_type' => [
                'sometimes',
                'required',
                Rule::in(Sales::TYPES),
            ],
            'sale_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:999999999',
            ],
            'received_amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'payment_mode' => [
                'sometimes',
                'required',
                Rule::in(Accountings::PAYMENT_MODES),
            ],
            'images' => [
                'nullable',
                'array',
                'max:20',
            ],
            'images.*' => [
                'nullable',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('received_amount') && $this->has('sale_price')) {
                $receivedAmount = $this->input('received_amount');
                $salePrice = $this->input('sale_price');
                
                if ($receivedAmount > $salePrice) {
                    $validator->errors()->add('received_amount', 'Received amount cannot exceed sale price.');
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
            'vehicle_id.exists' => 'Please select an existing vehicle from the system.',
            'contact_id.exists' => 'Please select an existing customer from the system.',
            'received_to_financial_account_id.exists' => 'Please select an existing financial account from the system.',
            'sale_date.before_or_equal' => 'Sale date cannot be in the future.',
            'sale_type.in' => 'Please select a sale type.',
            'payment_mode.in' => 'Please select a payment mode.',
            'sale_price.min' => 'Sale price must be greater than zero.',
            'images.max' => 'You can upload a maximum of 20 photos.',
        ];
    }
}

