<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AccountTitleFilter extends QueryFilters
{
    protected array $allowedFilters = ['code', 'name'];

    protected array $columnSearch = ['code', 'name'];
}
