<?php

namespace App\Models;

use App\Filters\EntryFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entry extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $guarded = [];
    protected string $default_filters = EntryFilter::class;

    public function accountTitles(): BelongsToMany {
        return $this->belongsToMany(
            AccountTitle::class,
            'account_title_entry',
            'entry_id',
            'account_title_id'
        )->withTimestamps();
    }
}
