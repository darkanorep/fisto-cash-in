<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankRequest;
use App\Http\Resources\BankResource;
use App\Services\BankService;
use Illuminate\Http\Request;

class BankController extends Controller
{
    protected $bankService;

    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

    public function index(Request $request) {
        $banks = $this->bankService->getBanks($request);

        $banks->getCollection()->transform(function ($item) {
            return new BankResource($item);
        });

        return $this->responseSuccess('Banks fetched successfully', $banks);
    }

    public function store(BankRequest $request) {
        $data = $request->validated();

        return $this->responseSuccess('Bank created successfully', $this->bankService->createBank($data), 201);
    }

    public function show($id) {
        if (!$this->bankService->getBankById($id)) {
            return $this->responseNotFound('Bank not found');
        }

        return $this->responseSuccess('Bank fetched successfully', new BankResource($this->bankService->getBankById($id)));
    }

    public function update(BankRequest $request, $id) {
        $data = $request->validated();
        $bank = $this->bankService->getBankById($id);
        if (!$bank) {
            return $this->responseNotFound('Bank not found');
        }
        $updatedBank = $this->bankService->updateBank($bank, $data);
        return $this->responseSuccess('Bank updated successfully', new BankResource($updatedBank));
    }

    public function destroy($id) {
        $bank = $this->bankService->changeStatus($id);
        if (!$bank) {
            return $this->responseNotFound('Bank not found');
        }

        return $this->responseSuccess('Bank status changed successfully', $bank);
    }

}
