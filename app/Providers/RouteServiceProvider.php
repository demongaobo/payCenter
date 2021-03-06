<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace_order = 'App\Order\Controllers\api\v1';
    protected $namespace_warehouse = 'App\Warehouse\Controllers\api\v1';
    protected $namespace_orderuser = 'App\OrderUser\Controllers\api\v1';
    protected $namespace_activity = 'App\Activity\Controllers\api\v1';
    protected $namespace_company = 'App\Company\Controllers\api\v1';//企业租赁
    protected $namespace_shop = 'App\Shop\Controllers\api\v1';//商品系统

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapWarehouseRoutes();

        $this->mapOrderUserRoutes();

        $this->mapActivityRoutes();

        $this->mapCompanyRoutes();

        $this->mapShopRoutes();

    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace_order)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace_order)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "warehouse" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapWarehouseRoutes()
    {
        Route::prefix('warehouse')
            ->middleware('warehouse')
            ->namespace($this->namespace_warehouse)
            ->group(base_path('routes/warehouse.php'));
    }
    /**
     * Define the "activity" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapActivityRoutes()
    {
        Route::prefix('activity')
            ->middleware('activity')
            ->namespace($this->namespace_activity)
            ->group(base_path('routes/activity.php'));
    }

    /**
     * Define the "orderuser" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapOrderUserRoutes()
    {
        Route::prefix('orderuser')
            ->middleware('orderuser')
            ->namespace($this->namespace_orderuser)
            ->group(base_path('routes/orderuser.php'));
    }

    /**
     * Define the "company" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapCompanyRoutes()
    {
        Route::prefix('company')
            ->middleware('company')
            ->namespace($this->namespace_company)
            ->group(base_path('routes/company.php'));
    }


    /**
     * Define the "shop" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapShopRoutes()
    {
        Route::prefix('shop')
            ->middleware('shop')
            ->namespace($this->namespace_company)
            ->group(base_path('routes/shop.php'));
    }
}
