<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UserFilter extends QueryFilters
{
    protected array $allowedFilters = [
        'employee_id',
        'first_name',
        'last_name',
        'postion',
        'username'
    ];

    protected array $columnSearch = [
        'employee_id',
        'first_name',
        'last_name',
        'position',
        'username'
    ];

    protected array $relationSearch = [
        'roles' => ['name']
    ];

    public function status($status)
    {
        return $this->builder->withTrashed()->when(!$status, function ($query) {
            $query->whereNotNull('deleted_at');
        }, function ($query) use ($status) {
            $query->when($status, function ($query){
                $query->whereNull('deleted_at');
            });
        });
    }
}
