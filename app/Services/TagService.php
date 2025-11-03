<?php

namespace App\Services;

use App\Models\Transaction;

class TagService
{
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransactions($filters = []) {
        $query = $this->transaction->query();

        // Apply status scope first (this handles the basic status filtering)
        if (isset($filters['status'])) {
            $query->status($filters['status']); // This calls your scope
        }

        // Then apply additional conditions based on specific status values
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                case 'received':
                    $query->where([
                        'is_tagged' => false,
                        'is_cleared' => false,
                    ]);
                    break;
                case 'tagged':
                    $query->where('is_tagged', true);
                    break;
                case 'cleared':
                    $query->where('is_cleared', true);
                    break;
                // Add more status-specific filters as needed
            }
        }

        return $query->dynamicPaginate();
    }
}