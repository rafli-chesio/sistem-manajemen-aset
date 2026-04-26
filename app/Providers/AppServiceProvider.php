<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

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
         * Bridge Spatie permissions → Laravel Gate.
         *
         * This allows $this->authorize('permission.name') in controllers
         * and @can('permission.name') in Blade/Inertia to work seamlessly
         * using the Spatie permission system as the source of truth.
         *
         * Super Admin bypass: any user with the super_admin role automatically
         * passes every Gate check.
         */
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true; // Super admin bypasses all checks
            }
        });

        // Register each Spatie permission as a Gate ability
        // Using a cached approach — only runs once per request
        try {
            Permission::all()->each(function ($permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    return $user->hasPermissionTo($permission);
                });
            });
        } catch (\Exception $e) {
            // Silently fail during migrations when permission table doesn't exist yet
        }
    }
}
