<?php

namespace App\Http\Controllers;

use App\Events\TagNotificationCount;
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

    public function index(Request $request) {
        $transactions = $this->transactionService->getAllTransactions($request);

        // Handle both Paginator and Collection
        $collection = $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $transactions->getCollection()
            : $transactions;

        $collection->transform(function ($transaction) {
            return new TransactionResource($transaction);
        });

        return $transactions->isNotEmpty() && $collection->isNotEmpty()
            ? $this->responseSuccess('Transactions fetched successfully', $transactions)
            : $this->responseNotFound('No transactions found.');
    }

    public function store(TransactionRequest $request)
    {
        //Gate authorization
        // $this->authorize('create-transaction');

        $data = $request->validated();
        $transaction = $this->transactionService->createTransaction($data);

        event(new TagNotificationCount());

        return $this->responseCreated('Transaction created successfully', new TransactionResource($transaction));
    }

    public function show($id)
    {
        $transaction = $this->transactionService->getTransactionById($id);
        //Gate authorization
        $this->authorize('transaction', $transaction);

        if (!$transaction) {
            return $this->responseError('Transaction not found', 404);
        }

        return $this->responseSuccess('Transaction fetched successfully', new TransactionResource($transaction));
    }

    public function update(TransactionRequest $request, $id)
    {
        //Gate authorization
        // $this->authorize('transaction');

        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return $this->responseError('Transaction not found', 404);
        }

        $data = $request->validated();
        $updatedTransaction = $this->transactionService->updateTransaction($transaction, $data);

        event(new TagNotificationCount());

        return $this->responseSuccess('Transaction updated successfully', $updatedTransaction);
    }

    public function void(Request $request, $id)
    {
        //Gate authorization
        // $this->authorize('transaction');

        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return $this->responseError('Transaction not found', 404);
        }
        $this->transactionService->voidTransaction($transaction, $request);

        return $this->responseSuccess('Transaction voided successfully');
    }

    public function statusCount() {
        return response()->json([
            $this->transactionService->statusCount()
        ]);
    }

    public function truncate()
    {
        $this->transactionService->truncateTransactions();
        return $this->responseSuccess('All transactions have been deleted successfully.');
    }
}
