<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slip extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'type',
        'number',
        'amount',
    ];

    public function transactions()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    
    }
}
