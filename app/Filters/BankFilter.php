<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BankFilter extends QueryFilters
{
    protected array $allowedFilters = ['code', 'name', 'branch', 'account_number'];

    protected array $columnSearch = ['code', 'name', 'branch', 'account_number'];
}
