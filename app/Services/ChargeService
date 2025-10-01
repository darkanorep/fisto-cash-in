<?php

namespace App\Services;

use App\Models\Charges;
use Illuminate\Http\Request;

class ChargeService
{
    protected $charge;

    public function __construct(Charges $charge) {
        $this->charge = $charge;
    }

    public function getAllCharges(Request $request) {
        return $this->charge->get();
    }

    public function createCharge($data) {
        return $this->charge->create($data);
    }

    public function getChargeById($id) {
        return $this->charge->find($id);
    }

    public function updateCharge($charge, $data) {
        $charge->update($data);
        
        return $charge;
    }

    public function changeStatus($id) {
        $charge = $this->charge->withTrashed()->find($id);

        if ($charge->trashed()) {
            $charge->restore();
        } else {
            $charge->delete();
        }

        return $charge;
    }
}