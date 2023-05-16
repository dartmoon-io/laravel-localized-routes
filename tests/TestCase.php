<?php

namespace Tests;

use Dartmoon\LaravelLocalized\LaravelLocalizedServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelLocalizedServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('locale.available', [
            'en' => 'EN',
            'it' => 'IT',
        ]);

        config()->set('locale.default', 'en');
    }
}