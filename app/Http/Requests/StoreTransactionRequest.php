<?php

namespace App\Http\Requests;

use App\Constants\Accountings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('transaction:create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(Accountings::getTransactionTypes()),
            ],
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
            'entries' => [
                'required',
                'array',
                'min:2',
                'max:100',
            ],
            'entries.*.financial_account_id' => [
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id'),
            ],
            'entries.*.amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'entries.*.type' => [
                'required',
                Rule::in(Accountings::ENTRY_TYPES),
            ],
            'entries.*.description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $entries = $this->input('entries', []);
            
            // Check at least one debit and one credit
            $hasDebit = false;
            $hasCredit = false;
            $debitTotal = 0;
            $creditTotal = 0;
            
            foreach ($entries as $index => $entry) {
                if ($entry['type'] === 'debit') {
                    $hasDebit = true;
                    $debitTotal += $entry['amount'];
                } else {
                    $hasCredit = true;
                    $creditTotal += $entry['amount'];
                }
            }
            
            if (!$hasDebit || !$hasCredit) {
                $validator->errors()->add('entries', 'At least one debit and one credit entry are required.');
            }
            
            // Check balanced entries (with tolerance for floating point)
            if (abs($debitTotal - $creditTotal) > 0.01) {
                $validator->errors()->add('entries', 'Total debit amount must equal total credit amount.');
            }
            
            // Check not all zero
            $allZero = true;
            foreach ($entries as $entry) {
                if ($entry['amount'] > 0) {
                    $allZero = false;
                    break;
                }
            }
            
            if ($allZero) {
                $validator->errors()->add('entries', 'Transaction amounts must not all be zero.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Transaction type is required. Please select a valid type.',
            'entries.min' => 'At least two entries are required. Please add transaction entries.',
            'entries.max' => 'You can have a maximum of 100 entries.',
            'entries.*.financial_account_id.exists' => 'Please provide a valid account reference.',
            'entries.*.type.in' => 'Transaction type is required. Please select a valid type.',
            'images.max' => 'You can upload a maximum of 20 photos.',
        ];
    }
}

