<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function __construct() {
        $this->db = DB::table('settings');
    }

    public function index()
    {
        $settings = DB::table('settings')
            ->leftJoin('users', function ($join) {
                $join->on(
                    DB::raw("CASE WHEN settings.value1 ~ '^[0-9]+$' THEN settings.value1::bigint END"),
                    '=',
                    'users.id'
                )
                    ->whereNotNull('settings.value2')
                    ->where('settings.value2', '!=', ''); // treat empty string as "not present" too
            })
            ->select(
                'settings.id',
                'settings.key',
                'settings.value',
                'settings.value1',
                'settings.value2',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name")
            )
            ->get();

        return response()->json($settings);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'value1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'value2' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (empty($validated)) {
            return response()->json(['message' => 'No fields provided to update.'], 422);
        }

        $updated = $this->db->where('id', $id)->update($validated);

        if ($updated === 0) {
            return response()->json(['message' => 'Setting not found or no changes applied.'], 404);
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }

    public function toggle($id)
    {
        $entryEnabled = $this->db
            ->where('id', $id)
            ->first();

        if ($entryEnabled->value == 1) {
            DB::table('settings')->where('id', $id)
                ->update(['value' => 0]);
        } else {
            DB::table('settings')->where('id', $id)
                ->update(['value' => 1]);
        }

        return response()->json(['message' => 'Status updated.']);
    }
}

