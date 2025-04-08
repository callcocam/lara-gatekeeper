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
use Callcocam\LaraGatekeeper\Commands\LaraGatekeeperSetupCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

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
            ->hasMigrations(
                'alter_users_table',
                'create_tenants_table',
                'create_addresses_table',
                'create_roles_table',
                'create_permissions_table',
                'create_permission_role_table',
                'create_role_user_table',
                'create_permission_user_table'
            )
            ->hasCommand(LaraGatekeeperCommand::class)
            ->hasTranslations()
            ->hasAssets()
            ->hasCommand(LaraGatekeeperSetupCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->publish('lara-gatekeeper:translations')
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('callcocam/lara-gatekeeper')
                    ->endWith(function (InstallCommand $command) {
                        $command->call('lara-gatekeeper:setup');
                    });
            });
    }
}
