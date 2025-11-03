<?php

namespace App\Models;

use App\Filters\PermissionFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = ["name", "role_id"];
    protected $hidden = ["role_id"];

    protected string $default_filters = PermissionFilter::class;

    public function role() {
        return $this->belongsTo(Role::class)->withTrashed();
    }
}
