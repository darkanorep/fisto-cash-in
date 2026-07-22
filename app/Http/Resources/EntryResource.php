<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
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
            'description' => $this->description,
            'account_titles' => $this->whenLoaded('accountTitles', function () {
                return $this->accountTitles->map(function ($accountTitle) {
                    return [
                        'id'                  => $accountTitle->id,
                        'code'                => $accountTitle->code,
                        'title'               => $accountTitle->name,
                        'account_type'        => $accountTitle->account_type,
                        'account_group'       => $accountTitle->account_group,
                        'sub_group'           => $accountTitle->sub_group,
                        'financial_statement' => $accountTitle->financial_statement,
                        'normal_balance'     => $accountTitle->normal_balance,
                        'unit'                => $accountTitle->unit,
                        'allocation'          => $accountTitle->allocation,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
