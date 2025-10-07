<?php

namespace App\Services;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankService
{
    protected $bank;

    public function __construct(Bank $bank)
    {
        $this->bank = $bank;
    }

    public function getBanks(Request $request)
    {
        return $this->bank->useFilters()->dynamicPaginate();
    }

    public function createBank($data)
    {
        return $this->bank->create($data);
    }

    public function getBankById($id)
    {
        return $this->bank->find($id);
    }

    public function updateBank($bank, $data)
    {
        $bank->update($data);
        return $bank;
    }

    public function changeStatus($id)
    {
        $bank = $this->bank->withTrashed()->find($id);

        if ($bank->trashed()) {
            $bank->restore();
        } else {
            $bank->delete();
        }

        return $bank;
    }
}