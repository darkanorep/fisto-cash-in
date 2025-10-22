<?php

namespace App\Http\Controllers;

use App\Services\SlipService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class SlipController extends Controller
{
    use ApiResponse;
    protected $slipService;

    public function __construct(SlipService $slipService)
    {
        $this->slipService = $slipService;
    }

    public function getRemainingSlipAmount(Request $request)
    {
        $type = $request->input('type');
        $slipNumber = $request->input('slip_number');

        return $this->responseSuccess('Remaining slip amount fetched successfully', $this->slipService->remainingSlipAmount($type, $slipNumber));
    }
}
