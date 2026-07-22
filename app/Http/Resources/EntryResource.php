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
            'account_titles' => collect($this->account_title_entries ?? [])->map(function ($row) {
                return [
                    'id'                  => $row->account_title_id,
                    'code'                => $row->code,
                    'title'               => $row->title,
                    'account_type'        => $row->account_type,
                    'account_group'       => $row->account_group,
                    'sub_group'           => $row->sub_group,
                    'financial_statement' => $row->financial_statement,
                    'normal_balance'     => $row->normal_balance,
                    'unit'                => $row->unit,
                    'allocation'          => $row->allocation,
                ];
            })->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
