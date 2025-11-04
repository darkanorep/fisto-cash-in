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

        // Apply status scope first (this handles the basic status filtering)
        if (isset($filters['status'])) {
            $query->status($filters['status']); // This calls your scope
        }

        // Then apply additional conditions based on specific status values
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                case 'receive':
                    $query->where([
                        'is_tagged' => false,
                        'is_cleared' => false,
                    ]);
                    break;
                case 'tag':
                    $query->where('is_tagged', true);
                    break;
                case 'clear':
                    $query->where('is_cleared', true);
                    break;
                // Add more status-specific filters as needed
            }
        }

        return $query->dynamicPaginate();
    }

    public function action($request) {
        
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');
        $depositDate = $request->input('deposit_date');
        $bankDeposit = $request->input('bank_deposit');
        $depositRemarks = $request->input('deposit_remarks');
        $series = $request->input('series');
        $transaction = $this->transaction->findOrFail($transactionId);

        switch($status) {

            case 'receive':
                $transaction->status = $status;
                break;

            case 'tag':
                $transaction->is_tagged = true;
                $transaction->status = $status;
                $transaction->deposit_date = $depositDate ?? null;
                $transaction->bank_deposit = $bankDeposit ?? null;
                $transaction->deposit_remarks = $depositRemarks ?? null;
                if (!$transaction->tag_number) {
                    $transaction->tag_number = $this->generateTagNumber($series);
                }
                break;
        }

        $transaction->save();
        $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
            'status' => $status,
            'deposit_date' => $depositDate,
            'bank_deposit' => $bankDeposit,
            'deposit_remarks' => $depositRemarks,
            'tag_number' => $transaction->tag_number,
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