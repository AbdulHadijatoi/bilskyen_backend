<?php

namespace App\Http\Requests;

use App\Constants\Accountings;
use App\Constants\Expenses;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('expense:update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => [
                'sometimes',
                'required',
                'date',
            ],
            'narration' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:250',
            ],
            'category' => [
                'sometimes',
                'required',
                Rule::in(Expenses::getAllActivities()),
            ],
            'payment_mode' => [
                'sometimes',
                'required',
                Rule::in(Accountings::PAYMENT_MODES),
            ],
            'total_amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:999999999',
            ],
            'paid_amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'paid_from_financial_account_id' => [
                'sometimes',
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
            if ($this->has('paid_amount') && $this->has('total_amount')) {
                $paidAmount = $this->input('paid_amount');
                $totalAmount = $this->input('total_amount');
                
                if ($paidAmount > $totalAmount) {
                    $validator->errors()->add('paid_amount', 'Paid amount cannot exceed total amount.');
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


