<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Services\FileService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    use ApiResponse;
    protected $fileService;
    public function __construct(FileService $fileService) {
        $this->fileService = $fileService;
    }

    public function index(Request $request) {
        // $this->authorize('transaction');

        $transactions = $this->fileService->getTransactions($request);

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
        $transaction = $this->fileService->action($request);
        return $this->responseSuccess('Transaction updated successfully', $transaction);
    }

    public function statusCount() {
        return response()->json([
            $this->fileService->statusCount()
        ]);
    }
}
