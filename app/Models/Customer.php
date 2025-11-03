<?php

namespace App\Models;

use App\Filters\CustomerFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = CustomerFilter::class;

    protected $fillable = ['code', 'name'];
}
