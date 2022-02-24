<?php

namespace App\Providers;

use App\Classes\TelegramBotHandler;
use App\Classes\TelegramMessage;
use Illuminate\Support\ServiceProvider;

class FacadeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('military.service', fn () => new TelegramBotHandler());

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
