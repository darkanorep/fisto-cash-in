<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordPendingUserRequest;
use App\Http\Requests\CreatePendingUserRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use App\Services\PendingUserService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PendingUserController extends Controller
{
    use ApiResponse;
    public function __construct(private readonly PendingUserService $accounts) {}

    public function index(Request $request) {
        $users = $this->accounts->getPendingUser();

        if ($users->isEmpty()) {
            return $this->responseNotFound('No Accounts found.');
        }

        return $this->responseSuccess($users);
    }
    public function store(CreatePendingUserRequest $request)
    {
        $result = $this->accounts->createOrUpdatePendingUser($request->validated());

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function changePassword(ChangePasswordPendingUserRequest $request, string $idPrefixIdNo)
    {
        $result = $this->accounts->changePassword(
            $idPrefixIdNo,
            $request->validated('old_password'),
            $request->validated('password'),
        );

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function resetPassword(string $idPrefixIdNo)
    {
        $this->authorize('resetPassword', User::class);

        $result = $this->accounts->resetPassword($idPrefixIdNo);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
