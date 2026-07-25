<?php

namespace App\Services;

use App\Models\PendingUser;
use App\Models\User;
use App\Supports\EmployeeId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PendingUserService
{
    public function getPendingUser() {
        return PendingUser::orderBy('updated_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();
    }
    public function createOrUpdatePendingUser(array $data): array
    {
        $employeeId = EmployeeId::build($data['id_prefix'], $data['id_no']);

        return DB::transaction(function () use ($employeeId, $data) {
            // Lock the row (if it exists) to avoid two concurrent requests
            // both taking the "create" branch.
            $user = User::withTrashed()
                ->where('employee_id', $employeeId)
                ->lockForUpdate()
                ->first();

            if ($user) {
                $user->update([
                    'username' => $data['username'],
                    'password' => Hash::make($data['password']),
                ]);

                return ['message' => 'User updated successfully.', 'status' => 200];
            }

            PendingUser::withTrashed()->updateOrCreate(
                ['employee_id' => $employeeId],
                [
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'suffix'      => $data['suffix'] ?? null,
                    'username'    => $data['username'],
                    'password'    => Hash::make($data['password']),
                    'deleted_at'  => null,
                ]
            );

            return ['message' => 'Pending user created successfully.', 'status' => 201];
        });
    }

    public function changePassword(string $employeeId, string $oldPassword, string $newPassword): array
    {
        $user = User::withTrashed()->where('employee_id', $employeeId)->first();

        if (!$user) {
            return ['message' => 'User not found.', 'status' => 404];
        }

        if (!Hash::check($oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Old password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);

        return ['message' => 'Password changed successfully.', 'status' => 200];
    }

    public function resetPassword(string $employeeId): array
    {
        $user = User::withTrashed()->where('employee_id', $employeeId)->first();

        if (!$user) {
            return ['message' => 'User not found.', 'status' => 404];
        }

        $user->update([
            'password' => Hash::make($user->username),
            // Strongly recommend adding this column so the temp password
            // can't just sit there indefinitely:
            // 'must_change_password' => true,
        ]);

        return ['message' => 'Password reset successfully.', 'status' => 200];
    }

}
