<?php

namespace Amarenkov\MutableContentDaisyUi\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

use Amarenkov\MutableContent\MutableContentServiceProvider;

use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUiServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MutableContentServiceProvider::class,
            MutableContentDaisyUiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('database.default', env('DB_CONNECTION', 'pgsql'));
    }
}
