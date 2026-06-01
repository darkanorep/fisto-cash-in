<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        $routeTransaction = $this->route('transaction');

        // Handle both model binding (object) and raw ID
        $transactionId = $routeTransaction instanceof \App\Models\Transaction
            ? $routeTransaction->id
            : $routeTransaction;

        $transaction = $transactionId
            ? \App\Models\Transaction::withTrashed()->find($transactionId)
            : null;

        $isPending = $this->input('status') === 'pending' || $transaction?->status === 'pending';
        $isReturnUntagged = ($this->input('status') === 'return' || $transaction?->status === 'return')
            && (!$this->input('is_tagged') && !$transaction?->is_tagged);

        $allowEdit = $isPending || $isReturnUntagged;

//        dd([
//            'transactionId'  => $transactionId,
//            'customer_name'  => $this->input('customer.name'),
//            'reference_no'   => $this->input('reference_no'),
//        ]);

        return [
            'type'                       => $allowEdit ? 'string|required' : 'string|nullable',
            'category'                   => $allowEdit ? 'string|required' : 'string|nullable',
            'sync_id'                    => 'nullable',
            'sync_payment_record_id'     => 'integer|nullable',
            'distribution_type'          => 'string|nullable|max:255',
            'reference_no' => [
                'string',
                'nullable',
                Rule::unique('transactions', 'reference_no')
                    ->where(fn ($query) => $query->where('customer_name', $this->input('customer.name')))
                    ->ignore($transactionId, 'id')
            ],
            'transaction_date'           => $allowEdit ? 'required|date|date_format:Y-m-d H:i:s' : 'nullable|date|date_format:Y-m-d H:i:s',
            'payment_date'               => $allowEdit ? 'required|date|date_format:Y-m-d H:i:s' : 'nullable|date|date_format:Y-m-d H:i:s',
            'customer.id'                => 'nullable',
            'customer.code'              => 'string|nullable',
            'customer.name'              => $allowEdit ? 'string|required' : 'string|nullable',
            'mode_of_payment'            => $allowEdit ? 'string|required' : 'string|nullable',
            'bank.id'                    => 'integer|nullable|exists:banks,id|required_if:mode_of_payment,cheque',
            'bank.code'                  => 'nullable',
            'bank.name'                  => 'string|nullable|required_if:mode_of_payment,cheque',
            'cheque.no'                  => [
                'string',
                'nullable',
                'required_if:mode_of_payment,cheque',
                Rule::unique('transactions', 'check_no')
                    ->where(function ($query) {
                        $query->where('bank_name', $this->input('bank.name'))
                            ->where('mode_of_payment', 'cheque');
                    })
                    ->ignore($transactionId, 'id'),
            ],
            'cheque.date'                => 'nullable|date_format:Y-m-d H:i:s',
            'amount'                     => $allowEdit ? 'numeric|required' : 'numeric|nullable',
            'remaining_balance'          => 'numeric|nullable',
            'charge.id'                  => 'integer|nullable',
            'charge.name'                => 'string|nullable',
            'slip.*.type'                => 'string|nullable',
            'slip.*.number'              => 'string|nullable',
            'slip.*.amount'              => 'numeric|nullable',
            'slip.*.actual_amount_paid'  => 'numeric|nullable',
            'remarks'                    => 'string|nullable',
        ];
    }

    public function messages(): array {
        return [
            'cheque.no.unique' => 'Cheque no already exists.',
        ];
    }
}
