<?php

namespace App\Providers;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderShop;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Policies\DisputePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ShopPolicy;
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
        // Registro explícito de las Policies de dominio
        Gate::policy(Shop::class, ShopPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(OrderShop::class, OrderPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Dispute::class, DisputePolicy::class);
    }
}
