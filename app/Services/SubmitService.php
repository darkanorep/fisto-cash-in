<?php

namespace App\Services;

use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SubmitService
{
    use ActivityLogTrait;

    protected $transaction;

    public function __construct(Transaction $transaction, private readonly VoucherEntryService $voucherEntryService) {
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
                        $query->where([
                            'status' =>'pending',
                            'is_tagged' => false,
                            'is_cleared' => false
                        ])
                            ->whereNotIn('mode_of_payment', Transaction::modeOfPaymentOptions);
                    });
                    break;
                case 'receive':
                    $query->where(function ($query) {
                        $query->where([
                            'status' => 'receive',
                            'is_tagged' => false,
                            'is_cleared' => false,
                        ])->whereNull('tag_number')->whereNotIn('mode_of_payment', Transaction::modeOfPaymentOptions);
                    });
                    break;
                default:
                    $query->status($filters['status']);
                    break;
            }
        }

        if (isset($filters['mode_of_payment'])) {
            $query->modeOfPayment($filters['mode_of_payment']);
        }

        $query->orderBy('updated_at', 'desc');

        return $query->with([
            'bank',
            'customer',
            'slips',
            'voucherAccountEntries'
        ])->useFilters()->dynamicPaginate();
    }

    public function action($request) {
        $transactionIds = $request->input('transaction_id');
        $status = $request->input('status');
        $reason = $request->input('reason');

        // Ensure it's always an array
        $transactionIds = is_array($transactionIds) ? $transactionIds : [$transactionIds];

        $transactions = [];

        foreach ($transactionIds as $transactionId) {
            $accountTitles = $request->input('account_titles');
            $transaction = $this->transaction->findOrFail($transactionId);

            if (!$transaction) {
                continue; // Skip if transaction not found
            }

            switch ($status) {
                case 'submit':
                    $transaction->is_submitted = true;
                    break;

//                case 'return':
//                    $transaction->reason = $reason;
//                    $transaction->date_filed = null;
//                    break;
            }

            $transaction->status = $status;
            $transaction->save();

            $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
                'status' => $status,
            ], 'submit:'.$status);

            if (!empty($accountTitles)) {
                $this->voucherEntryService->processVoucherEntries($transaction, $accountTitles, $status);
            }

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    public function statusCount() : array {
        return [
            'pending' => $this->transaction->newQuery()
                ->where(function ($query) {
                    $query->where([
                        'status' =>'pending',
                        'is_tagged' => false,
                        'is_cleared' => false
                    ])
                        ->whereNotIn('mode_of_payment', Transaction::modeOfPaymentOptions);
                })->count(),
        ];
    }
}
