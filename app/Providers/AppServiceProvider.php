<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LogStreamService;
use App\Services\BlockByTimestamp;
use App\Services\BalanceUtil;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(BlockByTimestamp::class, function ($app) {
            return new BlockByTimestamp();
        });

        $this->app->bind(BalanceUtil::class, function ($app) {
            return new BalanceUtil();
        });

        $this->app->bind(LogStreamService::class, function ($app) {
            $blockByTimestamp = $app->make(BlockByTimestamp::class);
            $balanceUtil = $app->make(BalanceUtil::class);
            return new LogStreamService($blockByTimestamp, $balanceUtil);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
