<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $this->registerPolicies();

        Gate::define('admin', function (User $user) {
            return $user->roles->contains('name', Role::ADMIN);
        });

        Gate::define('transaction', function (User $user, $transaction = null) {
            return $user->roles->contains('name', Role::REQUESTOR) || 
                ($transaction && $transaction->user_id === $user->id);
        });
    }
}
