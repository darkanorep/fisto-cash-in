<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Services\SubmitService;
use App\Traits\PastTenseConverterTrait;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class SubmitController extends Controller
{
    use ApiResponse, PastTenseConverterTrait;

    public function __construct(private readonly SubmitService $submitService) { }

    public function index(Request $request) {
        $transactions = $this->submitService->getTransactions($request);

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

    public function action(Request $request) {
        // $this->authorize('tag-transaction');
        $transaction = $this->submitService->action($request);
        $status = $request->input('status');

        $pastStatus = $this->convertToPastTense($status);

        return $this->responseSuccess("Transaction {$pastStatus} successfully", $transaction);
    }

    public function statusCount() {
        return response()->json([
            $this->submitService->statusCount()
        ]);
    }

}
