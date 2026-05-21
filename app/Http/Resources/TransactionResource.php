<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'type' => $this->type,
            'category' => $this->category,
            'sync_id' => $this->sync_id,
            'sync_payment_record_id' => $this->sync_payment_record_id,
            'distribution_type' => $this->distribution_type,
            'reference_no' => $this->reference_no,
            'transaction_date' => $this->transaction_date
                ? \Carbon\Carbon::parse($this->transaction_date)->format('Y-m-d H:i:s')
                : null,
            'payment_date' => $this->payment_date
                ? \Carbon\Carbon::parse($this->payment_date)->format('Y-m-d H:i:s')
                : null,
            'customer' => [
                'id' => $this->customer_id ?? null,
                'code' => $this->customer_code ?? null,
                'name' => $this->customer_name ?? null,
            ],
            'mode_of_payment' => $this->mode_of_payment ?? null,
            'bank' => [
                'id' => $this->bank_id ?? null,
                'code' => $this->bank_code ?? null,
                'name' => $this->bank_name ?? null,
            ],
            'cheque' => [
                'no' => $this->check_no,
                'date' => $this->check_date
                    ? \Carbon\Carbon::parse($this->check_date)->format('Y-m-d H:i:s')
                    : null
            ],
            'amount' => $this->amount,
            'remaining_balance' => $this->remaining_balance,
            'amount_paid' => $this->relationLoaded('slips')
                ? $this->slips->sum('actual_amount_paid')
                : $this->slips()->sum('actual_amount_paid'),
            'total_amount' => $this->relationLoaded('slips')
                ? $this->slips->sum('amount')
                : $this->slips()->sum('amount'),
            'remarks' => $this->remarks,
            'charge' => [
                'id' => $this->charge->id,
                'name' => $this->charge->name,
                'company' => [
                    'code' => $this->charge->company_code,
                    'name' => $this->charge->company_name,
                ],
                'business_unit' => [
                    'code' => $this->charge->business_unit_code,
                    'name' => $this->charge->business_unit_name,
                ],
                'department' => [
                    'code' => $this->charge->department_code,
                    'name' => $this->charge->department_name,
                ],
                'unit' => [
                    'code' => $this->charge->unit_code,
                    'name' => $this->charge->unit_name,
                ],
                'sub_unit' => [
                    'code' => $this->charge->sub_unit_code,
                    'name' => $this->charge->sub_unit_name,
                ],
                'location' => [
                    'code' => $this->charge->location_code,
                    'name' => $this->charge->location_name,
                ]
            ],
            'user' => [
                'id' => $this->user->id,
                'employee_number' => $this->user->employee_id,
                'name' => $this->user->getFullNameAttribute(),
            ],
            'slips' => SlipResource::collection($this->whenLoaded('slips')),
            'tag_number' => $this->tag_number,
            'date_cleared' => $this->date_cleared
                ? \Carbon\Carbon::parse($this->date_cleared)->format('Y-m-d H:i:s')
                : null,
            'date_filed' => $this->date_filed
                ? \Carbon\Carbon::parse($this->date_filed)->format('Y-m-d H:i:s')
                : null,
            'bank_deposit' => $this->bank_deposit,
            'bank_code_deposit' => $this->bank_code_deposit,
            'deposit_date' => $this->deposit_date
                ? \Carbon\Carbon::parse($this->deposit_date)->format('Y-m-d H:i:s')
                : null,
            'reason' => $this->reason,
            'logs' => $this->logs->map(function ($log) {
                return [
                    'description' => $log->description,
                    'causer' => $log->causer?->getFullNameAttribute(),
                    'logged_at' => \Carbon\Carbon::parse($log->created_at)->format('l, d M Y h:i:s A'),
                ];
            })
        ];
    }
}
