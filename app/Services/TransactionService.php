<?php

namespace App\Services;


use App\Exports\ActivityExport;
use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class TransactionService
{
    use ActivityLogTrait;
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getAllTransactions($request)
    {
        $query = $this->transaction->query()->with([
            'bank',
            'customer',
            'slips'
        ])->where('user_id', auth()->id());

        $status      = $request->input('status');
        $paymentType = $request->input('payment_type');

        if ($status) {
            switch ($status) {
                case 'return-request':
                    $query->where('status', 'return')
                        ->where('is_tagged', false)
                        ->whereNotNull('reason');
                    break;

                default:
                    $query->status($status);
                    break;
            }
        } else {
            // No status filter requested -> hide voided transactions by default
            $query->whereNotIn('status', ['void']);
        }

        $query->paymentType($paymentType);

        if (isset($request['date_from']) && isset($request['date_to'])) {
            $query->date([
                'date_from' => $request['date_from'],
                'date_to' => $request['date_to'],
            ]);
        }

        $query->orderBy('updated_at', 'desc');

        $transactions = $query->get();

        // Distribute payment per transaction's slips
        $transactions->each(function ($transaction) {
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
        });

        return $transactions;
    }
    private function buildTransactionData($data, $additionalFields = [])
    {
        $baseData = [
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'sync_id' => $data['sync_id'] ?? null,
            'sync_payment_record_id' => $data['sync_payment_record_id'] ?? null,
            'distribution_type' => $data['distribution_type'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'customer_id' => $data['customer']['id'],
            'customer_code' => $data['customer']['code'] ?? null,
            'customer_name' => $data['customer']['name'],
            'mode_of_payment' => $data['mode_of_payment'],
            'payment_type' => $data['payment_type'],
            'bank_id' => $data['bank']['id'] ?? null,
            'bank_code' => $data['bank']['code'] ?? null,
            'bank_name' => $data['bank']['name'] ?? null,
            'check_no' => $data['cheque']['no'] ?? $data['check']['no'] ?? null,
            'check_date' => $data['cheque']['date'] ?? $data['check']['date'] ?? null,
            'amount' => $data['amount'],
            'remaining_balance' => $data['remaining_balance'] ?? 0,
            'charge_id' => $data['charge']['id'],
            'charge_name' => $data['charge']['name'],
            'remarks' => $data['remarks'] ?? null,
        ];

        return array_merge($baseData, $additionalFields);
    }
    public function createTransaction($data)
    {
        $transactionData = $this->buildTransactionData($data);
        $transaction = $this->transaction->create($transactionData);

        if (!empty($data['slip'])) {
            foreach ($data['slip'] as $slip) {
                $transaction->slips()->create([
                    'type' => $slip['type'],
                    'number' => $slip['number'],
                    'amount' => $slip['amount'],
                    'actual_amount_paid' => $slip['actual_amount_paid'],
                ]);
            }

            $this->logActivityOn($transaction, 'Slips Added for Transaction', ['slips' => $data['slip']]);
        }

        $this->logActivityOn($transaction, 'Transaction Created', $transactionData);

        return $transaction;
    }
    public function getTransactionById($id)
    {
        return $this->transaction->with([
            'slips',
            'bank',
            'customer'
            ])->find($id);
    }
    public function updateTransaction($transaction, $data)
    {
        $transactionData = $this->buildTransactionData($data, ['status' => 'pending']);
        $transaction->update($transactionData);

        if (!empty($data['slip'])) {
            $transaction->slips()->delete();

            foreach ($data['slip'] as $slip) {
                $transaction->slips()->create([
                    'type' => $slip['type'],
                    'number' => $slip['number'],
                    'amount' => $slip['amount'],
                    'actual_amount_paid' => $slip['actual_amount_paid'],
                ]);
            }

            $this->logActivityOn($transaction, 'Slips Updated for Transaction', ['slips' => $data['slip']], 'updated');
        }

        $this->logActivityOn($transaction, 'Transaction Updated', $transactionData, 'updated');

        return $transaction;
    }
    public function voidTransaction($transaction, $data)
    {
        $transactionData = [
            'status' => 'void',
            'reason' => $data['reason'] ?? null,
        ];

        $transaction->update($transactionData);

        $this->logActivityOn($transaction, 'Transaction Voided', $transactionData, 'voided');

        return $transaction;
    }
    public function export($request) {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $state  = $request->input('state');
        $status  = $request->input('status');
        $mode_of_payment = $request->input('mode_of_payment');
        $userId = $request->input('user_id');

        $stateLabel = filled($state) ? strtoupper($state) : 'ALL';
        $statusLabel = filled($status) ? strtoupper($status) : 'ALL';
        $dateFromLabel = filled($dateFrom) ? $dateFrom : 'START';
        $dateToLabel = filled($dateTo) ? $dateTo : 'END';

        $filename = "T{$stateLabel}-{$statusLabel}_{$dateFromLabel}_to_{$dateToLabel}.xlsx";
        return Excel::download(new ActivityExport($dateFrom, $dateTo, $state, $status, $userId, $mode_of_payment), $filename);
    }
    public function truncateTransactions(): void
    {
        try {
            $driver = DB::connection()->getDriverName();

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
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }
    public function statusCount() {
        return [
            'return' => $this->transaction->newQuery()->where('status', 'return')
                ->where('user_id', auth()->id())
                ->where('is_tagged', false)
                ->whereNotNull('reason')
                ->count(),
        ];
    }

}
