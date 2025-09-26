<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountTitleResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'account_type' => $this->account_type,
            'account_group' => $this->account_group,
            'sub_group' => $this->sub_group,
            'financial_statement' => $this->financial_statement,
            'normal_balance' => $this->normal_balance,
            'unit' => $this->unit
        ];
    }
}
