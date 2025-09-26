<?php

namespace App\Services;

use App\Models\AccountTitle;
use Illuminate\Http\Request;


class AccountTitleService
{
    protected $accountTitle;

    public function __construct(AccountTitle $accountTitle) {
        $this->accountTitle = $accountTitle;
    }

    public function getAccountTitles(Request $request) {
        return $this->accountTitle->dynamicPaginate();
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
}