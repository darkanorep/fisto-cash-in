<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Services\ClearService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class ClearController extends Controller
{
    use ApiResponse;
    protected $clearService;

    public function __construct(ClearService $clearService)
    {
        $this->clearService = $clearService;
    }

    public function index(Request $request) {
        $transactions = $this->clearService->getTransactions($request);

        // Handle both Paginator and Collection
        $collection = $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $transactions->getCollection()
            : $transactions;

        $collection->transform(function ($transaction) {
            return new TransactionResource($transaction);
        });

        return $transactions->isNotEmpty() && $collection->isNotEmpty()
            ? $this->responseSuccess('Transactions fetched successfully', $collection)
            : $this->responseNotFound('No transactions found.');
    }

    public function action(Request $request) {
        // $this->authorize('clear-transaction');
        $transaction = $this->clearService->action($request);
        return $this->responseSuccess('Transaction updated successfully', $transaction);
    }

    public function statusCount() {
        return response()->json([
            $this->clearService->statusCount()
        ]);
    }
}
