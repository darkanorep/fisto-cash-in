<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class EntryFilter extends QueryFilters
{
    protected array $allowedFilters = ['description'];

    protected array $columnSearch = ['description'];
}
