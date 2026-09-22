<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class EntryFilter extends QueryFilters
{
    protected array $allowedFilters = ['payment_mode'];

    protected array $columnSearch = ['payment_mode'];

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
