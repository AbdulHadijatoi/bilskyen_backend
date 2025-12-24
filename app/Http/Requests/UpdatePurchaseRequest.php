<?php

namespace App\Http\Requests;

use App\Constants\Accountings;
use App\Constants\Purchases;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('purchase:update');
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
            'paid_from_financial_account_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id'),
            ],
            'purchase_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'purchase_type' => [
                'sometimes',
                'required',
                Rule::in(Purchases::TYPES),
            ],
            'purchase_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'paid_amount' => [
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
            if ($this->has('paid_amount') && $this->has('purchase_price')) {
                $paidAmount = $this->input('paid_amount');
                $purchasePrice = $this->input('purchase_price');
                
                if ($paidAmount > $purchasePrice) {
                    $validator->errors()->add('paid_amount', 'Paid amount cannot exceed purchase price.');
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
            'contact_id.exists' => 'Please select an existing contact from the system.',
            'paid_from_financial_account_id.exists' => 'Please select an existing financial account from the system.',
            'purchase_type.in' => 'Please select a purchase type.',
            'payment_mode.in' => 'Please select a payment mode.',
            'images.max' => 'You can upload a maximum of 20 photos.',
        ];
    }
}


