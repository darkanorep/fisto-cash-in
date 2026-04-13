<?php

namespace App\Models;

use App\Filters\TransactionFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class Transaction extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = [];
    protected string $default_filters = TransactionFilter::class;

    public function logs() {
        return $this->morphMany(Activity::class, 'subject')->orderBy('id', 'desc');
    }

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

    public function scopeDate($query, array $dateRange) {
        $dateFrom = $dateRange['date_from'] ?? null;
        $dateTo = $dateRange['date_to'] ?? null;

        return $query->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        });
    }

    public function scopeDepositDate($query, array $dateRange) {
        $depositDateFrom = $dateRange['deposit_date_from'] ?? null;
        $depositDateTo = $dateRange['deposit_date_to'] ?? null;

        return $query->when($depositDateFrom && $depositDateTo, function ($q) use ($depositDateFrom, $depositDateTo) {
            $q->whereBetween('deposit_date', [$depositDateFrom, $depositDateTo]);
        });
    }
}
