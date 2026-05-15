<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Tests;

use Junges\CloudflareMail\Providers\CloudflareMailServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CloudflareMailServiceProvider::class,
        ];
    }
}
