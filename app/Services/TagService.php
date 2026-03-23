<?php

namespace App\Services;

use App\Models\Transaction;
use App\Traits\ActivityLogTrait;

class TagService
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
                case 'receive':
                    $query->status($filters['status'])
                        ->where([
                            'is_tagged' => false,
                            'is_cleared' => false,
                        ]);
                    break;
                case 'tag':
                    $query->status($filters['status'])
                        ->where('is_tagged', true);
                    break;
                case 'clear':
                    $query->status($filters['status'])
                        ->where('is_cleared', true);
                    break;
                
                case 'return-tag':
                    // Don't call scope here - 'return-tag' is not a real status
                    $query->where([
                        'is_tagged' => true,
                        'status' => 'return',
                    ])
                    ->whereNotNull('reason')
                    ->whereNull('date_cleared');
                    break;
                    
                default:
                    $query->status($filters['status']);
                    break;
            }
        }

        return $query->with(['bank'])->useFilters()->dynamicPaginate();
    }

    public function action($request) {
        
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');
        $depositDate = $request->input('deposit_date');
        $bankDeposit = $request->input('bank_deposit');
        $depositRemarks = $request->input('deposit_remarks');
        $series = $request->input('series');
        $reason = $request->input('reason');
        $transaction = $this->transaction->findOrFail($transactionId);

        switch($status) {

            case 'receive':
                $transaction->reason = null;
                $transaction->deposit_date = null;
                $transaction->bank_deposit = null;
                $transaction->deposit_remarks = null;
                $transaction->is_tagged = false;
                break;

            case 'tag':
                $transaction->is_tagged = true;
                $transaction->deposit_date = $depositDate ?? null;
                $transaction->bank_deposit = $bankDeposit ?? null;
                $transaction->deposit_remarks = $depositRemarks ?? null;
                if (!$transaction->tag_number) {
                    $transaction->tag_number = $this->generateTagNumber($series);
                }
                break;

            case 'deposit':
                $transaction->deposit_date = $depositDate ?? null;
                $transaction->bank_deposit = $bankDeposit ?? null;
                $transaction->deposit_remarks = $depositRemarks ?? null;
                break;

            case 'return':
                $transaction->is_tagged = false;
                $transaction->reason = $reason;
                $transaction->deposit_date = $transaction->deposit_date ?? null;
                $transaction->bank_deposit = $transaction->bank_deposit ?? null;
                $transaction->deposit_remarks = $transaction->deposit_remarks ?? null;
                $transaction->tag_number = $transaction->tag_number ?? null;
                break;

            case 'void':
                $transaction->reason = $reason;
                break;
        }

        $transaction->status = $status;
        $transaction->save();
        $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
            'status' => $status,
            'deposit_date' => $depositDate,
            'bank_deposit' => $bankDeposit,
            'deposit_remarks' => $depositRemarks,
            'tag_number' => $transaction->tag_number,
            'reason' => $reason,
        ], $status);

        return $transaction;

    }

    public function generateTagNumber($series) {

        $lastTag = Transaction::whereNotNull('tag_number')
            ->where('tag_number', 'like', $series . '%')
            ->select('tag_number')
            ->get();

        $maxTag = $lastTag->map(function ($item) use ($series) {
            return (int) str_replace($series, '', $item->tag_number);
        })->max();

        $nextTagNumber = $maxTag ? $maxTag + 1 : 1;

        return $series . str_pad($nextTagNumber, 4, '0', STR_PAD_LEFT);
    }
}