<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;
    use ApiResponse;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request) {
        $customers = $this->customerService->getAllCustomers($request);

        $customers->getCollection()->transform(function ($customers) {
            return new CustomerResource($customers);
        });

        return $this->responseSuccess('Customers fetched successfully', $customers);
    }

    public function store(CustomerRequest $request) {
        $data = $request->validated();
        return $this->responseSuccess('Customer created successfully', $this->customerService->createCustomer($data), 201);
    }

    public function show($id) {

        if (!$this->customerService->getCustomerById($id)) {
            return $this->responseNotFound('Customer not found');
        }

        return $this->responseSuccess('Customer fetched successfully', new CustomerResource($this->customerService->getCustomerById($id)));
    }

    public function update(CustomerRequest $request, $id) {

        $data = $request->validated();
        $customer = $this->customerService->getCustomerById($id);
        if (!$customer) {
            return $this->responseNotFound('Customer not found');
        }

        return $this->responseSuccess('Customer updated successfully', $this->customerService->updateCustomer($customer, $data));
    }

    public function destroy($id) {
        $customer = $this->customerService->changeStatus($id);
        if (!$customer) {
            return $this->responseNotFound('Customer not found');
        }

        return $this->responseSuccess('Customer status changed successfully', $customer);
    }
    
}
