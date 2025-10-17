<?php

use App\Http\Controllers\AccountTitleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ChargesController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        Route::post('reset-password/{id}', [AuthController::class, 'resetPassword']);

        Route::group(['prefix' => 'admin'], function() {

            //Roles
            Route::resource('roles', RoleController::class);

            //Permissions
            Route::resource('permissions', PermissionController::class);

            //Users
            Route::resource('users', UserController::class);

            //Account Titles
            Route::resource('account-titles', AccountTitleController::class);

            //One Charging
            Route::post('charges/sync', [ChargesController::class, 'sync']);
            Route::resource('charges', ChargesController::class);

            //Customers
            Route::resource('customers', CustomerController::class);

            //Banks
            Route::resource('banks', BankController::class);
        });
    });

    //Transactions
    Route::post('transactions', [TransactionController::class, 'store'])->middleware('can:create-transaction');
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->middleware('can:my-transaction,transaction');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->middleware('can:my-transaction,transaction');
    Route::delete('transactions', [TransactionController::class, 'truncate']);

    //Logout
    Route::post('logout', [AuthController::class, 'logout']);
});