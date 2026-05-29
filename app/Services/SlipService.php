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

    public function remainingSlipAmount($requests)
    {
        $initialQuery = $this->slip->newQuery()
           ->whereHas('transactions', function ($query) use ($requests) {
               $query->where('customer_name', $requests->input('customer_name'));
           })
           ->where('type', $requests->input('type'))->where('number', $requests->input('slip_number'))->pluck('transaction_id');
       $transactionSlips = $this->slip->newQuery()->whereIn('transaction_id', $initialQuery)->pluck('number');
       $transactionIDs = $this->slip->newQuery()
           ->whereHas('transactions', function ($query) use ($requests) {
               $query->where('customer_name', $requests->input('customer_name'));
           })
           ->whereIn('number', $transactionSlips)->pluck('transaction_id');
       $transactionsQuery = $this->transaction->newQuery()->whereIn('id', $transactionIDs)->where('status', '!=', 'void')->select('id', 'amount');
       $transactionSlips = $this->slip->newQuery()->with(['transactions'])->whereIn('transaction_id', $transactionsQuery->pluck('id'))->get()
        ->unique('number')->values();

        $totalSlipAmount = $transactionSlips->sum('amount');
        $totalTransactionAmount =  $transactionsQuery->sum('amount');
        $remainingAmount = $totalSlipAmount - $totalTransactionAmount;

        return [
            'total_slip_amount' => $totalSlipAmount,
            'total_amount_paid' => $totalTransactionAmount,
            'remaining_amount' => $remainingAmount,
            'slips' => SlipResource::collection($transactionSlips),
        ];
    }
}
