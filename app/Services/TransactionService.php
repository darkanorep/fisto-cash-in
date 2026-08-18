<?php

namespace App\Services;

use App\Exports\ActivityExport;
use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use Illuminate\Http\Client\ConnectionException;

class TransactionService
{
    use ActivityLogTrait;

    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|mixed
     */
    private mixed $arcanaApiKey;
    private mixed $arcanaUrl;

    public function __construct(protected Transaction $transaction)
    {
        $this->arcanaApiKey = config('app.arcana_api_key');
        $this->arcanaUrl = config('app.arcana_url');
    }

    public function getAllTransactions(Request $request)
    {
        $query = $this->transaction->query()
            ->with(['bank', 'customer', 'slips', 'voucherAccountEntries'])
            ->where('user_id', auth()->id());

        $status      = $request->input('status');
        $paymentType = $request->input('payment_type');

        if ($status) {
            match ($status) {
                'return-request' => $query->where('status', 'return')
                    ->where('is_tagged', false)
                    ->whereNotNull('reason'),
                default => $query->status($status),
            };
        } else {
            // No status filter requested -> hide voided transactions by default
            $query->whereNotIn('status', ['void']);
        }

        $query->paymentType($paymentType);

        if (isset($request['date_from']) && isset($request['date_to'])) {
            $query->date([
                'date_from' => $request['date_from'],
                'date_to'   => $request['date_to'],
            ]);
        }

        $paginated = $query->orderBy('updated_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        // Distribute payment per transaction's slips — operate on the paginated items
        $paginated->getCollection()->each(fn ($transaction) => $this->applySlipDistribution($transaction));

        return $paginated;
    }

    /**
     * Allocate a transaction's paid amount across its slips (FIFO by slip
     * number) and flag whether the transaction is fully paid. Extracted
     * from the inline closure in getAllTransactions() for readability —
     * behavior is unchanged.
     */
    private function applySlipDistribution(Transaction $transaction): void
    {
        $remainingPayment = $transaction->amount;

        $slips = $transaction->slips
            ->unique('number')
            ->sortBy('number')
            ->values();

        $slips->each(function ($slip) use (&$remainingPayment) {
            $actualPaid               = min($slip->amount, max(0, $remainingPayment));
            $slip->actual_amount_paid = $actualPaid;
            $slip->remaining_amount   = $slip->amount - $actualPaid;
            $remainingPayment        -= $actualPaid;
        });

        $transaction->setRelation('slips', $slips);

        // Full paid = every slip's remaining_amount is fully covered
        $transaction->is_fully_paid = (int) ($slips->sum('remaining_amount') <= 0);
    }

    private function buildTransactionData(array $data, array $additionalFields = []): array
    {
        $baseData = [
            'user_id'                 => $data['user_id'] ?? auth()->id(),
            'type'                    => $data['type'] ?? null,
            'category'                => $data['category'] ?? null,
            'sync_id'                 => $data['sync_id'] ?? null,
            'sync_payment_record_id'  => $data['sync_payment_record_id'] ?? null,
            'sync_transaction_number' => $data['sync_transaction_number'] ?? null,
            'distribution_type'       => $data['distribution_type'] ?? null,
            'reference_no'            => $data['reference_no'] ?? null,
            'transaction_date'        => $data['transaction_date'] ?? null,
            'payment_date'            => $data['payment_date'] ?? null,
            'customer_id'             => $data['customer']['id'] ?? null,
            'customer_code'           => $data['customer']['code'] ?? $data['customer_code'] ?? null,
            'customer_name'           => $data['customer']['name'] ?? $data['customer_name'] ?? null,
            'mode_of_payment'         => strtolower($data['mode_of_payment']) ?? null,
            'payment_type'            => $data['payment_type'] ?? null,
            'bank_id'                 => $data['bank']['id'] ?? null,
            'bank_code'               => $data['bank']['code'] ?? $data['bank_code'] ?? null,
            'bank_name'               => $data['bank']['name'] ?? $data['bank'] ?? null,
            'check_no'                => $data['cheque']['no'] ?? $data['check']['no'] ?? $data['cheque_no'] ?? null,
            'check_date'              => $data['cheque']['date'] ?? $data['check']['date'] ?? $data['cheque_date'] ?? null,
            'amount'                  => $data['amount'] ?? null,
            'remaining_balance'       => $data['remaining_balance'] ?? 0,
            'charge_id'               => $data['charge']['id'] ?? null,
            'charge_name'             => $data['charge']['name'] ?? $data['charge_name'] ?? null,
            'charge_code'             => $data['charge']['code'] ?? $data['charge_code'] ?? null,
            'company_code'            => $data['company']['code'] ?? $data['company_code'] ?? null,
            'company_name'            => $data['company']['name'] ?? $data['company_name'] ?? null,
            'business_unit_code'      => $data['business_unit']['code'] ?? $data['business_unit_code'] ?? null,
            'business_unit_name'      => $data['business_unit']['name'] ?? $data['business_unit_name'] ?? null,
            'department_code'         => $data['department']['code'] ?? $data['department_code'] ?? null,
            'department_name'         => $data['department']['name'] ?? $data['department_name'] ?? null,
            'unit_code'               => $data['unit']['code'] ?? $data['unit_code'] ?? null,
            'unit_name'               => $data['unit']['name'] ?? $data['unit_name'] ?? null,
            'sub_unit_code'           => $data['sub_unit']['code'] ?? $data['sub_unit_code'] ?? null,
            'sub_unit_name'           => $data['sub_unit']['name'] ?? $data['sub_unit_name'] ?? null,
            'location_code'           => $data['location']['code'] ?? $data['location_code'] ?? null,
            'location_name'           => $data['location']['name'] ?? $data['location_name'] ?? null,
            'remarks'                 => $data['remarks'] ?? null,
        ];

        return array_merge($baseData, $additionalFields);
    }

    /**
     * Creates slip rows for a transaction. Extracted from the duplicated
     * foreach blocks previously in createTransaction() and
     * updateTransaction() — behavior is unchanged.
     */
    private function createSlips(Transaction $transaction, array $slipsData): void
    {
        foreach ($slipsData as $slip) {
            $transaction->slips()->create([
                'type'               => $slip['type'],
                'number'             => $slip['number'],
                'amount'             => $slip['amount'],
                'actual_amount_paid' => $slip['actual_amount_paid'],
            ]);
        }
    }

    public function createTransaction(array $data): Transaction
    {
        $transactionData = $this->buildTransactionData($data);
        $transaction     = $this->transaction->create($transactionData);

        if (!empty($data['slip'])) {
            $this->createSlips($transaction, $data['slip']);

            $this->logActivityOn($transaction, 'Slips Added for Transaction', ['slips' => $data['slip']]);
        }

        $this->logActivityOn($transaction, 'Transaction Created', $transactionData);

        return $transaction;
    }

    /**
     * Scoped to the authenticated user — previously this had no user_id
     * filter, which let any authenticated user fetch any transaction by id
     * (IDOR). Fixed here.
     */
    public function getTransactionById(int|string $id): \Illuminate\Database\Eloquent\Builder|array|Collection|\Illuminate\Database\Eloquent\Model
    {
        return $this->transaction->query()
            ->with(['slips', 'bank', 'customer', 'voucherAccountEntries'])
            ->where('user_id', auth()->id())
            ->find($id);
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        $transactionData = $this->buildTransactionData($data, ['status' => 'pending']);
        $transaction->update($transactionData);

        if (!empty($data['slip'])) {
            $transaction->slips()->delete();
            $this->createSlips($transaction, $data['slip']);

            $this->logActivityOn($transaction, 'Slips Updated for Transaction', ['slips' => $data['slip']], 'updated');
        }

        $this->logActivityOn($transaction, 'Transaction Updated', $transactionData, 'updated');

        return $transaction;
    }

    public function voidTransaction(Transaction $transaction, array|Request $data): Transaction
    {
        // Controller passes the Request object directly (not ->validated()
        // or ->all()), so normalize here rather than assuming array.
        $data = $data instanceof Request ? $data->all() : $data;

        $transactionData = [
            'status' => 'void',
            'reason' => $data['reason'] ?? null,
        ];

        // Fixed: previously re-fetched $transaction from the DB just to read
        // a column already available on the model — removed the redundant
        // query. Also guarded against sync_transaction_number being null:
        // Eloquent compiles where('col', null) to "IS NULL", so the old code
        // would cascade-void every transaction with a null
        // sync_transaction_number, not just the sibling rows intended.
        if (filled($transaction->sync_id)) {
            $this->transaction->newQuery()
                ->where('sync_id', $transaction->sync_id)
                ->update($transactionData);
        }

        $transaction->update($transactionData);

        $this->logActivityOn($transaction, 'Transaction Voided', $transactionData, 'voided');

        // The Arcana push is a side effect, not the source of truth — the
        // transaction is voided locally regardless of whether the remote
        // call succeeds. We catch both transport-level failures (timeouts,
        // DNS, connection refused) and non-2xx responses so a flaky
        // gateway never turns a successful void into a 500, while still
        // surfacing the failure for reconciliation/alerting.
        try {
            $response = Http::withHeaders(['api-key' => $this->arcanaApiKey])
                ->withQueryParameters([
                    'paymentTransactionId' => $transaction->sync_id,
                ])
                ->post($this->arcanaUrl . 'void');

            if ($response->successful()) {
                Log::info('Arcana void call succeeded', [
                    'transaction_id' => $transaction->id,
                    'paymentTransactionId' => $transaction->sync_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                Log::warning('Arcana void call returned an error response', [
                    'transaction_id' => $transaction->id,
                    'paymentTransactionId' => $transaction->sync_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (ConnectionException $e) {
            Log::error('Arcana void call failed: connection error', [
                'transaction_id' => $transaction->id,
                'paymentTransactionId' => $transaction->sync_id,
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::error('Arcana void call failed unexpectedly', [
                'transaction_id' => $transaction->id,
                'paymentTransactionId' => $transaction->sync_id,
                'message' => $e->getMessage(),
            ]);
        }

        return $transaction;
    }

    public function export(Request $request): BinaryFileResponse
    {
        $dateFrom      = $request->input('date_from');
        $dateTo        = $request->input('date_to');
        $state         = $request->input('state');
        $status        = $request->input('status');
        $modeOfPayment = $request->input('mode_of_payment');
        $requestedUser = $request->input('user_id');

        // Fixed: previously trusted a caller-supplied user_id outright,
        // letting any user export another user's transaction history.
        // Only honor it if the authenticated user is authorized to view
        // other users' transactions; otherwise fall back to their own id.
        // Adjust the ability name ('viewAny') / gate below to whatever your
        // app's actual authorization convention is.
        $userId = ($requestedUser && auth()->user()?->can('viewAny', Transaction::class))
            ? $requestedUser
            : auth()->id();

        $stateLabel    = filled($state) ? strtoupper($state) : 'ALL';
        $statusLabel   = filled($status) ? strtoupper($status) : 'ALL';
        $dateFromLabel = filled($dateFrom) ? $dateFrom : 'START';
        $dateToLabel   = filled($dateTo) ? $dateTo : 'END';

        $filename = "T{$stateLabel}-{$statusLabel}_{$dateFromLabel}_to_{$dateToLabel}.xlsx";

        return Excel::download(
            new ActivityExport($dateFrom, $dateTo, $state, $status, $userId, $modeOfPayment),
            $filename
        );
    }

    public function truncateTransactions(): void
    {
        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'pgsql') {
                // PostgreSQL: Use CASCADE to truncate dependent tables
                DB::statement('TRUNCATE TABLE slips CASCADE');
                DB::statement('TRUNCATE TABLE activity_log CASCADE');
                DB::statement('TRUNCATE TABLE transactions CASCADE');
            } else {
                // MySQL: Disable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('slips')->truncate();
                DB::table('activity_log')->truncate();
                $this->transaction->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Ensure foreign key checks are re-enabled on error (MySQL only)
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            // Fixed: previously this exception was swallowed entirely with
            // no logging and no re-throw, so a failed truncate looked
            // identical to a successful one to the caller. Now it's logged
            // and re-thrown so callers/monitoring can actually see it.
            Log::error('Failed to truncate transactions', [
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function statusCount(): array
    {
        return [
            'return' => $this->transaction->newQuery()
                ->where('status', 'return')
                ->where('user_id', auth()->id())
                ->where('is_tagged', false)
                ->whereNotNull('reason')
                ->count(),
        ];
    }
}
