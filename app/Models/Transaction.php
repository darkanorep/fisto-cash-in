<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

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
}
