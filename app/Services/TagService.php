<?php

namespace App\Services;

use App\Events\ClearNotificationCount;
use App\Events\RequestNotificationCount;
use App\Exports\ActivityExport;
use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class TagService
{
    use ActivityLogTrait;
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransactions($request) {
        $query = $this->transaction->query();

        $filters = $request instanceof Request ? $request->all() : $request;

        // Then apply additional conditions based on specific status values
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                case 'receive':
                    $query->status($filters['status'])
                        ->where([
                            'is_tagged' => false,
                            'is_cleared' => false,
                        ]);
                    break;
                case 'tag':
                    $query->status($filters['status'])
                        ->where('is_tagged', true);
                    break;
                case 'clear':
                    $query->status($filters['status'])
                        ->where('is_cleared', true);
                    break;

                case 'return-tag':
                    // Don't call scope here - 'return-tag' is not a real status
                    $query->where([
                        'is_tagged' => true,
                        'status' => 'return',
                    ])
                    ->whereNotNull('reason')
                    ->whereNull('date_cleared');
                    break;

                default:
                    $query->status($filters['status']);
                    break;
            }
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->date([
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
            ]);
        }

        return $query->with(['bank'])->useFilters()->dynamicPaginate();
    }

    public function action($request) {

        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');
        $depositDate = $request->input('deposit_date');
        $bankDeposit = $request->input('bank_deposit');
        $depositRemarks = $request->input('deposit_remarks');
        $series = $request->input('series');
        $reason = $request->input('reason');
        $transaction = $this->transaction->findOrFail($transactionId);

        switch($status) {

            case 'receive':
                $transaction->reason = null;
                $transaction->deposit_date = null;
                $transaction->bank_deposit = null;
                $transaction->deposit_remarks = null;
                $transaction->is_tagged = false;
                break;

            case 'tag':
                $transaction->is_tagged = true;
                $transaction->deposit_date = $depositDate ?? null;
                $transaction->bank_deposit = $bankDeposit ?? null;
                $transaction->deposit_remarks = $depositRemarks ?? null;
                if (!$transaction->tag_number || !str_starts_with($transaction->tag_number, $series)) {
                    $transaction->tag_number = $this->generateTagNumber($series);
                }
                break;

            case 'deposit':
                $transaction->deposit_date = $depositDate ?? null;
                $transaction->bank_deposit = $bankDeposit ?? null;
                $transaction->deposit_remarks = $depositRemarks ?? null;
                event(new ClearNotificationCount());
                break;

            case 'return':
                $transaction->is_tagged = false;
                $transaction->reason = $reason;
                $transaction->deposit_date = $transaction->deposit_date ?? null;
                $transaction->bank_deposit = $transaction->bank_deposit ?? null;
                $transaction->deposit_remarks = $transaction->deposit_remarks ?? null;
                $transaction->tag_number = $transaction->tag_number ?? null;
                event(new RequestNotificationCount($transaction->user));
                break;

            case 'void':
                $transaction->reason = $reason;
                break;
        }

        $transaction->status = $status;
        $transaction->save();
        $this->logActivityOn($transaction, 'Transaction ' . ucfirst($status), [
            'status' => $status,
            'deposit_date' => $depositDate,
            'bank_deposit' => $bankDeposit,
            'deposit_remarks' => $depositRemarks,
            'tag_number' => $transaction->tag_number,
            'reason' => $reason,
        ], 'tag:'.$status);

        return $transaction;

    }

    public function generateTagNumber($series) {
        // Get all tags with the CHOSEN series
        $tagsWithSeries = Transaction::whereNotNull('tag_number')
            ->where('tag_number', 'like', $series . '%')
            ->get()
            ->map(function ($item) use ($series) {
                // Extract only the numeric portion
                return (int) str_replace($series, '', $item->tag_number);
            });

        // Find max number of that series, default to 0 if none exist
        $maxNumber = $tagsWithSeries->max() ?? 0;

        // Increment to get next number
        $nextTagNumber = $maxNumber + 1;

        return $series . str_pad($nextTagNumber, 4, '0', STR_PAD_LEFT);
    }

    public function export($request) {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $status  = $request->input('status');
        $mode_of_payment = $request->input('mode_of_payment');

        $statusLabel = $status ? strtoupper(str_replace('tag:', '', $status)): 'All';
        $filename = "TAG-{$statusLabel}_{$dateFrom}_to_{$dateTo}.xlsx";
        return Excel::download(new ActivityExport($dateFrom, $dateTo, $status, $mode_of_payment), $filename);
    }

    public function statusCount() : array {
        $baseQuery = $this->transaction->newQuery()
            ->where('status', 'pending');

        $grouped = (clone $baseQuery)
            ->selectRaw('LOWER(mode_of_payment) as mode_of_payment, COUNT(*) as total')
            ->groupByRaw('LOWER(mode_of_payment)')
            ->pluck('total', 'mode_of_payment');

        $returnQuery = $this->transaction->newQuery()
            ->where('status', 'return')
            ->whereNotNull('reason')
            ->where('is_cleared', false);

        return [
            'pending' => [
                'cash' => (int) ($grouped['cash'] ?? 0),
                'online' => (int) ($grouped['online'] ?? 0),
                'cheque' => (int) ($grouped['cheque'] ?? 0),
                'total' => (int) $grouped->sum(),
            ],
            'return' => $returnQuery->count(),
        ];
    }
}
