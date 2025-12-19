<?php

namespace App\Http\Requests;

use App\Constants\Accountings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('financial-account:update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $accountTypes = array_keys(Accountings::FINANCIAL_ACCOUNT_TYPES);
        $allCategories = [];
        foreach (Accountings::FINANCIAL_ACCOUNT_TYPES as $categories) {
            $allCategories = array_merge($allCategories, $categories);
        }
        
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:50',
            ],
            'type' => [
                'sometimes',
                'required',
                Rule::in($accountTypes),
            ],
            'category' => [
                'sometimes',
                'required',
                Rule::in($allCategories),
            ],
            'is_cash_account' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('type') && $this->has('category')) {
                $type = $this->input('type');
                $category = $this->input('category');
                
                if (!isset(Accountings::FINANCIAL_ACCOUNT_TYPES[$type])) {
                    $validator->errors()->add('type', 'Invalid financial account type.');
                    return;
                }
                
                $validCategories = Accountings::FINANCIAL_ACCOUNT_TYPES[$type];
                if (!in_array($category, $validCategories)) {
                    $validator->errors()->add('category', 'Selected category is not valid for the selected type.');
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
            'type.in' => 'Please select a valid financial account type from the list.',
            'category.in' => 'Please select a valid financial account category from the list.',
            'is_cash_account.boolean' => 'Please specify if this is a cash account.',
        ];
    }
}

