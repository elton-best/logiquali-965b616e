<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Always allow in local/testing environment
            if (in_array(app()->environment(), ['local', 'testing'])) {
                return true;
            }

            // In production, require authenticated user with permission
            if (!$user) {
                return false;
            }

            // Check if user has dashboard access permission
            try {
                return $user->hasPermissionTo('dashboard.read');
            } catch (\Exception $e) {
                return false;
            }
        });
    }
}
