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

        return [
            'type'                       => $allowEdit ? 'string|required' : 'string|nullable',
            'category'                   => $allowEdit ? 'string|required' : 'string|nullable',
            'sync_id'                    => 'nullable',
            'sync_payment_record_id'     => 'integer|nullable',
            'sync_transaction_number'    => 'integer|nullable',
            'distribution_type'          => 'string|nullable|max:255',
            'reference_no' => array_filter([
                'string',
                'nullable',
                $this->filled('sync_id') ? null : Rule::unique('transactions', 'reference_no')
                    ->where(fn ($query) => $query->where('customer_name', $this->input('customer.name')))
                    ->ignore($transactionId, 'id'),
            ]),
            'transaction_date'           => $allowEdit ? 'required|date|date_format:Y-m-d H:i:s' : 'nullable|date|date_format:Y-m-d H:i:s',
            'payment_date'               => $allowEdit ? 'required|date|date_format:Y-m-d H:i:s' : 'nullable|date|date_format:Y-m-d H:i:s',
            'customer.id'                => 'nullable',
            'customer.code'              => 'nullable',
            'customer_code'              => 'string|nullable',
            'customer.name'              => $allowEdit ? 'string|required' : 'string|nullable',
            'customer_name'             => 'string|nullable',
            'mode_of_payment'            => $allowEdit ? 'string|required' : 'string|nullable',
            'payment_type'               => 'string|required|in:full,partial,arcana',
            'bank.id'                    => 'integer|nullable|exists:banks,id',
            'bank.code'                  => 'nullable',
            'bank_code'                   => 'string|nullable',
            'bank.name'                  => 'string|nullable|required_if:mode_of_payment,cheque',
            'bank'                      => 'nullable',
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
            'cheque_no' => 'string|nullable',
            'cheque.date'                => 'nullable|date_format:Y-m-d H:i:s',
            'cheque_date'                => 'nullable|date_format:Y-m-d H:i:s',
            'amount'                     => $allowEdit ? 'numeric|required' : 'numeric|nullable',
            'remaining_balance'          => 'numeric|nullable',
            'charge.id'                  => 'integer|nullable',
            'charge.name'                => 'string|nullable',
            'slip.*.type'                => 'string|nullable',
            'slip.*.number'              => 'string|nullable',
            'slip.*.amount'              => 'numeric|nullable',
            'slip.*.actual_amount_paid'  => 'numeric|nullable',
            'remarks'                    => 'string|nullable',
            'charge_code'                 => 'string|nullable',
            'charge_name'                 => 'string|nullable',
            'company_code'                => 'string|nullable',
            'company_name'                => 'string|nullable',
            'business_unit_code'          => 'string|nullable',
            'business_unit_name'          => 'string|nullable',
            'department_code'             => 'string|nullable',
            'department_name'             => 'string|nullable',
            'unit_code'                   => 'string|nullable',
            'unit_name'                   => 'string|nullable',
            'sub_unit_code'               => 'string|nullable',
            'sub_unit_name'               => 'string|nullable',
            'location_code'               => 'string|nullable',
            'location_name'               => 'string|nullable'
        ];
    }

    public function messages(): array {
        return [
            'cheque.no.unique' => 'Cheque no already exists.',
        ];
    }
}
