<?php

namespace App\Models;

use App\Filters\BankFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = BankFilter::class;

    protected $fillable = [
        'code',
        'name',
        'account_number',
        'branch',
    ];

    protected $hidden = [
        'created_at',
    ];
}
