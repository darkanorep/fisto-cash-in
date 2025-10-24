<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PermissionFilter extends QueryFilters
{
    protected array $allowedFilters = ['name'];

    protected array $columnSearch = ['name'];

    protected array $relationSearch = [
        'role' => ['name']
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
