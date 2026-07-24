<?php

use App\Http\Controllers\AccountTitleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ChargesController;
use App\Http\Controllers\ClearController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SlipController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//Authentication
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function() {

    //Admin Routes
    Route::group(['middleware' => 'admin'], function() {
        //Password Reset
        Route::patch('reset-password/{id}', [AuthController::class, 'resetPassword']);

        Route::group(['prefix' => 'admin'], function() {

            //TRUNCATE
            Route::delete('transactions/truncate', [TransactionController::class, 'truncate']);
            Route::delete('users/truncate', [UserController::class, 'truncate']);

            //Roles
            Route::resource('roles', RoleController::class);

            //Permissions
            Route::resource('permissions', PermissionController::class);

            //Users
            Route::resource('users', UserController::class);

            //Account Titles
            Route::post('account-titles/sync', [AccountTitleController::class, 'sync']);
            Route::resource('account-titles', AccountTitleController::class);

            //One Charging
            Route::post('charges/sync', [ChargesController::class, 'sync']);
            Route::resource('charges', ChargesController::class);

            //Customers
            Route::resource('customers', CustomerController::class);

            //Banks
            Route::resource('banks', BankController::class);

            //Entries
            Route::resource('entries', EntryController::class);
        });

        Route::patch('settings/{id}/toggle', [SettingController::class, 'toggle']);
        Route::resource('settings', SettingController::class);
    });

    //Dropdown
    Route::group(['prefix' => 'dropdown'], function () {
        Route::get('banks', [BankController::class, 'index']);
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('charges', [ChargesController::class, 'index']);
        Route::get('account-titles', [AccountTitleController::class, 'index']);
    });

    //Transactions
    Route::get('transactions/export', [TransactionController::class, 'export']);
    Route::delete('transactions/void/{transaction}', [TransactionController::class, 'void']);
    Route::resource('transactions', TransactionController::class);
    Route::get('transactions-status-count', [TransactionController::class, 'statusCount']);

    //Tagging
    Route::get('tag-transactions', [TagController::class, 'index']);
    Route::post('tag-transaction', [TagController::class, 'action']);
    Route::get('tag-status-count', [TagController::class, 'statusCount']);

    //Clearing
    Route::get('clear-transactions', [ClearController::class, 'index']);
    Route::post('clear-transaction', [ClearController::class, 'action']);
    Route::get('clear-status-count', [ClearController::class, 'statusCount']);

    //Filing
    Route::get('file-transactions', [FileController::class, 'index']);
    Route::post('file-transaction', [FileController::class, 'action']);
    Route::get('file-status-count', [FileController::class, 'statusCount']);

    //Slip
    Route::get('remaining-slip-amount', [SlipController::class, 'getRemainingSlipAmount']);


    //Change Password
    Route::post('change-password', [AuthController::class, 'changePassword']);
    //Logout
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware('api_key')->group(function () {
    Route::post('transactions/external', [TransactionController::class, 'store']);
});
