<?php

namespace App\Services;

use App\Models\Charges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChargeService
{
    protected $charge;

    public function __construct(Charges $charge) {
        $this->charge = $charge;
    }

    public function getAllCharges(Request $request) {
        return $this->charge->useFilters()->dynamicPaginate();
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

    public function sync() {
        $url = Http::withHeaders(['API_KEY' => config('app.one_charging_key')])->get('https://api-one.rdfmis.com/api/charging_api', ['pagination' => 'none']);

        $data = json_decode($url->body(), true);

        collect(data_get($data, 'data'))->chunk(100)->each(function ($chunk) {
            foreach ($chunk as $item) {
                $chargeData = [
                    'code' => data_get($item, 'code'),
                    'name' => data_get($item, 'name'),
                    'company_code' => data_get($item, 'company_code'),
                    'company_name' => data_get($item, 'company_name'),
                    'business_unit_code' => data_get($item, 'business_unit_code'),
                    'business_unit_name' => data_get($item, 'business_unit_name'),
                    'department_code' => data_get($item, 'department_code'),
                    'department_name' => data_get($item, 'department_name'),
                    'unit_code' => data_get($item, 'unit_code'),
                    'unit_name' => data_get($item, 'unit_name'),
                    'sub_unit_code' => data_get($item, 'sub_unit_code'),
                    'sub_unit_name' => data_get($item, 'sub_unit_name'),
                    'location_code' => data_get($item, 'location_code'),
                    'location_name' => data_get($item, 'location_name'),
                ];

                // Use updateOrCreate instead of upsert
                $this->charge->updateOrCreate(
                    ['code' => data_get($item, 'code')], // Search criteria
                    $chargeData // Data to update/create
                );
            }
        });
    }
}