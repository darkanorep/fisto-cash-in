<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChargeRequest;
use App\Http\Resources\ChargeResource;
use App\Services\ChargeService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class ChargesController extends Controller
{
    use ApiResponse;
    protected $chargeService;

    // Dependency Injection
    public function __construct(ChargeService $chargeService)
    {
        $this->chargeService = $chargeService;
    }

    // List Charges with Pagination and Filtering
    public function index(Request $request) {
        $charges = $this->chargeService->getAllCharges($request);

        $charges->getCollection()->transform(function ($item) {
            return new ChargeResource($item);
        });

        return $this->responseSuccess('Charges fetched successfully', $charges);
    }

    // Create a new Charge
    public function store(ChargeRequest $request) {
        $data = $request->validated();

        return $this->responseSuccess('Charge created successfully', $this->chargeService->createCharge($data), 201);
    }

    // Get a specific Charge by ID
    public function show($id) {
        if (!$this->chargeService->getChargeById($id)) {
            return $this->responseNotFound('Charge not found');
        }

        return $this->responseSuccess('Charge fetched successfully', new ChargeResource($this->chargeService->getChargeById($id)));
    }


    // Update an existing Charge
    public function update(ChargeRequest $request, $id) {
        $data = $request->validated();
        $charge = $this->chargeService->getChargeById($id);
        if (!$charge) {
            return $this->responseNotFound('Charge not found');
        }
        $updatedCharge = $this->chargeService->updateCharge($charge, $data);
        return $this->responseSuccess('Charge updated successfully', new ChargeResource($updatedCharge));
    }

    // Soft Delete (Change Status) of a Charge
    public function destroy($id) {
        $charge = $this->chargeService->changeStatus($id);
        if (!$charge) {
            return $this->responseNotFound('Charge not found');
        }

        return $this->responseSuccess('Charge status changed successfully', $charge);
    }
}
