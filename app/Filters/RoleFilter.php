<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class RoleFilter extends QueryFilters
{
    protected array $allowedFilters = ['name'];

    protected array $columnSearch = ['name'];

    protected array $relationSearch = [
        'users' => ['first_name', 'last_name']
    ];
}
