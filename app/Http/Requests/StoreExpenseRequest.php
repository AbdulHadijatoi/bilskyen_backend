<?php

namespace App\Http\Requests;

use App\Constants\Accountings;
use App\Constants\Expenses;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('expense:create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
            ],
            'narration' => [
                'required',
                'string',
                'min:3',
                'max:250',
            ],
            'category' => [
                'required',
                Rule::in(Expenses::getAllActivities()),
            ],
            'payment_mode' => [
                'required',
                Rule::in(Accountings::PAYMENT_MODES),
            ],
            'total_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999',
            ],
            'paid_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'paid_from_financial_account_id' => [
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id'),
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicles', 'id'),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id'),
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:500',
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
            $paidAmount = $this->input('paid_amount');
            $totalAmount = $this->input('total_amount');
            
            if ($paidAmount > $totalAmount) {
                $validator->errors()->add('paid_amount', 'Paid amount cannot exceed total amount.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category.in' => 'Please select a valid expense category.',
            'payment_mode.in' => 'Please select a valid payment mode.',
            'paid_from_financial_account_id.exists' => 'Please select an existing financial account from the system.',
            'vehicle_id.exists' => 'Please select an existing vehicle from the system.',
            'contact_id.exists' => 'Please select an existing contact from the system.',
            'total_amount.min' => 'Amount must be greater than zero.',
            'images.max' => 'You can upload a maximum of 20 photos.',
        ];
    }
}

