<?php

namespace Amarenkov\MutableContentDaisyUi;

use Illuminate\Support\ServiceProvider;

class MutableContentDaisyUiServiceProvider extends ServiceProvider
{
    // protected

    // public
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mutable-content-daisyui');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/mutable-content-daisyui'),
        ], 'mutable-content-daisyui-views');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mutable-content-daisyui');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/mutable-content-daisyui'),
        ], 'mutable-content-daisyui-lang');
    }
}
