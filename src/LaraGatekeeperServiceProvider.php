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
use Illuminate\Support\Facades\Auth;
use Callcocam\LaraGatekeeper\Core\Landlord\Providers\LandlordAuthProvider;
use Callcocam\LaraGatekeeper\Core\Landlord\Providers\TenantAuthProvider;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;

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
            ->hasRoutes('web','api')
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

    public function packageRegistered()
    {
        $this->app->register(\Callcocam\LaraGatekeeper\Core\Shinobi\ShinobiServiceProvider::class);
        $this->app->register(\Callcocam\LaraGatekeeper\Core\Landlord\LandlordServiceProvider::class);
        
        // Registrar GuardManager como singleton
        $this->app->singleton(\Callcocam\LaraGatekeeper\Core\Landlord\Guards\GuardManager::class, function ($app) {
            return new \Callcocam\LaraGatekeeper\Core\Landlord\Guards\GuardManager(
                $app[TenantManager::class]
            );
        });
    }

    public function packageBooted()
    {
        $this->registerAuthProviders();
        $this->registerAuthGuards();
        $this->registerMiddleware();
    }

    /**
     * Registrar os providers de autenticação customizados
     */
    protected function registerAuthProviders()
    {
        // Registrar LandlordAuthProvider
        Auth::provider('landlord', function ($app, array $config) {
            return new LandlordAuthProvider(
                $app['hash'],
                $config['model']
            );
        });

        // Registrar TenantAuthProvider
        Auth::provider('tenant', function ($app, array $config) {
            return new TenantAuthProvider(
                $app['hash'],
                $config['model'],
                $app[TenantManager::class]
            );
        });
    }

    /**
     * Registrar os guards de autenticação customizados
     */
    protected function registerAuthGuards()
    {
        // Registrar guard Landlord customizado
        Auth::extend('landlord', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            
            return new \Callcocam\LaraGatekeeper\Core\Landlord\Guards\LandlordGuard(
                $name,
                $provider,
                $app['session.store'],
                $app['request'],
                $app[TenantManager::class]
            );
        });

        // Registrar guard Tenant customizado
        Auth::extend('tenant', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            
            return new \Callcocam\LaraGatekeeper\Core\Landlord\Guards\TenantGuard(
                $name,
                $provider,
                $app['session.store'],
                $app['request'],
                $app[TenantManager::class]
            );
        });

        // Configurar guards no config/auth.php dinamicamente
        $this->configureAuthConfig();
    }

    /**
     * Configurar o config/auth.php dinamicamente
     */
    protected function configureAuthConfig()
    {
        $config = config('lara-gatekeeper');
        
        if (isset($config['guards'])) {
            // Adicionar guards ao config de auth
            config(['auth.guards.landlord' => $config['guards']['landlord']]);
            config(['auth.guards.tenant' => $config['guards']['tenant']]);
        }

        if (isset($config['providers'])) {
            // Adicionar providers ao config de auth
            config(['auth.providers.landlord_users' => $config['providers']['landlord_users']]);
            config(['auth.providers.tenant_users' => $config['providers']['tenant_users']]);
        }
    }

    /**
     * Registrar middlewares
     */
    protected function registerMiddleware()
    {
        $router = $this->app['router'];

        // Registrar middlewares
        $router->aliasMiddleware('tenant', \Callcocam\LaraGatekeeper\Http\Middleware\TenantMiddleware::class);
        $router->aliasMiddleware('landlord', \Callcocam\LaraGatekeeper\Http\Middleware\LandlordMiddleware::class);
        $router->aliasMiddleware('tenant-resolver', \Callcocam\LaraGatekeeper\Http\Middleware\TenantResolver::class);

        // Adicionar middleware global para resolução de tenant
        if (config('lara-gatekeeper.url_resolution.enabled', true)) {
            $router->pushMiddlewareToGroup('web', \Callcocam\LaraGatekeeper\Http\Middleware\TenantResolver::class);
        }
    }
}
