<?php

namespace App\Services;

use App\Events\ClearNotificationCount;
use App\Events\RequestNotificationCount;
use App\Exports\ActivityExport;
use App\Models\Transaction;
use App\Traits\ActivityLogTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class TagService
{
    use ActivityLogTrait;
    protected $transaction;
    private mixed $arcanaApiKey;
    private mixed $arcanaUrl;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->arcanaApiKey = config('app.arcana_api_key');
        $this->arcanaUrl = config('app.arcana_url');
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
                    $query
                        ->where([
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

        $query->orderBy('updated_at', 'desc');

        return $query->with(['bank'])->useFilters()->dynamicPaginate();
    }

    public function action($request) {

        $transactionIds = $request->input('transaction_id');
        $status = $request->input('status');
        $depositDate = $request->input('deposit_date');
        $bankDeposit = $request->input('bank_deposit');
        $bankCodeDeposit = $request->input('bank_code_deposit');
        $depositRemarks = $request->input('deposit_remarks');
        $series = $request->input('series');
        $reason = $request->input('reason');

        $tagNumber = [];

        foreach ($transactionIds as $transactionId) {
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
                    $transaction->bank_code_deposit = $bankCodeDeposit ?? null;

                    if (!$transaction->tag_number || !str_starts_with($transaction->tag_number, $series)) {
                        if ($transaction->mode_of_payment === 'cheque') {
                            $refKey = 'cheque_' . ($transaction->reference_no ?? 'null');

                            if (!isset($tagNumber[$refKey]) || !str_starts_with($tagNumber[$refKey], $series)) {
                                $tagNumber[$refKey] = $this->generateTagNumber($series);
                            }
                            $transaction->tag_number = $tagNumber[$refKey];
                        } else {
                            $transaction->tag_number = $this->generateTagNumber($series);
                        }
                    } elseif ($transaction->mode_of_payment === 'cheque') {
                        // keep the cheque group cache in sync even when this transaction
                        // already had a valid tag_number, so later transactions in the
                        // same reference_no group reuse it instead of generating a new one
                        $refKey = 'cheque_' . ($transaction->reference_no ?? 'null');
                        $tagNumber[$refKey] = $transaction->tag_number;
                    }

                    if ($transaction->sync_payment_record_id) {
                        $payload = [
                            'paymentRecordId' => $transaction->sync_payment_record_id,
                            'paymentMethod' => $transaction->mode_of_payment,
                            'paymentAmount' => $transaction->amount,
                            'aTag' => $transaction->tag_number,
                        ];

                        Log::info('Arcana tag payload for transaction ' . $transaction->id, $payload);

                        try {
                            $response = Http::withHeaders(['api-key' => $this->arcanaApiKey])->post(
                                $this->arcanaUrl . 'tag', $payload
                            );

                            Log::info('Arcana tag response for transaction ' . $transaction->id, [
                                'status' => $response->status(),
                                'body' => $response->body(),
                            ]);

                            $response->throw();


                        } catch (\Throwable $e) {
                            Log::error('Arcana tag sync failed for transaction ' . $transaction->id, [
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                    break;

                case 'deposit':
                    $transaction->deposit_date = $depositDate ?? null;
                    $transaction->bank_deposit = $bankDeposit ?? null;
                    $transaction->bank_code_deposit = $bankCodeDeposit ?? null;
                    $transaction->deposit_remarks = $depositRemarks ?? null;
                    event(new ClearNotificationCount());
                    break;

                case 'return':
                    $transaction->is_tagged = false;
                    $transaction->reason = $reason;
                    $transaction->deposit_date = $transaction->deposit_date ?? null;
                    $transaction->bank_deposit = $transaction->bank_deposit ?? null;
                    $transaction->bank_code_deposit = $bankCodeDeposit ?? null;
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
                'bank_code_deposit' => $bankCodeDeposit,
                'deposit_remarks' => $depositRemarks,
                'tag_number' => $transaction->tag_number,
                'reason' => $reason,
            ], 'tag:'.$status);
        }
    }

    public function generateTagNumber($series) {
        // Get current month and year
        $monthYear = Carbon::now()->format('Y-m');

        // Create the prefix with series, year, and month
        $prefix = $series . '-' . $monthYear . '-';

        // Get all tags with the CHOSEN series and current month/year
        $tagsWithSeries = Transaction::whereNotNull('tag_number')
            ->where('tag_number', 'like', $prefix . '%')
            ->get()
            ->map(function ($item) use ($prefix) {
                // Extract only the numeric portion after the prefix
                return (int) str_replace($prefix, '', $item->tag_number);
            });

        // Find max number of that series and month/year, default to 0 if none exist
        $maxNumber = $tagsWithSeries->max() ?? 0;

        // Increment to get next number
        $nextTagNumber = $maxNumber + 1;

        return $prefix . str_pad($nextTagNumber, 4, '0', STR_PAD_LEFT);
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
            ->where('is_tagged', true)
            ->where('is_cleared', false);
//            ->where('user_id', !auth()->id());

        return [
            'pending' => [
                'cash' => (int) ($grouped['cash'] ?? 0),
                'online' => (int) ($grouped['online'] ?? 0),
                'cheque' => (int) ($grouped['cheque'] ?? 0),
                'advance_payment' => (int) ($grouped['advance payment'] ?? 0),
                'total' => (int) $grouped->sum(),
            ],
            'return' => $returnQuery->count(),
        ];
    }
}
