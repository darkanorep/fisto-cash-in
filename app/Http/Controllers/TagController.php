<?php

namespace App\Http\Controllers;

use App\Events\TagNotificationCount;
use App\Http\Resources\TransactionResource;
use App\Services\TagService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    use ApiResponse;
    protected $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    public function index(Request $request) {
        // $this->authorize('transaction');

        $transactions = $this->tagService->getTransactions($request);

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
        $transaction = $this->tagService->action($request);
        event(new TagNotificationCount());
        return $this->responseSuccess('Transaction updated successfully', $transaction);
    }

    public function export(Request $request) {
        return $this->tagService->export($request);
    }

    public function statusCount() {
        return response()->json([
            $this->tagService->statusCount()
        ]);
    }
}
