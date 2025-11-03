<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'name' => $this->getFullNameAttribute(),
            'position' => $this->position,
            'roles' => $this->whenLoaded('roles', function() {
                return $this->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                });
            }),
            'charge' => $this->whenLoaded('charge', function() {
                return [
                    'id' => $this->charge->id,
                    'code' => $this->charge->code,
                    'name' => $this->charge->name,
                    'company_code' => $this->charge->company_code,
                    'company_name' => $this->charge->company_name,
                    'business_unit_code' => $this->charge->business_unit_code,
                    'business_unit_name' => $this->charge->business_unit_name,
                    'department_code' => $this->charge->department_code,
                    'department_name' => $this->charge->department_name,
                    'unit_code' => $this->charge->unit_code,
                    'unit_name' => $this->charge->unit_name,
                    'sub_unit_code' => $this->charge->sub_unit_code,
                    'sub_unit_name' => $this->charge->sub_unit_name,
                    'location_code' => $this->charge->location_code,
                    'location_name' => $this->charge->location_name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
