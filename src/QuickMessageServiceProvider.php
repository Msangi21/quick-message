<?php

namespace GenTech\QuickMessage;

use Illuminate\Support\ServiceProvider;

class QuickMessageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/quickmessage.php' => config_path('quickmessage.php')
        ]);
    }

    public function register()
    {
        $this->app->singleton(SendMessage::class, function (){
            return new SendMessage();
        });
    }
}
