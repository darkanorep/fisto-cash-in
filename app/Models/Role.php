<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["name"];
    protected $hidden = ["created_at"];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_users', 'role_id', 'user_id');
    }

    const ADMIN = 'Admin';
}
