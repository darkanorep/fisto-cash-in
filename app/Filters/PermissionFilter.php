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
}
