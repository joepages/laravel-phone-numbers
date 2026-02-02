<?php

declare(strict_types=1);

namespace PhoneNumbers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use PhoneNumbers\Contracts\PhoneNumberRepositoryInterface;
use PhoneNumbers\Contracts\PhoneNumberServiceInterface;
use PhoneNumbers\Repositories\PhoneNumberRepository;
use PhoneNumbers\Services\PhoneNumberService;

class PhoneNumbersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/phone-numbers.php',
            'phone-numbers'
        );

        // Repository
        $this->app->bind(PhoneNumberRepositoryInterface::class, PhoneNumberRepository::class);

        // Service
        $this->app->singleton(PhoneNumberServiceInterface::class, PhoneNumberService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        $this->registerRouteMacro();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/phone-numbers.php' => config_path('phone-numbers.php'),
            ], 'phone-numbers-config');

            $this->commands([
                Console\Commands\InstallPhoneNumbersCommand::class,
            ]);
        }
    }

    protected function registerRouteMacro(): void
    {
        Route::macro('phoneNumberRoutes', function (string $prefix, string $controller) {
            $singular = Str::singular($prefix);

            Route::prefix("{$prefix}/{{$singular}}")->group(function () use ($controller) {
                Route::get('/phone-numbers', [$controller, 'listPhoneNumbers']);
                Route::post('/phone-numbers', [$controller, 'storePhoneNumber']);
                Route::put('/phone-numbers/{phoneNumber}', [$controller, 'updatePhoneNumber']);
                Route::delete('/phone-numbers/{phoneNumber}', [$controller, 'deletePhoneNumber']);
            });
        });
    }
}
