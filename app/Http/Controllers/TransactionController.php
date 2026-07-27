<?php

namespace App\Http\Controllers;

use App\Events\TagNotificationCount;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Services\TransactionSyncService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;
    protected $transactionService;
    protected $transactionSyncService;

    public function __construct(
        TransactionService $transactionService,
        TransactionSyncService $transactionSyncService
    ) {
        $this->transactionService = $transactionService;
        $this->transactionSyncService = $transactionSyncService;
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

        // user_id is resolved ONLY at creation time, from the Aranca sync
        // settings mapping (cash/non-cash -> settings.value1). It is
        // intentionally never re-resolved or overwritten on update, even
        // if mode_of_payment changes later.
        if ($this->transactionSyncService->isSyncTransaction($request->input('sync_id'), $request->input('sync_payment_record_id'))) {
            $resolvedUserId = $this->transactionSyncService->resolveSyncUserId($data['mode_of_payment'] ?? null);

            if ($resolvedUserId === null) {
                // Fail fast with a clear 422 instead of letting this fall
                // through to a DB NOT NULL violation on transactions.user_id.
                return $this->responseNotFound(
                    'Unable to resolve user_id for this synced transaction. Check the mode_of_payment value and the external_cash_transaction / external_non_cash_transaction settings.',
                    422
                );
            }

            $data['user_id'] = $resolvedUserId;
        }

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
            return $this->responseNotFound('Transaction not found', 404);
        }

        return $this->responseSuccess('Transaction fetched successfully', new TransactionResource($transaction));
    }

    public function update(TransactionRequest $request, $id)
    {
        //Gate authorization
        // $this->authorize('transaction');

        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return $this->responseNotFound('Transaction not found', 404);
        }

        $data = $request->validated();

        // Deliberately NOT resolving/overwriting user_id here — it's set
        // once at creation time only (see store()).

        $updatedTransaction = $this->transactionService->updateTransaction($transaction, $data);

        event(new TagNotificationCount());

        return $this->responseSuccess(
            'Transaction updated successfully',
            new TransactionResource($updatedTransaction->fresh())
        );
    }

    public function void(Request $request, $id)
    {
        //Gate authorization
        // $this->authorize('transaction');

        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return $this->responseNotFound('Transaction not found', 404);
        }
        $this->transactionService->voidTransaction($transaction, $request);

        return $this->responseSuccess('Transaction voided successfully');
    }

    public function statusCount() {
        return response()->json([
            $this->transactionService->statusCount()
        ]);
    }

    public function export(Request $request) {
        return $this->transactionService->export($request);
    }

    public function truncate()
    {
        $this->transactionService->truncateTransactions();
        return $this->responseSuccess('All transactions have been deleted successfully.');
    }
}
