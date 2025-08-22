<?php

declare(strict_types=1);

namespace Bmadigan\Overpass;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Bmadigan\Overpass\Commands\OverpassInstallCommand;
use Bmadigan\Overpass\Commands\OverpassTestCommand;
use Bmadigan\Overpass\Services\PythonAiBridge;

class OverpassServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('overpass')
            ->hasConfigFile('overpass')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('bmadigan/overpass');
            })
            ->hasCommands([
                OverpassInstallCommand::class,
                OverpassTestCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        // Register the main bridge service
        $this->app->singleton(PythonAiBridge::class, function ($app) {
            return new PythonAiBridge();
        });

        // Register the bridge with an alias for easier access
        $this->app->alias(PythonAiBridge::class, 'overpass');
    }

    public function packageRegistered(): void
    {
        // Additional registration logic if needed
    }

    public function packageBooted(): void
    {
        // Additional boot logic if needed
    }
}