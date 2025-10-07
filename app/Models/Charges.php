<?php

namespace App\Models;

use App\Filters\ChargesFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charges extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = ChargesFilter::class;

    protected $guarded = [];
}
