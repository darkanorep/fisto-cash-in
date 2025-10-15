<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Services\TransactionService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function store(TransactionRequest $request)
    {
        $data = $request->validated();
        $transaction = $this->transactionService->createTransaction($data);

        return $this->responseSuccess('Transaction created successfully', $transaction, 201);
    }

    public function truncate()
    {
        $this->transactionService->truncateTransactions();
        return $this->responseSuccess('All transactions have been deleted successfully.');
    }
}
