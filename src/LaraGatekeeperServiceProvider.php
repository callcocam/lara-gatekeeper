<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Callcocam\LaraGatekeeper\Commands\LaraGatekeeperCommand;

class LaraGatekeeperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('lara-gatekeeper')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_lara_gatekeeper_table')
            ->hasCommand(LaraGatekeeperCommand::class);
    }
}
