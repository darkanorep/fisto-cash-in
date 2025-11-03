<?php

namespace App\Models;

use App\Filters\TransactionFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $guarded = [];
    protected string $default_filters = TransactionFilter::class;


    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function bank() {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function charge() {
        return $this->belongsTo(Charges::class, 'charge_id');
    }

    public function slips() {
        return $this->hasMany(Slip::class, 'transaction_id');
    }

    public function scopeStatus($query, $status)
    {
        return $query->when($status, function ($q) use ($status) {
            $q->where('status', $status);
        });
    }
}
