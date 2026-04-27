<?php

namespace App\Services;

use App\Models\AccountTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class AccountTitleService
{
    protected $accountTitle;

    public function __construct(AccountTitle $accountTitle) {
        $this->accountTitle = $accountTitle;
    }

    public function getAccountTitles(Request $request) {
        return $this->accountTitle->useFilters()->dynamicPaginate();
    }

    public function createAccountTitle($data) {
        return $this->accountTitle->create($data);
    }

    public function getAccountTitleById($id) {
        return $this->accountTitle->find($id);
    }

    public function updateAccountTitle($accountTitle, $data) {
        $accountTitle->update($data);

        return $accountTitle;
    }

    public function changeStatus($id) {
        $accountTitle = $this->accountTitle->withTrashed()->find($id);

        if ($accountTitle->trashed()) {
            $accountTitle->restore();
        } else {
            $accountTitle->delete();
        }

        return $accountTitle;
    }

    public function sync() {
        $url = Http::withHeaders(['API_KEY' => config('app.one_charging_key')])->get('https://api-one.rdfmis.com/api/account_title_external', ['pagination' => 'none']);

        $data = json_decode($url->body(), true);

        collect(data_get($data, 'data'))->chunk(100)->each(function ($chunk) {
            foreach ($chunk as $item) {
                $accountTitleData = [
                    'sync_id' => $item['id'],
                    'code' => data_get($item, 'code'),
                    'name' => data_get($item, 'name'),
                    'account_type' => data_get($item, 'account_type_name'),
                    'account_group' => data_get($item, 'account_group_name'),
                    'sub_group' => data_get($item, 'account_sub_group_name'),
                    'financial_statement' => data_get($item, 'financial_statement_name'),
                    'normal_balance' => data_get($item, 'normal_balance_name'),
                    'allocation' => data_get($item, 'allocation_name'),
                    'unit' => data_get($item, 'account_unit_name'),
                    'charge' => data_get($item, 'charge'),
                    'created_at' => data_get($item, 'created_at'),
                    'updated_at' => data_get($item, 'updated_at'),
                    'deleted_at' => data_get($item, 'deleted_at'),
                ];

                 //Use updateOrCreate instead of upsert
                $this->accountTitle->updateOrCreate(
                    [
                        'code' => data_get($item, 'code'),
                        'sync_id' => data_get($item, 'id'),
                    ], // Search criteria
                    $accountTitleData // Data to update/create
                );
            }
        });
    }
}
