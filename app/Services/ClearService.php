<?php

namespace App\Services;

use App\Models\Transaction;
use App\Traits\ActivityLogTrait;

class ClearService
{
    use ActivityLogTrait;
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransactions($filters = []) {
        $query = $this->transaction->query();

        // Then apply additional conditions based on specific status values
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                    $query->where([
                        'is_tagged' => true,
                        'is_cleared' => false,
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
            }
        }

        return $query->dynamicPaginate();
    }

    public function action($request) {
        
        $transactionId = $request->input('transaction_id');
        $dateCleared = $request->input('date_cleared');
        $status = $request->input('status');
        $reason = $request->input('reason');
        $transaction = $this->transaction->findOrFail($transactionId);

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
        ], $status);

        return $transaction;
    }
}