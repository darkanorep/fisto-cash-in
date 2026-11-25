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

//    public function remainingSlipAmount($requests)
//    {
//        $initialQuery = $this->slip->newQuery()
//           ->whereHas('transactions', function ($query) use ($requests) {
//               $query->where('customer_name', $requests->input('customer_name'));
//           })
//           ->where('type', $requests->input('type'))->where('number', $requests->input('slip_number'))->pluck('transaction_id');
//       $transactionSlips = $this->slip->newQuery()->whereIn('transaction_id', $initialQuery)->pluck('number');
//       $transactionIDs = $this->slip->newQuery()
//           ->whereHas('transactions', function ($query) use ($requests) {
//               $query->where('customer_name', $requests->input('customer_name'));
//           })
//           ->whereIn('number', $transactionSlips)->pluck('transaction_id');
//       $transactionsQuery = $this->transaction->newQuery()->whereIn('id', $transactionIDs)->where('status', '!=', 'void')->select('id', 'amount');
//       $transactionSlips = $this->slip->newQuery()->with(['transactions'])->whereIn('transaction_id', $transactionsQuery->pluck('id'))->get()
//        ->unique('number')->values();
//
//        $totalSlipAmount = $transactionSlips->sum('amount');
//        $totalTransactionAmount =  $transactionsQuery->sum('amount');
//        $remainingAmount = $totalSlipAmount - $totalTransactionAmount;
//
//        return [
//            'total_slip_amount' => $totalSlipAmount,
//            'total_amount_paid' => $totalTransactionAmount,
//            'remaining_amount' => $remainingAmount,
//            'slips' => SlipResource::collection($transactionSlips),
//        ];
//    }

    public function remainingSlipAmount($requests)
    {
        $customerName = $requests->input('customer_name');
        $type         = $requests->input('type');
        $slipNumber   = $requests->input('slip_number');

        // 1. Find the transaction_id for the specific slip
        $transactionId = $this->slip->newQuery()
            ->whereHas('transactions', fn($q) => $q->where('customer_name', $customerName))
            ->where('type', $type)
            ->where('number', $slipNumber)
            ->value('transaction_id');

        // 2. Get all slip numbers tied to that transaction
        $slipNumbers = $this->slip->newQuery()
            ->where('transaction_id', $transactionId)
            ->pluck('number');

        // 3. Get all transaction IDs that share those slip numbers (same customer)
        $transactionIDs = $this->slip->newQuery()
            ->whereHas('transactions', fn($q) => $q->where('customer_name', $customerName))
            ->whereIn('number', $slipNumbers)
            ->pluck('transaction_id')
            ->unique();

        // 4. Get non-void transactions
        $transactions = $this->transaction->newQuery()
            ->whereIn('id', $transactionIDs)
            ->where('status', '!=', 'void')
            ->where(function ($query) {
                $query->where('status', '!=', 'return')
                    ->orWhere('is_tagged', true);
            })
            ->select('id', 'amount')
            ->get();

        $validTransactionIDs = $transactions->pluck('id');

        $transactionSlips = $this->slip->newQuery()
            ->whereIn('transaction_id', $validTransactionIDs)
            ->get()
            ->unique('number')
            ->sortBy('number')
            ->values();

        $totalSlipAmount = $transactionSlips->sum('amount');
        $totalTransactionAmount = $transactions->sum('amount');

        $remainingPayment = $totalTransactionAmount;

        $slips = $transactionSlips->map(function ($slip) use (&$remainingPayment) {
            $actualPaid = min($slip->amount, max(0, $remainingPayment));
            $remainingPayment -= $actualPaid;

            $slip->actual_amount_paid = $actualPaid;
            $slip->remaining_amount = $slip->amount - $actualPaid;

            return $slip;
        });

        return [
            'total_slip_amount' => $totalSlipAmount,
            'total_amount_paid' => $totalTransactionAmount,
            'remaining_amount' => $slips->sum('remaining_amount'),
            'slips' => SlipResource::collection($slips),
        ];
    }
}
