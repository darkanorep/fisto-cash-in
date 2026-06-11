<?php

namespace App\Http\Resources;

use App\Models\Slip;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!isset($this->actual_amount_paid, $this->remaining_amount)) {
            // Get all slips with the same number group, ordered
            $relatedSlips = Slip::join('transactions', 'transactions.id', '=', 'slips.transaction_id')
                ->where('transactions.status', '!=', 'void')
                ->whereIn('slips.number', function ($q) {
                    $q->select('number')
                        ->from('slips')
                        ->where('transaction_id', $this->transaction_id);
                })
                ->select('slips.number', 'slips.amount')
                ->distinct()
                ->orderBy('slips.number')
                ->get();

            $totalPaid = Transaction::join('slips', 'transactions.id', '=', 'slips.transaction_id')
                ->whereIn('slips.number', $relatedSlips->pluck('number'))
                ->where('transactions.status', '!=', 'void')
                ->sum('transactions.amount');

            // Distribute payment in order
            $remainingPayment = $totalPaid;
            $actualPaid       = 0;
            $remainingAmount  = 0;

            foreach ($relatedSlips as $slip) {
                $consumed = min($slip->amount, max(0, $remainingPayment));
                $remainingPayment -= $consumed;

                if ($slip->number === $this->number) {
                    $actualPaid      = $consumed;
                    $remainingAmount = $slip->amount - $consumed;
                    break;
                }
            }

            $this->actual_amount_paid = $actualPaid;
            $this->remaining_amount   = $remainingAmount;
        }

        return [
            'type'               => $this->type,
            'number'             => $this->number,
            'amount'             => $this->amount,
            'actual_amount_paid' => $this->actual_amount_paid,
            'remaining_amount'   => $this->remaining_amount,
        ];
    }
}
