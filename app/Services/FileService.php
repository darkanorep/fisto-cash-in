<?php

namespace App\Services;

use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FileService
{
    use ActivityLogTrait;
    protected $transaction;

    private mixed $arcanaApiKey;
    private mixed $arcanaUrl;

    public function __construct(Transaction $transaction) {
        $this->transaction = $transaction;
        $this->arcanaApiKey = config('app.arcana_api_key');
        $this->arcanaUrl = config('app.arcana_url');
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

        if (isset($filters['clear_date_from']) && isset($filters['clear_date_to'])) {
            $query->clearDate([
                'clear_date_from' => $filters['clear_date_from'],
                'clear_date_to' => $filters['clear_date_to'],
            ]);
        }

        $query->orderBy('updated_at', 'desc');

        return $query->with(['bank'])->useFilters()->dynamicPaginate();
    }

    public function action($request) {
        $transactionIds = $request->input('transaction_id');
        $status = $request->input('status');
        $reason = $request->input('reason');
        $dateFiled = Carbon::now();

        // Ensure it's always an array
        $transactionIds = is_array($transactionIds) ? $transactionIds : [$transactionIds];

        $transactions = [];

        foreach ($transactionIds as $transactionId) {
            $transaction = $this->transaction->findOrFail($transactionId);

            if (!$transaction) {
                continue; // Skip if transaction not found
            }

            switch ($status) {
                case 'file':
                    $transaction->date_filed = $dateFiled;

                    if ($transaction->sync_payment_record_id) {
                        Http::withHeaders(['api-key' => $this->arcanaApiKey])->post(
                            $this->arcanaUrl . 'file', [
                            'paymentRecordId' => $transaction->sync_payment_record_id,
                            'paymentMethod' => $transaction->mode_of_payment,
                            'paymentAmount' => $transaction->amount
                        ]);
                    }
                    break;

                case 'return':
                    $transaction->reason = $reason;
                    $transaction->date_filed = null;
                    break;
            }

            $transaction->status = $status;
            $transaction->save();

            $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
                'status' => $status,
                'date_filed' => $dateFiled,
            ], 'file:'.$status);

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    public function statusCount() : array {
        return [
            'pending' => $this->transaction->newQuery()
                ->where(function ($query) {
                    $query->whereIn('mode_of_payment', Transaction::modeOfPaymentOptions)
                        ->where('status', 'clear');
                })
                ->orWhere(function ($query) {
                    $query->whereNotIn('mode_of_payment', Transaction::modeOfPaymentOptions)
                        ->where('status', 'pending');
                })
                ->count(),
        ];
    }
}
