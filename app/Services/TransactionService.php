<?php

namespace App\Services;

use App\Events\RequestNotificationCount;
use App\Exports\ActivityExport;
use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    private const GROUP_COLUMN_BY_TYPE = [
        Transaction::ARCANA         => 'sync_payment_record_id',
        Transaction::FLOCK_FORTRESS => 'payment_group_id',
    ];

    public function getAllTransactions(Request $request)
    {

        $status      = $request->input('status');
        $paymentType = $request->input('payment_type');


        $query = $this->transaction->query()
            ->with(['bank', 'customer', 'slips', 'voucherAccountEntries'])
            ->where('user_id', auth()->id());

        $query->paymentType($paymentType);

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

        if (isset($request['mode_of_payment'])) {
            $query->modeOfPayment($request['mode_of_payment']);
        }

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
            'payment_group_id'        => $data['payment_group_id'] ?? null,
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
            'applied_amount'          => $data['applied_amount'] ?? null,
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
        $reason = $data instanceof Request
            ? $data->input('reason')
            : ($data['reason'] ?? null);

        $payload     = ['status' => 'void', 'reason' => $reason];
        $groupColumn = self::GROUP_COLUMN_BY_TYPE[$transaction->type] ?? null;

        if (blank($transaction->sync_id) || $groupColumn === null) {
            return $transaction;
        }

        DB::transaction(function () use ($transaction, $payload, $groupColumn) {
            $groupId = $transaction->{$groupColumn};

            if (filled($groupId)) {
                $statuses = $this->transaction->newQuery()
                    ->where($groupColumn, $groupId)
                    ->lockForUpdate()
                    ->pluck('status');

                if ($statuses->contains('file')) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaction cannot be voided.',
                    ]);
                }

                $this->transaction->newQuery()
                    ->where($groupColumn, $groupId)
                    ->whereKeyNot($transaction->getKey())
                    ->update($payload);
            } elseif ($transaction->status === 'file') {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaction cannot be voided.',
                ]);
            }

            $transaction->update($payload);

            $this->logActivityOn($transaction, 'Transaction Voided', $payload, 'voided');

            if ($transaction->type === Transaction::ARCANA) {
                // Runs only if the surrounding DB transaction commits.
                DB::afterCommit(fn () => $this->pushVoidToArcana($transaction));
            }
        });

        return $transaction;
    }

    /**
     * Side effect only: the local void is the source of truth, so failures
     * here are logged for reconciliation and never bubble up to the caller.
     */
    private function pushVoidToArcana(Transaction $transaction): void
    {
        $context = [
            'transaction_id'       => $transaction->id,
            'paymentTransactionId' => $transaction->sync_id,
        ];

        try {
            $response = Http::withHeaders(['api-key' => $this->arcanaApiKey])
                ->timeout(10)
                ->withQueryParameters(['paymentTransactionId' => $transaction->sync_id])
                ->post($this->arcanaUrl . 'void');

            $response->successful()
                ? Log::info('Arcana void call succeeded', $context + ['status' => $response->status()])
                : Log::warning('Arcana void call returned an error response', $context + [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
        } catch (ConnectionException $e) {
            Log::error('Arcana void call failed: connection error', $context + ['message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Arcana void call failed unexpectedly', $context + ['message' => $e->getMessage()]);
        }
    }

    /**
     * Returns every row in a multi-row Flock Fortress payment group.
     *
     * @return bool true if the group was returned; false if the transaction is
     *              not part of a multi-row group (caller handles it as a single).
     */
    public function returnFlockFortressGroup(
        Transaction $transaction,
        ?string $reason,
        ?string $bankCodeDeposit = null
    ): bool {
        // No group id means no group. Never query where('payment_group_id', null).
        if (blank($transaction->payment_group_id)) {
            return false;
        }

        $payload = [
            'status'            => 'return',
            'is_tagged'         => false,
            'reason'            => $reason,
            'bank_code_deposit' => $bankCodeDeposit,
        ];

        return DB::transaction(function () use ($transaction, $payload) {
            $group = $this->transaction->newQuery()
                ->where('type', Transaction::FLOCK_FORTRESS)
                ->where('payment_group_id', $transaction->payment_group_id)
                ->lockForUpdate()
                ->get();

            // Single-row "group": let the normal return flow handle it.
            if ($group->count() < 2) {
                return false;
            }

            $notReady = $group->filter(
                fn (Transaction $t) => ! $t->is_tagged || ! $t->is_cleared
            );

            if ($notReady->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'transaction' => 'All transactions in the group must be tagged and cleared before they can be returned.',
                ]);
            }

            $this->transaction->newQuery()
                ->whereKey($group->modelKeys())
                ->update($payload);

            $transaction->refresh();

            $this->logActivityOn($transaction, 'Transaction Returned', $payload, 'returned');

            $group->load('user');

            DB::afterCommit(function () use ($group) {
                $group->unique('user_id')->each(
                    fn (Transaction $t) => event(new RequestNotificationCount($t->user))
                );
            });

            return true;
        });
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
