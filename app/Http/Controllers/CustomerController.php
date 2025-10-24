<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerController extends Controller
{
    protected $customerService;
    use ApiResponse;

    // Dependency Injection
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    // List Customers with Pagination and Filtering
    public function index(Request $request) {
        $customers = $this->customerService->getAllCustomers($request);

        $customers instanceof LengthAwarePaginator
            ? $customers->setCollection($customers->getCollection()->transform(function ($item) {
                    return new CustomerResource($item);
                })) 
            : $customers = CustomerResource::collection($customers);

        return $customers->isEmpty()
            ? $this->responseNotFound('No Customers found.')
            : $this->responseSuccess('Customers fetched successfully', $customers);
    }

    // Create a new Customer
    public function store(CustomerRequest $request) {
        $data = $request->validated();
        return $this->responseCreated('Customer created successfully', new CustomerResource($this->customerService->createCustomer($data)));
    }

    // Get a specific Customer by ID
    public function show($id) {

        if (!$this->customerService->getCustomerById($id)) {
            return $this->responseNotFound('Customer not found');
        }

        return $this->responseSuccess('Customer fetched successfully', new CustomerResource($this->customerService->getCustomerById($id)));
    }

    // Update an existing Customer
    public function update(CustomerRequest $request, $id) {

        $data = $request->validated();
        $customer = $this->customerService->getCustomerById($id);
        if (!$customer) {
            return $this->responseNotFound('Customer not found');
        }

        return $this->responseSuccess('Customer updated successfully', $this->customerService->updateCustomer($customer, $data));
    }

    // Soft Delete (Change Status) of a Customer
    public function destroy($id) {
        $customer = $this->customerService->changeStatus($id);
        if (!$customer) {
            return $this->responseNotFound('Customer not found');
        }

        return $this->responseSuccess('Customer status changed successfully', $customer);
    }
    
}
