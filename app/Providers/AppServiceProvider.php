<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Actions\CancelOrderAction;
use App\Actions\ProcessOrderAction;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Repositories
        $this->app->bind(ProductRepository::class, function ($app) {
            return new ProductRepository(new Product());
        });

        $this->app->bind(OrderRepository::class, function ($app) {
            return new OrderRepository(new Order());
        });

        // Bind Services
        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService($app->make(ProductRepository::class));
        });

        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService(
                $app->make(OrderRepository::class),
                $app->make(ProcessOrderAction::class),
                $app->make(CancelOrderAction::class)
            );
        });

        // Bind Actions
        $this->app->bind(ProcessOrderAction::class);
        $this->app->bind(CancelOrderAction::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
