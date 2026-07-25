<?php

namespace App\Models;

use App\Filters\PendingUserFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingUser extends Model
{
    use HasFactory, softDeletes, Filterable;

    protected $guarded = [];
    protected string $default_filters = PendingUserFilter::class;
}
