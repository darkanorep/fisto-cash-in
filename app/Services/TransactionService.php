<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{

    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getAllTransactions()
    {
        return $this->transaction->dynamicPaginate();
    }

    public function createTransaction($data)
    {
        $transactionData = [
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'customer_id' => $data['customer']['id'],
            'customer_name' => $data['customer']['name'],
            'mode_of_payment' => $data['mode_of_payment'],
            'bank_id' => $data['bank']['id'] ?? null,
            'bank_name' => $data['bank']['name'] ?? null,
            'check_no' => $data['check']['no'] ?? null,
            'check_date' => $data['check']['date'] ?? null,
            'amount' => $data['amount'],
            'remaining_balance' => $data['remaining_balance'] ?? 0,
            'charge_id' => $data['charge']['id'],
            'charge_name' => $data['charge']['name'],
            'remarks' => $data['remarks'] ?? null,
        ];

        activity()
        ->on(auth()->user())
        ->withProperties($transactionData)
        ->event('created')
        ->log('Transaction Created');

        return $this->transaction->create($transactionData);
    }

    public function getTransactionById($id)
    {
        return $this->transaction->find($id);
    }

    public function updateTransaction($transaction, $data) 
    {
        $data = [
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'customer_id' => $data['customer']['id'],
            'customer_name' => $data['customer']['name'],
            'mode_of_payment' => $data['mode_of_payment'],
            'bank_id' => $data['bank']['id'] ?? null,
            'bank_name' => $data['bank']['name'] ?? null,
            'check_no' => $data['check']['no'] ?? null,
            'check_date' => $data['check']['date'] ?? null,
            'amount' => $data['amount'],
            'remaining_balance' => $data['remaining_balance'] ?? 0,
            'charge_id' => $data['charge']['id'],
            'charge_name' => $data['charge']['name'],
            'remarks' => $data['remarks'] ?? null,
        ];

        $transaction->update($data);
        return $transaction;
    }

    public function truncateTransactions(): void
    {
        $this->transaction->truncate();
        DB::table('activity_log')->truncate();
    }
}