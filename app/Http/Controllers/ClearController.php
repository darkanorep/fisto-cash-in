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
        $filters = $request->only(['status']);

        $transactions = $this->clearService->getTransactions($filters);

        $transactions->getCollection()->transform(function ($transaction) {
            return new TransactionResource($transaction);
        });

        return $this->responseSuccess('Transactions fetched successfully', $transactions);
    }

    public function action(Request $request) {
        // $this->authorize('clear-transaction');
        $transaction = $this->clearService->action($request);
        return $this->responseSuccess('Transaction updated successfully', $transaction);
    }
}
