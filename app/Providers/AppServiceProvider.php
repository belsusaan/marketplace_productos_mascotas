<?php

namespace App\Providers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Policies\DeliveryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
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
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Delivery::class, DeliveryPolicy::class);

        // Gates para acciones administrativas globales
        Gate::define('manage-users', function ($user) {
            return $user->hasPermissionTo('manage users');
        });

        Gate::define('manage-categories', function ($user) {
            return $user->hasPermissionTo('manage categories');
        });

        Gate::define('manage-deliveries', function ($user) {
            return $user->hasPermissionTo('manage deliveries');
        });
    }
}
