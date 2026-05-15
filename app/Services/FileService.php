<?php

namespace App\Services;

use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;

class FileService
{
    use ActivityLogTrait;
    protected $transaction;
    public function __construct(Transaction $transaction) {
        $this->transaction = $transaction;
    }

    public function getTransactions($request) {
        $query = $this->transaction->query();

        $filters = $request instanceof Request ? $request->all() : $request;

        // Then apply additional conditions based on specific status values
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                    $query->where(function ($query) {
                        $query->where('status', 'clear')
                            ->orWhere(function ($query) {
                                $query->where('status', 'pending')
                                    ->whereNotIn('mode_of_payment', ['online', 'cash', 'cheque']);
                            });
                    });
                    break;
                case 'receive':
                    $query->where(function ($query) {
                        $query->where([
                            'is_cleared' => true,
                            'status' => 'receive'
                        ])->orWhere(function ($query) {
                            $query->where([
                                'status' => 'receive',
                                'is_tagged' => false,
                                'is_cleared' => false,
                            ])->whereNull('tag_number')->whereNotIn('mode_of_payment', ['online', 'cash', 'cheque']);
                        });
                    });
                    break;
                default:
                    $query->status($filters['status']);;
                    break;
            }
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->date([
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
            ]);
        }

        $query->orderBy('updated_at', 'desc');

        return $query->with(['bank'])->useFilters()->dynamicPaginate();
    }

    public function action($request) {
        $transactionIds = $request->input('transaction_id');
        $status = $request->input('status');

        foreach ($transactionIds as $transactionId) {
            $transaction = $this->transaction->findOrFail($transactionId);
            $transaction->status = $status;
            $transaction->save();

            $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
                'status' => $status,
            ], 'file:'.$status);

        }
    }

    public function statusCount() : array {
        $query = $this->transaction->newQuery();

        return [
            'pending' => $query->whereNotIn('mode_of_payment', ['online', 'cash', 'cheque'])->where('status', 'pending')->count()
        ];
    }
}
