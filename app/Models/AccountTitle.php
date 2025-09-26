<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTitle extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $dates = ['created_at'];
}
