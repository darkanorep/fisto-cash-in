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
            return $user->roles->contains('name', Role::ADMIN)
                || $user->roles->contains('name', Role::ADMINISTRATOR);
        });

        Gate::define('create-transaction', function (User $user) {
            return $user->roles->contains('name', Role::REQUESTOR);
        });

        Gate::define('transaction', function (User $user, $transaction = null) {
            if (!$transaction) {
                return false;
            }

            if (is_numeric($transaction) || is_string($transaction)) {
                $transaction = \App\Models\Transaction::find($transaction);
                if (!$transaction) {
                    return false;
                }
            }

            // Allow TAGGER to view all transactions
            if ($user->roles->contains('name', Role::TAGGER)) {
                return true;
            }

            // Regular users can only view their own transactions
            return $transaction->user_id === $user->id;
        });

        Gate::define('tag-transaction', function (User $user) {
            return $user->roles->contains('name', Role::TAGGER);
        });

        Gate::define('clear-transaction', function (User $user) {
            return $user->roles->contains('name', Role::CLEARER);
        });
    }
}
