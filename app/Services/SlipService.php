<?php

namespace App\Services;

use App\Http\Resources\SlipResource;
use App\Models\Slip;
use App\Models\Transaction;

class SlipService
{
    protected $slip, $transaction;
    public function __construct(Slip $slip, Transaction $transaction)
    {
        $this->slip = $slip;
        $this->transaction = $transaction;
    }

    public function remainingSlipAmount($type, $slipNumber)
    {
        $transactionIDs = $this->slip->where('type', $type)->where('number', $slipNumber)->pluck('transaction_id');
        $transactionsQuery = $this->transaction->whereIn('id', $transactionIDs)->where('status', '!=', 'void')->select('id', 'amount');
        $transactionSlips = $this->slip->whereIn('transaction_id', $transactionsQuery->pluck('id'))->get()
        ->unique('number')->values();
        
        $totalSlipAmount = $transactionSlips->sum('amount');
        $totalTransactionAmount =  $transactionsQuery->sum('amount');
        $remainingAmount = $totalSlipAmount - $totalTransactionAmount;

        return [
            'remaining_amount' => $remainingAmount,
            'slips' => SlipResource::collection($transactionSlips),
        ];
    }
}