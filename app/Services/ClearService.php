<?php

namespace App\Services;

use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;

class ClearService
{
    use ActivityLogTrait;
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransactions($request) {
        $query = $this->transaction->with(['bank']);

        $filters = $request instanceof Request ? $request->all() : $request;

        // Then apply additional conditions based on specific status values
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                    $query->where([
                        'is_tagged' => true,
                        'is_cleared' => false,
                        'status' => 'deposit',
                    ]);
                    break;
                case 'receive':
                    $query->where([
                        'is_tagged' => true,
                        'is_cleared' => false,
                        'status' => 'receive',
                    ]);
                    break;
                case 'clear':
                    $query->where([
                        'is_cleared' => true,
                        'status' => 'clear',
                    ]);
                    break;
                case 'return':
                    $query->where([
                        'is_tagged' => true,
                        'status' => 'return',
                    ]);
                    break;
            }
        }

        if (isset($filters['deposit_date_from']) && isset($filters['deposit_date_to'])) {
            $query->depositDate([
                'deposit_date_from' => $filters['deposit_date_from'],
                'deposit_date_to' => $filters['deposit_date_to'],
            ]);
        }

        return $query->useFilters()->dynamicPaginate();
    }

    public function action($request) {

        $transactionIds = $request->input('transaction_id'); // Now accepts array
        $dateCleared = $request->input('date_cleared');
        $status = $request->input('status');
        $reason = $request->input('reason');

        // Ensure it's always an array
        $transactionIds = is_array($transactionIds) ? $transactionIds : [$transactionIds];

        $transactions = [];

        foreach ($transactionIds as $transactionId) {
            $transaction = $this->transaction->find($transactionId);

            if (!$transaction) {
                continue; // Skip if transaction not found
            }

            switch($status) {
                case 'receive':
                    $transaction->status = $status;
                    break;

                case 'clear':
                    $transaction->is_cleared = true;
                    $transaction->status = $status;
                    $transaction->date_cleared = $dateCleared;
                    break;

                case 'return':
                    $transaction->status = $status;
                    $transaction->reason = $reason;
                    $transaction->date_cleared = null;
                    $transaction->is_cleared = false;
                    break;

                case 'void':
                    $transaction->status = $status;
                    $transaction->reason = $reason;
                    break;
            }
            $transaction->save();

            $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
                'status' => $status,
                'date_cleared' => $transaction->date_cleared,
                'reason' => $reason,
            ], 'clear:'.$status);

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    public function statusCount(): array {
        return [
            'pending' => $this->transaction->where('status', 'deposit')->count(),
        ];
    }
}
