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
        $transactionId = $this->route('transaction');
        $transaction = $transactionId ? \App\Models\Transaction::find($transactionId) : null;

        $isPending = $this->input('status') === 'pending' || $transaction?->status === 'pending';
        $isReturnUntagged = ($this->input('status') === 'return' || $transaction?->status === 'return')
            && (!$this->input('is_tagged') && !$transaction?->is_tagged);

        $allowEdit = $isPending || $isReturnUntagged;

        return [
            'type' => $allowEdit ? 'string|required' : 'string|nullable',
            'category' => $allowEdit ? 'string|required' : 'string|nullable',
            'reference_no' => $allowEdit ? 'string|required' : 'string|nullable',
            'transaction_date' => $allowEdit ? 'required|date|date_format:Y-m-d H:i:s' : 'nullable|date|date_format:Y-m-d H:i:s',
            'payment_date' => $allowEdit ? 'required|date|date_format:Y-m-d H:i:s' : 'nullable|date|date_format:Y-m-d H:i:s',
            'customer.id' => $allowEdit ? 'integer|required|exists:customers,id' : 'integer|nullable|exists:customers,id',
            'customer.name' => $allowEdit ? 'string|required' : 'string|nullable',
            'mode_of_payment' => $allowEdit ? 'string|required' : 'string|nullable',
            'bank.id' => 'integer|nullable|exists:banks,id|required_if:mode_of_payment,check',
            'bank.name' => 'string|nullable|required_if:mode_of_payment,check',
            'check.no' => 'string|nullable|required_if:mode_of_payment,check',
            'check.date' => 'nullable|date_format:Y-m-d H:i:s',
            'cheque.no' => 'string|nullable|required_if:mode_of_payment,cheque',
            'cheque.date' => 'nullable|date_format:Y-m-d H:i:s',
            'amount' => $allowEdit ? 'numeric|required' : 'numeric|nullable',
            'remaining_balance' => 'numeric|nullable',
            'charge.id' => 'integer|nullable',
            'charge.name' => 'string|nullable',
            'slip.*.type' => 'string|nullable',
            'slip.*.number' => 'string|nullable',
            'slip.*.amount' => 'numeric|nullable',
            'slip.*.actual_amount_paid' => 'numeric|nullable',
            'remarks' => 'string|nullable',
        ];
    }
}
