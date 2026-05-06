<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        /**
         * Simple role-based Gate definitions.
         *
         * Using direct role column on users table instead of Spatie permissions
         * for simpler, more maintainable RBAC.
         */

        // ADMIN can manage everything
        Gate::define('manage-admin', fn(User $user) => $user->isAdmin());

        // ADMIN + KAJUR can manage assets
        Gate::define('manage-assets', fn(User $user) => $user->canManageAssets());

        // ADMIN can approve borrows
        Gate::define('approve-borrows', fn(User $user) => $user->canApprove());

        // ADMIN + KAJUR can borrow
        Gate::define('create-borrows', fn(User $user) => $user->canManageAssets());

        // All authenticated users can view
        Gate::define('view-assets', fn(User $user) => true);
    }
}
