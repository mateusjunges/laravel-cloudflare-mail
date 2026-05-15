<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Junges\CloudflareMail\Cloudflare\Factory;
use Junges\CloudflareMail\Transport\CloudflareTransport;

final class CloudflareMailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('cloudflare', fn (array $config = []): CloudflareTransport => $this->app->make(Factory::class)->make($config));
    }
}
