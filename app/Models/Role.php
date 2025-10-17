<?php

namespace App\Models;

use App\Filters\RoleFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = ["name"];
    protected $hidden = ["created_at"];
    protected string $default_filters = RoleFilter::class;

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_users', 'role_id', 'user_id');
    }

    public function permissions() {
        return $this->hasMany(Permission::class, 'role_id', 'id');
    }

    const ADMIN = 'Admin';
    const REQUESTOR = 'Requestor';
}
