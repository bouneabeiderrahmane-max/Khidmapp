<?php

namespace App\Providers;

use App\Contracts\CatalogFetcher;
use App\Contracts\SmsGateway;
use App\Contracts\TranslatorGateway;
use App\Services\Catalog\PassthroughTranslator;
use App\Services\Catalog\StubCatalogFetcher;
use App\Services\Sms\LogSmsGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);
        $this->app->bind(CatalogFetcher::class, StubCatalogFetcher::class);
        $this->app->bind(TranslatorGateway::class, PassthroughTranslator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
