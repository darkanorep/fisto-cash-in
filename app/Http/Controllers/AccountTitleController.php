<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountTitleRequest;
use App\Http\Resources\AccountTitleResource;
use App\Services\AccountTitleService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class AccountTitleController extends Controller
{
    use ApiResponse;
    private $accountTitleService;

    public function __construct(AccountTitleService $accountTitleService) {
        $this->accountTitleService = $accountTitleService;
    }

    public function index(Request $request) {
        $accountTitles = $this->accountTitleService->getAccountTitles($request);

        $accountTitles->getCollection()->transform(function ($item) {
            return new AccountTitleResource($item);
        });

        return $this->responseSuccess('Account Titles fetched successfully', $accountTitles);
    }

    public function store(AccountTitleRequest $request) {
        $data = $request->validated();

        return $this->responseSuccess('Account Title created successfully', $this->accountTitleService->createAccountTitle($data), 201);
    }

    public function show($id) {
        if (!$this->accountTitleService->getAccountTitleById($id)) {
            return $this->responseNotFound('Account Title not found');
        }

        return $this->responseSuccess('Account Title fetched successfully', new AccountTitleResource($this->accountTitleService->getAccountTitleById($id)));
    }

    public function update(AccountTitleRequest $request, $id) {
        $data = $request->validated();
        $accountTitle = $this->accountTitleService->getAccountTitleById($id);
        if (!$accountTitle) {
            return $this->responseNotFound('Account Title not found');
        }
        $updatedAccountTitle = $this->accountTitleService->updateAccountTitle($accountTitle, $data);
        return $this->responseSuccess('Account Title updated successfully', new AccountTitleResource($updatedAccountTitle));
    }

    public function destroy($id) {
        $accountTitle = $this->accountTitleService->changeStatus($id);
        if (!$accountTitle) {
            return $this->responseNotFound('Account Title not found');
        }

        return $this->responseSuccess('Account Title status changed successfully', $accountTitle);
    }
}
