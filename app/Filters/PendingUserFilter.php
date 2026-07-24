<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PendingUserFilter extends QueryFilters
{
    protected array $allowedFilters = [
        'employee_id',
        'first_name',
        'last_name'
    ];

    protected array $columnSearch = [
        'employee_id',
        'first_name',
        'last_name'
    ];

    public function status($status) {
        return $this->builder->withTrashed()->when(!$status, function ($query) {
            $query->whereNotNull('deleted_at');
        }, function ($query) use ($status) {
            $query->when($status, function ($query){
                $query->whereNull('deleted_at');
            });
        });
    }
}
