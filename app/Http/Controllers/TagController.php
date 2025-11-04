<?php

namespace App\Http\Controllers;

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

        $this->authorize('transaction');
        
        $filters = $request->only(['status']); // Get filters from request
        $transactions = $this->tagService->getTransactions($filters);
        
        $transactions->getCollection()->transform(function ($transaction) {
            return new TransactionResource($transaction);
        });

        return $this->responseSuccess('Transactions fetched successfully', $transactions);
    }

    public function action(Request $request) {
        $this->authorize('tag-transaction');
        return $transaction = $this->tagService->action($request);
        return $this->responseSuccess('Transaction updated successfully', $transaction);
    }
}
