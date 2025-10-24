<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BankFilter extends QueryFilters
{
    protected array $allowedFilters = ['code', 'name', 'branch', 'account_number'];

    protected array $columnSearch = ['code', 'name', 'branch', 'account_number'];

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
