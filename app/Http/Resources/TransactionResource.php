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
            'type' => $this->type,
            'category' => $this->category,
            'reference_no' => $this->reference_no,
            'transaction_date' => $this->transaction_date,
            'payment_date' => $this->payment_date,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ],
            'mode_of_payment' => $this->mode_of_payment,
            'bank' => [
                'id' => $this->bank->id,
                'name' => $this->bank->name,
            ],
            'check' => [
                'no' => $this->check_no,
                'date' => $this->check_date,
            ],
            'amount' => $this->amount,
            'remaining_balance' => $this->remaining_balance,
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
                'employee_number' => $this->user->employee_number,
                'name' => $this->user->getFullNameAttribute(),
            ]
        ];
    }
}
