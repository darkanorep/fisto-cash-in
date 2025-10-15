<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'string|required',
            'category' => 'string|nullable',
            'reference_no' => 'string|nullable',
            'transaction_date' => 'date|date_format:Y-m-d|nullable',
            'payment_date' => 'date|nullable',
            'customer.id' => 'integer|required|exists:customers,id',
            'customer.name' => 'string|required',
            'mode_of_payment' => 'string|required',
            'bank.id' => 'integer|nullable|exists:banks,id|required_if:mode_of_payment,check',
            'bank.name' => 'string|nullable|required_if:mode_of_payment,check',
            'check.no' => 'string|nullable|required_if:mode_of_payment,check',
            'check.date' => 'date|nullable',
            'amount' => 'numeric|required',
            'remaining_balance' => 'numeric|nullable',
            'charge.id' => 'integer|nullable',
            'charge.name' => 'string|nullable',
            'remarks' => 'string|nullable',
        ];
    }
}
