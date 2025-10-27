<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('admin', function (User $user) {
            return $user->roles->contains('name', Role::ADMIN);
        });

        Gate::define('create-transaction', function (User $user) {
            return $user->roles->contains('name', Role::REQUESTOR);
        });

        Gate::define('transaction', function (User $user, $transaction = null) {
            // Handle case where $transaction is a string/ID instead of model instance
            if (is_string($transaction) || is_numeric($transaction)) {
                $transaction = \App\Models\Transaction::find($transaction);
            }

            // TAGGER role can access all transactions
            if ($user->roles->contains('name', Role::TAGGER)) {
                return true;
            }
            
            // Check if transaction exists and belongs to user
            if ($transaction && $transaction->user_id === $user->id) {
                return true;
            }
            
            return false;
        });

        Gate::define('tag-transaction', function (User $user) {
            return $user->roles->contains('name', Role::TAGGER);
        });
    }
}
