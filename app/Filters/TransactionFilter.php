<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class TransactionFilter extends QueryFilters
{
    protected array $allowedFilters = ['type', 'category', 'customer_name', 'mode_of_payment', 'bank_name', 'charge_name', 'transaction_date', 'payment_date', 'amount'];

    protected array $columnSearch = ['type', 'category', 'customer_name', 'mode_of_payment', 'bank_name', 'charge_name', 'transaction_date', 'payment_date', 'amount'];

    protected array $relations = ['customer', 'bank', 'user', 'charge', 'slips'];

}
