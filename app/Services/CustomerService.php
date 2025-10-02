<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    protected $customer;

    public function __construct(Customer $customer) {
        $this->customer = $customer;
    }

    public function getAllCustomers() {
        return $this->customer->dynamicPaginate();
    }

    public function createCustomer($data) {
        return $this->customer->create($data);
    }

    public function getCustomerById($id) {
        return $this->customer->find($id);
    }

    public function updateCustomer($customer, $data) {
        $customer->update($data);
        
        return $customer;
    }

    public function changeStatus($id) {
        $customer = $this->customer->withTrashed()->find($id);

        if ($customer->trashed()) {
            $customer->restore();
        } else {
            $customer->delete();
        }

        return $customer;
    }
}