<?php

namespace App\Services;


use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class TransactionService
{
    use ActivityLogTrait;
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getAllTransactions($request)
    {
        $query = $this->transaction->query()->with(['bank'])->where('user_id', auth()->id());
        $status = $request->input('status');

        // Apply status filter if provided
        if ($status) {
            switch($status) {
                case 'return-request':
                    $query->where('status', 'return')
                        ->whereIn('is_tagged', [true, false])
                        ->whereNotNull('reason');
                    break;

                default:
                    $query->status($status); // This calls your scope
                    break;
            }
        } else {
            // Only exclude void and return statuses when no status filter is provided
            $query->whereNotIn('status', ['return', 'void']);
        }

        return $query->useFilters()->dynamicPaginate();
    }

    public function createTransaction($data)
    {
        $transactionData = [
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'customer_id' => $data['customer']['id'],
            'customer_name' => $data['customer']['name'],
            'mode_of_payment' => $data['mode_of_payment'],
            'bank_id' => $data['bank']['id'] ?? null,
            'bank_name' => $data['bank']['name'] ?? null,
            'check_no' => $data['cheque']['no'] ?? $data['check']['no'] ?? null,
            'check_date' => $data['cheque']['date'] ?? $data['check']['date'] ?? null,
            'amount' => $data['amount'],
            'remaining_balance' => $data['remaining_balance'] ?? 0,
            'charge_id' => $data['charge']['id'],
            'charge_name' => $data['charge']['name'],
            'remarks' => $data['remarks'] ?? null,
        ];

        $transaction = $this->transaction->create($transactionData);

        if (!empty($data['slip'])) {
            foreach ($data['slip'] as $slip) {
                $transaction->slips()->create([
                    'type' => $slip['type'],
                    'number' => $slip['number'],
                    'amount' => $slip['amount'],
                    'actual_amount_paid' => $slip['actual_amount_paid'],
                ]);
            }

            $this->logActivityOn($transaction, 'Slips Added for Transaction', ['slips' => $data['slip']], 'created');
        }

        $this->logActivityOn($transaction, 'Transaction Created', $transactionData, 'created');

        return $transaction;
    }

    public function getTransactionById($id)
    {
        return $this->transaction->with([
            'slips',
            'bank',
            ])->find($id);
    }

    public function updateTransaction($transaction, $data)
    {
        $transactionData = [
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'customer_id' => $data['customer']['id'],
            'customer_name' => $data['customer']['name'],
            'mode_of_payment' => $data['mode_of_payment'],
            'bank_id' => $data['bank']['id'] ?? null,
            'bank_name' => $data['bank']['name'] ?? null,
            'check_no' => $data['cheque']['no'] ?? null,
            'check_date' => $data['cheque']['date'] ?? null,
            'amount' => $data['amount'],
            'remaining_balance' => $data['remaining_balance'] ?? 0,
            'charge_id' => $data['charge']['id'],
            'charge_name' => $data['charge']['name'],
            'remarks' => $data['remarks'] ?? null,
            'status' => 'pending', // Reset status to pending on update
        ];

        $transaction->update($transactionData);

        if (!empty($data['slip'])) {
            $transaction->slips()->delete();

            foreach ($data['slip'] as $slip) {
                $transaction->slips()->create([
                    'type' => $slip['type'],
                    'number' => $slip['number'],
                    'amount' => $slip['amount'],
                    'actual_amount_paid' => $slip['actual_amount_paid'],
                ]);
            }

            $this->logActivityOn($transaction, 'Slips Updated for Transaction', ['slips' => $data['slip']], 'updated');
        }

        $this->logActivityOn($transaction, 'Transaction Updated', $transactionData, 'updated');

        return $transaction;
    }

    public function voidTransaction($transaction, $data)
    {
        $transactionData = [
            'status' => 'void',
            'reason' => $data['reason'] ?? null,
        ];

        $transaction->update($transactionData);

        $this->logActivityOn($transaction, 'Transaction Voided', $transactionData, 'voided');

        return $transaction;
    }

    public function truncateTransactions(): void
    {
        $this->transaction->truncate();
        DB::table('activity_log')->truncate();
        DB::table('slips')->truncate();
    }
}
