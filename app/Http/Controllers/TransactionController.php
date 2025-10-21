<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
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

        return $this->responseCreated('Transaction created successfully', new TransactionResource($transaction));
    }

    public function show($id)
    {
        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return $this->responseError('Transaction not found', 404);
        }

        return $this->responseSuccess('Transaction fetched successfully', new TransactionResource($transaction));
    }

    public function update(TransactionRequest $request, $id)
    {
        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return $this->responseError('Transaction not found', 404);
        }

        $data = $request->validated();
        $updatedTransaction = $this->transactionService->updateTransaction($transaction, $data);

        return $this->responseSuccess('Transaction updated successfully', $updatedTransaction);
    }

    public function truncate()
    {
        $this->transactionService->truncateTransactions();
        return $this->responseSuccess('All transactions have been deleted successfully.');
    }
}
