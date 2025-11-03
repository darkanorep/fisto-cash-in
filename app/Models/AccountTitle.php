<?php

namespace App\Models;

use App\Filters\AccountTitleFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTitle extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = AccountTitleFilter::class;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'account_group',
        'sub_group',
        'financial_statement',
        'normal_balance',
        'unit',
    ];
}
